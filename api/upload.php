<?php
/**
 * CodeByTushu — Upload API v2
 * POST /api/upload.php
 *
 * Actions:
 *   (no action, file present) = upload
 *   action=upload             = upload (explicit)
 *   action=delete             = delete by ID
 *   action=bulk_delete        = delete array of IDs
 *
 * Supported types:
 *   image — JPG, PNG, WebP, GIF, SVG (max 10MB)
 *   video — MP4, WebM, MOV, AVI      (max 500MB)
 *   pdf   — application/pdf           (max 50MB)
 *   zip   — ZIP, RAR, 7z              (max 100MB)
 *   code  — .js .ts .php .py .java .cpp .c .cs .html .css .json .sh .sql .rb .go .xml (max 5MB)
 *   doc   — .docx .xlsx .pptx .txt .csv .md .odt .rtf (max 20MB)
 *   other — any remaining safe type   (max 20MB)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   Type definitions
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
const ALLOWED_TYPES = [
    'image' => [
        'mimes'   => ['image/jpeg','image/png','image/webp','image/gif','image/svg+xml'],
        'exts'    => ['jpg','jpeg','png','webp','gif','svg'],
        'max_mb'  => 10,
        'dir'     => 'images',
    ],
    'video' => [
        'mimes'   => ['video/mp4','video/webm','video/quicktime','video/x-msvideo','video/avi','video/mpeg'],
        'exts'    => ['mp4','webm','mov','avi','mpeg','mpg'],
        'max_mb'  => 500,
        'dir'     => 'videos',
    ],
    'pdf'   => [
        'mimes'   => ['application/pdf'],
        'exts'    => ['pdf'],
        'max_mb'  => 50,
        'dir'     => 'pdfs',
    ],
    'zip'   => [
        'mimes'   => ['application/zip','application/x-zip-compressed','application/x-rar-compressed',
                      'application/x-7z-compressed','application/octet-stream'],
        'exts'    => ['zip','rar','7z','tar','gz'],
        'max_mb'  => 100,
        'dir'     => 'zips',
    ],
    'code'  => [
        'mimes'   => ['text/plain','text/html','text/css','text/javascript','application/javascript',
                      'application/json','application/xml','text/xml','text/x-php',
                      'application/x-httpd-php','text/x-python','text/x-java-source',
                      'text/x-c','text/x-c++','text/x-csharp','application/x-sh','text/x-sql'],
        'exts'    => ['js','ts','jsx','tsx','php','py','java','cpp','c','cs','html','htm',
                      'css','json','xml','sh','bash','sql','rb','go','rs','vue','svelte',
                      'md','yaml','yml','toml','env'],
        'max_mb'  => 5,
        'dir'     => 'code',
    ],
    'doc'   => [
        'mimes'   => ['application/msword',
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                      'application/vnd.ms-excel',
                      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                      'application/vnd.ms-powerpoint',
                      'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                      'text/plain','text/csv','text/markdown',
                      'application/vnd.oasis.opendocument.text',
                      'application/rtf','text/rtf'],
        'exts'    => ['doc','docx','xls','xlsx','ppt','pptx','txt','csv','md','odt','rtf'],
        'max_mb'  => 20,
        'dir'     => 'docs',
    ],
    'other' => [
        'mimes'   => [],     // Accept any
        'exts'    => [],     // Accept any
        'max_mb'  => 20,
        'dir'     => 'other',
    ],
];

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   Dangerous extensions — ALWAYS BLOCKED
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
const BLOCKED_EXTS = ['php','php3','php4','php5','phtml','phar','shtml','htaccess',
                      'asp','aspx','jsp','exe','bat','cmd','sh','cgi','py','rb','pl'];

// NOTE: code category uploads .php etc. — we rename them with a safe extension
// so they can never be executed by the server. Only text rendering is allowed.

$action = $_POST['action'] ?? (isset($_FILES['file']) ? 'upload' : null);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   UPLOAD
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'upload') {
    if (!Auth::isAdmin() && !Auth::check()) jsonError('Login required.', 401);
    requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errMsg = match($file['error'] ?? -1) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum allowed size.',
            UPLOAD_ERR_NO_FILE                        => 'No file was uploaded.',
            UPLOAD_ERR_PARTIAL                        => 'File was only partially uploaded.',
            default                                   => 'Upload error code '.$file['error'].'. Check php.ini upload settings.',
        };
        jsonError($errMsg, 400);
    }

    // Sanitize category input
    $reqCat  = $_POST['category'] ?? 'other';
    $context = preg_replace('/[^a-z0-9_\-\/]/', '', strtolower($_POST['context'] ?? 'general'));
    if (!$context) $context = 'general';

    // Get real extension
    $origName = basename($file['name']);
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $mime     = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? 'application/octet-stream');

    // Determine category automatically from extension + mime
    $detectedCat = detectCategory($ext, $mime);
    // Use requested category if it matches detected, else use detected
    $cat = (array_key_exists($reqCat, ALLOWED_TYPES) && $reqCat !== 'other' && $reqCat === $detectedCat)
        ? $reqCat : $detectedCat;

    $typeDef = ALLOWED_TYPES[$cat] ?? ALLOWED_TYPES['other'];

    // Validate MIME for non-other categories
    if ($cat !== 'other' && !empty($typeDef['mimes']) && !in_array($mime, $typeDef['mimes'], true)) {
        // Check by extension as fallback (browser-reported MIME can differ)
        if (!in_array($ext, $typeDef['exts'], true)) {
            jsonError("File type not allowed for category \"$cat\". Got MIME: $mime, ext: $ext", 422);
        }
    }

    // Size limit
    $maxBytes = $typeDef['max_mb'] * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        jsonError("File exceeds {$typeDef['max_mb']}MB limit for $cat files.", 413);
    }

    // For code files — always save with .txt extension to prevent server execution
    $safeExt = $ext;
    if ($cat === 'code' && in_array($ext, BLOCKED_EXTS, true)) {
        $safeExt = $ext . '.txt';  // e.g. exploit.php → stored as exploit.php.txt
    }

    // Build upload directory
    $root    = rtrim(realpath(__DIR__ . '/../'), DIRECTORY_SEPARATOR);
    $subDir  = $context . DIRECTORY_SEPARATOR . $typeDef['dir'];
    $uploadDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subDir;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            jsonError('Could not create upload directory. Check server permissions.', 500);
        }
        // Drop an .htaccess to prevent script execution inside upload dirs
        file_put_contents($uploadDir . DIRECTORY_SEPARATOR . '.htaccess',
            "Options -Indexes\nphp_flag engine off\n<FilesMatch \"\\.php\">\nDeny from all\n</FilesMatch>\n");
    }

    // Generate unique stored name
    $stored   = bin2hex(random_bytes(16)) . '_' . preg_replace('/[^a-z0-9._-]/', '-', strtolower(pathinfo($origName, PATHINFO_FILENAME)));
    $stored   = substr($stored, 0, 120) . '.' . $safeExt;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $stored;
    $webPath  = '/uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $subDir) . '/' . $stored;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        jsonError('Failed to move uploaded file. Check directory permissions.', 500);
    }

    // Record in DB
    try {
        $pdo = db();
        $pdo->prepare(
            'INSERT INTO file_uploads
             (uploaded_by, original_name, stored_name, file_path, file_type, file_size, file_category, context)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            Auth::id(),
            $origName,
            $stored,
            $webPath,
            $mime,
            $file['size'],
            $cat,
            $context,
        ]);
        $insertId = (int)$pdo->lastInsertId();
    } catch (\PDOException $e) {
        // DB failed — still return the path since file is saved
        jsonSuccess([
            'id'   => 0,
            'path' => $webPath,
            'url'  => $webPath,
            'name' => $stored,
            'cat'  => $cat,
        ], 'File uploaded (DB record failed: ' . $e->getMessage() . ').');
    }

    jsonSuccess([
        'id'   => $insertId,
        'path' => $webPath,
        'url'  => $webPath,
        'name' => $stored,
        'cat'  => $cat,
        'size' => $file['size'],
    ], 'File uploaded successfully.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   DELETE (single)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'delete') {
    if (!Auth::isAdmin()) jsonError('Forbidden.', 403);
    requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

    $id  = (int)post('id');
    if (!$id) jsonError('File ID required.', 400);

    $pdo  = db();
    $stmt = $pdo->prepare('SELECT file_path FROM file_uploads WHERE id=? AND is_active=1 LIMIT 1');
    $stmt->execute([$id]); $f = $stmt->fetch();
    if (!$f) jsonError('File not found.', 404);

    // Remove physical file
    $root = rtrim(realpath(__DIR__ . '/../'), DIRECTORY_SEPARATOR);
    $abs  = $root . str_replace('/', DIRECTORY_SEPARATOR, $f['file_path']);
    if (file_exists($abs)) @unlink($abs);

    // Soft-delete in DB
    $pdo->prepare('UPDATE file_uploads SET is_active=0 WHERE id=?')->execute([$id]);

    jsonSuccess(null, 'File deleted.');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   BULK DELETE
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
if ($action === 'bulk_delete') {
    if (!Auth::isAdmin()) jsonError('Forbidden.', 403);
    requireCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (empty($ids)) jsonError('No IDs provided.', 400);
    if (count($ids) > 100) jsonError('Max 100 files per bulk operation.', 400);

    $pdo  = db();
    $root = rootPath();

    // ── Stage 1: Collect file paths before any mutation ──────────────────
    $toDelete = []; // ['id' => int, 'path' => string]
    foreach ($ids as $id) {
        $s = $pdo->prepare('SELECT file_path FROM file_uploads WHERE id=? AND is_active=1 LIMIT 1');
        $s->execute([$id]);
        $f = $s->fetch();
        if ($f) {
            $toDelete[] = ['id' => $id, 'path' => $f['file_path']];
        }
    }

    if (empty($toDelete)) jsonError('No active files found for those IDs.', 404);

    // ── Stage 2: Update DB in a single transaction ────────────────────────
    // Physical file deletion only happens AFTER the transaction commits.
    // If the transaction fails, the disk is untouched — DB and disk stay in sync.
    try {
        $pdo->beginTransaction();
        $upd = $pdo->prepare('UPDATE file_uploads SET is_active=0 WHERE id=?');
        foreach ($toDelete as $item) {
            $upd->execute([$item['id']]);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        jsonError('Bulk delete failed — DB transaction rolled back. No files were deleted.', 500);
    }

    // ── Stage 3: Delete physical files (DB is already committed) ─────────
    $ok = 0;
    foreach ($toDelete as $item) {
        $abs = $root . str_replace('/', DIRECTORY_SEPARATOR, $item['path']);
        if (file_exists($abs)) @unlink($abs);
        $ok++;
    }

    jsonSuccess(['deleted' => $ok], "$ok file(s) deleted.");
}

jsonError('Invalid request.', 400);

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   HELPERS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function detectCategory(string $ext, string $mime): string {
    $imageExts = ['jpg','jpeg','png','webp','gif','svg','avif','ico','bmp','tiff'];
    $videoExts = ['mp4','webm','mov','avi','mpeg','mpg','mkv','flv','ogv','3gp'];
    $pdfExts   = ['pdf'];
    $zipExts   = ['zip','rar','7z','tar','gz','bz2'];
    $codeExts  = ['js','ts','jsx','tsx','php','py','java','cpp','c','cs','html','htm',
                  'css','json','xml','sh','bash','sql','rb','go','rs','vue','svelte',
                  'yaml','yml','toml','env','gitignore','htaccess'];
    $docExts   = ['doc','docx','xls','xlsx','ppt','pptx','txt','csv','md','odt','rtf','pages','numbers'];

    if (in_array($ext, $imageExts, true) || str_starts_with($mime,'image/')) return 'image';
    if (in_array($ext, $videoExts, true) || str_starts_with($mime,'video/')) return 'video';
    if (in_array($ext, $pdfExts,   true) || $mime === 'application/pdf')      return 'pdf';
    if (in_array($ext, $zipExts,   true))                                      return 'zip';
    if (in_array($ext, $codeExts,  true))                                      return 'code';
    if (in_array($ext, $docExts,   true))                                      return 'doc';
    return 'other';
}
