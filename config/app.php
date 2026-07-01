<?php
/**
 * CodeByTushu — Application Configuration
 * Loads .env and defines all application constants.
 * Include this file FIRST in every PHP entry point.
 */

declare(strict_types=1);

// ── Load .env ──────────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Strip surrounding quotes if present
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}

// Helper to read env with fallback
function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $default;
}

// ── Environment ────────────────────────────────────────────────────────
define('APP_ENV',   env('APP_ENV',   'production'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');
define('SITE_URL',  rtrim(env('SITE_URL', 'https://codebytushu.com'), '/'));
define('APP_NAME',  'CodeByTushu');

// ── Error Handling ─────────────────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ERROR | E_WARNING);
    ini_set('log_errors', '1');
    $logDir = dirname(__DIR__) . '/private/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    ini_set('error_log', $logDir . '/php_errors.log');
}

// ── Database ────────────────────────────────────────────────────────────
define('DB_HOST',    env('DB_HOST',    'localhost'));
define('DB_PORT',    env('DB_PORT',    '3306'));
define('DB_NAME',    env('DB_NAME',    'codebytushu_db'));
define('DB_USER',    env('DB_USER',    'root'));
define('DB_PASS',    env('DB_PASS',    ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// ── Google OAuth ────────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     env('GOOGLE_CLIENT_ID',     ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI',  env('GOOGLE_REDIRECT_URI',  SITE_URL . '/api/auth/callback.php'));

// ── Razorpay Payment ────────────────────────────────────────────────────
define('RAZORPAY_KEY_ID',      env('RAZORPAY_KEY_ID',      ''));
define('RAZORPAY_KEY_SECRET',  env('RAZORPAY_KEY_SECRET',  ''));

// ── Email / SMTP ────────────────────────────────────────────────────────
define('SMTP_HOST',       env('SMTP_HOST',       'smtp.gmail.com'));
define('SMTP_PORT',       (int) env('SMTP_PORT', '587'));
define('SMTP_USER',       env('SMTP_USER',       ''));
define('SMTP_PASS',       env('SMTP_PASS',       ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'noreply@codebytushu.com'));
define('SMTP_FROM_NAME',  env('SMTP_FROM_NAME',  'CodeByTushu'));
define('SMTP_TO_EMAIL',   env('SMTP_TO_EMAIL',   'codebytushu@gmail.com'));

// ── Session ─────────────────────────────────────────────────────────────
define('SESSION_NAME',     env('SESSION_NAME',     'CBT_SESSION'));
define('SESSION_LIFETIME', 86400);   // 24 hours
define('SESSION_DB_CHECK', 1800);    // Re-validate DB every 30 min

// ── Security ─────────────────────────────────────────────────────────────
define('APP_SECRET_KEY', env('APP_SECRET_KEY', 'changeme_insecure_default'));
define('CSRF_TOKEN_KEY', '_cbt_csrf');

// ── Uploads ─────────────────────────────────────────────────────────────
define('UPLOAD_DIR',         dirname(__DIR__) . '/uploads');
define('UPLOAD_URL',         SITE_URL . '/uploads');
define('UPLOAD_MAX_IMAGE',   5 * 1024 * 1024);   // 5 MB
define('UPLOAD_MAX_PDF',     20 * 1024 * 1024);  // 20 MB
define('UPLOAD_MAX_VIDEO',   200 * 1024 * 1024); // 200 MB
define('UPLOAD_MAX_ZIP',     50 * 1024 * 1024);  // 50 MB
define('ALLOWED_IMAGE_MIME', ['image/jpeg','image/png','image/gif','image/webp']);
define('ALLOWED_PDF_MIME',   ['application/pdf']);
define('ALLOWED_VIDEO_MIME', ['video/mp4','video/webm','video/ogg']);
define('ALLOWED_ZIP_MIME',   ['application/zip','application/x-zip-compressed']);

// ── Rate Limiting ────────────────────────────────────────────────────────
define('RATE_LIMIT_CONTACT_MAX',  (int) env('RATE_LIMIT_MAX_REQUESTS',       '3'));
define('RATE_LIMIT_CONTACT_WIN',  (int) env('RATE_LIMIT_WINDOW_SECONDS',     '600'));
define('RATE_LIMIT_LOGIN_MAX',    5);   // 5 failed attempts
define('RATE_LIMIT_LOGIN_WIN',    900); // 15-minute lockout

// ── Feature Flags ────────────────────────────────────────────────────────
define('FEATURE_PHP_AUTH',      env('FEATURE_PHP_AUTH_ACTIVE',      'true')  === 'true');
define('FEATURE_FIREBASE_AUTH', env('FEATURE_FIREBASE_AUTH_ACTIVE', 'false') === 'true');
define('FEATURE_ADMIN_PANEL',   env('FEATURE_ADMIN_PANEL_ACTIVE',   'true')  === 'true');

// ── Paths ────────────────────────────────────────────────────────────────
define('ROOT_DIR',     dirname(__DIR__));
define('INCLUDES_DIR', __DIR__ . '/../includes');
define('ADMIN_DIR',    __DIR__ . '/../admin');
define('CLASSES_DIR',  __DIR__ . '/../classes');

// ── Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));
