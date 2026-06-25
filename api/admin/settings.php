<?php
/**
 * CodeByTushu — Settings Admin API v2
 *
 * Actions:
 *   update_config    — batch-update any site_config keys
 *   update_social    — update a single social_links row
 *   upload_logo      — upload logo file and save path to site_config
 *   upload_favicon   — upload favicon file and save path to site_config
 *   remove_asset     — clear a config key (logo_path / favicon_path)
 *   clear_cache      — clear server-side cache (config|templates|all)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();
if (!Auth::isAdmin()) jsonError('Forbidden', 403);
requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

$pdo    = db();
$action = post('action');

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   update_config — batch upsert site_config rows
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'update_config') {
    $skip    = ['action', 'csrf_token'];
    $updated = 0;

    // Load valid keys (those that exist in site_config)
    $validKeys = $pdo->query('SELECT config_key FROM site_config')
        ->fetchAll(PDO::FETCH_COLUMN);
    $validKeys = array_flip($validKeys);

    // New keys we also allow to be upserted (for future-proofing)
    $allowNew = [
        'logo_path', 'favicon_path',
        'color_primary', 'color_secondary', 'color_bg',
        'footer_tagline', 'footer_about',
        'privacy_policy_url', 'terms_url',
        'seo_og_image', 'seo_keywords',
        'admin_notification_email', 'email_from_name', 'email_autoreply_text',
        'maintenance_title', 'maintenance_message', 'maintenance_eta',
        'google_maps_embed',
    ];

    $upsert = $pdo->prepare(
        'INSERT INTO site_config (config_key, config_value, label, config_group)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW()'
    );

    foreach ($_POST as $key => $value) {
        if (in_array($key, $skip, true)) continue;
        // Only allow known keys or explicitly whitelisted new keys
        if (!isset($validKeys[$key]) && !in_array($key, $allowNew, true)) continue;
        // Determine group
        $group = match(true) {
            str_starts_with($key, 'seo_')           => 'seo',
            str_starts_with($key, 'footer_')        => 'footer',
            str_starts_with($key, 'email_')         => 'email',
            str_starts_with($key, 'maintenance_')   => 'maintenance',
            str_starts_with($key, 'color_')         => 'branding',
            in_array($key, ['logo_path','favicon_path']) => 'branding',
            default                                  => 'general',
        };
        $label = ucwords(str_replace('_', ' ', $key));
        $upsert->execute([sanitize($key), sanitize((string)$value), $label, $group]);
        $updated++;
    }

    jsonSuccess(['updated' => $updated], "$updated setting(s) saved.");
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   update_social — update a social_links row
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'update_social') {
    $id      = (int)post('id');
    if (!$id) jsonError('Social link ID required.', 400);

    $url     = filter_var(post('url'), FILTER_SANITIZE_URL) ?: '';
    $footer  = (int)(bool)post('show_in_footer');
    $contact = (int)(bool)post('show_in_contact');
    $active  = (int)(bool)post('is_active');

    $pdo->prepare(
        'UPDATE social_links
         SET url=?, show_in_footer=?, show_in_contact=?, is_active=?, updated_at=NOW()
         WHERE id=?'
    )->execute([$url, $footer, $contact, $active, $id]);

    jsonSuccess(null, 'Social link updated.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   upload_logo / upload_favicon — file upload → site_config
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'upload_logo' || $action === 'upload_favicon') {
    $isLogo     = ($action === 'upload_logo');
    $fieldName  = $isLogo ? 'logo' : 'favicon';
    $configKey  = $isLogo ? 'logo_path' : 'favicon_path';
    $dirName    = $isLogo ? 'logo' : 'favicon';

    // If URL was provided directly (no file upload)
    $urlVal = post($configKey);
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        if ($urlVal) {
            upsertConfig($pdo, $configKey, sanitize($urlVal), 'branding');
            jsonSuccess(['path' => $urlVal], ($isLogo?'Logo':'Favicon') . ' URL saved.');
        }
        jsonError('No file or URL provided.', 400);
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonError('Upload error: ' . $file['error'], 400);
    }

    // Validate
    $maxBytes = $isLogo ? (2 * 1024 * 1024) : (512 * 1024);
    if ($file['size'] > $maxBytes) {
        jsonError('File too large. Max ' . ($isLogo?'2MB':'512KB') . '.', 413);
    }

    $mime    = mime_content_type($file['tmp_name']);
    $allowed = $isLogo
        ? ['image/png','image/svg+xml','image/jpeg','image/webp','image/gif']
        : ['image/x-icon','image/png','image/gif','image/svg+xml','image/vnd.microsoft.icon'];
    if (!in_array($mime, $allowed, true)) {
        jsonError("Invalid file type: $mime.", 422);
    }

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
    $ext  = preg_replace('/[^a-z0-9]/', '', strtolower($ext));
    $root = rtrim(realpath(__DIR__ . '/../../'), DIRECTORY_SEPARATOR);
    $dir  = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'brand' . DIRECTORY_SEPARATOR . $dirName;

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        jsonError('Cannot create upload directory.', 500);
    }

    // Write .htaccess to disable PHP execution
    if (!file_exists($dir . DIRECTORY_SEPARATOR . '.htaccess')) {
        file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', "Options -Indexes\nphp_flag engine off\n");
    }

    // Delete old file if stored
    $old = $pdo->prepare('SELECT config_value FROM site_config WHERE config_key=? LIMIT 1');
    $old->execute([$configKey]); $oldVal = $old->fetchColumn();
    if ($oldVal && str_starts_with($oldVal, '/uploads/')) {
        $absOld = $root . str_replace('/', DIRECTORY_SEPARATOR, $oldVal);
        if (file_exists($absOld)) @unlink($absOld);
    }

    $stored  = $dirName . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest    = $dir . DIRECTORY_SEPARATOR . $stored;
    $webPath = '/uploads/brand/' . $dirName . '/' . $stored;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonError('Failed to save file.', 500);
    }

    upsertConfig($pdo, $configKey, $webPath, 'branding');

    jsonSuccess(['path' => $webPath], ($isLogo?'Logo':'Favicon') . ' uploaded successfully.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   remove_asset — clear config value + delete file
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'remove_asset') {
    $key = sanitize(post('key'));
    if (!in_array($key, ['logo_path', 'favicon_path'], true)) {
        jsonError('Invalid asset key.', 400);
    }
    $row = $pdo->prepare('SELECT config_value FROM site_config WHERE config_key=? LIMIT 1');
    $row->execute([$key]); $path = $row->fetchColumn();
    if ($path && str_starts_with($path, '/uploads/')) {
        $root = rtrim(realpath(__DIR__ . '/../../'), DIRECTORY_SEPARATOR);
        $abs  = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (file_exists($abs)) @unlink($abs);
    }
    $pdo->prepare('UPDATE site_config SET config_value=NULL WHERE config_key=?')->execute([$key]);
    jsonSuccess(null, 'Asset removed.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   clear_cache — clear opcache / file-based caches
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'clear_cache') {
    $type = post('cache_type', 'all');
    $cleared = [];

    if (in_array($type, ['config', 'all'], true)) {
        // Clear PHP OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $cleared[] = 'OPcache';
        }
        $cleared[] = 'Config cache';
    }

    if (in_array($type, ['templates', 'all'], true)) {
        // Clear any file-based template caches
        $cacheDir = rtrim(realpath(__DIR__ . '/../../'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . DIRECTORY_SEPARATOR . '*.php');
            if ($files) { foreach ($files as $f) @unlink($f); }
        }
        $cleared[] = 'Template cache';
    }

    $msg = empty($cleared) ? 'Nothing to clear.' : implode(', ', $cleared) . ' cleared.';
    jsonSuccess(['cleared' => $cleared], $msg);
}

jsonError('Unknown action: ' . $action, 400);

/* ━━ Helpers ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function upsertConfig(PDO $pdo, string $key, string $value, string $group): void {
    $label = ucwords(str_replace('_', ' ', $key));
    $pdo->prepare(
        'INSERT INTO site_config (config_key, config_value, label, config_group)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW()'
    )->execute([$key, $value, $label, $group]);
}
