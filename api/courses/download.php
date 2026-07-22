<?php
/**
 * /api/courses/download.php
 * ─────────────────────────────────────────────────────────────
 * Secure course content delivery controller.
 *
 * Security model:
 *   • PDFs live in  private/courses/  (web-inaccessible via .htaccess)
 *   • This script is the ONLY way to access them
 *   • Free courses  → login NOT required, anyone can download
 *   • Paid courses  → must be logged in + have a verified order
 *   • The real filesystem path is NEVER exposed to the client
 *
 * Future-proof:
 *   • content_type = 'pdf'   → stream the PDF
 *   • content_type = 'video' → redirect to signed/secure video URL
 *   • content_type = 'mixed' → stream primary PDF
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();

/* ── 1. Parse & validate course ID ───────────────────────── */
$course_id = (int) ($_GET['id'] ?? 0);
if ($course_id < 1) {
    http_response_code(400);
    exit('Invalid request.');
}

/* ── 2. Fetch published course ────────────────────────────── */
$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, title, price, is_free, download_file_path, content_type
       FROM courses
      WHERE id = ? AND is_published = 1
      LIMIT 1'
);
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

/* ── 3. Access control for PAID courses ──────────────────── */
$isFree = ((float) $course['price'] === 0.0 || (int) $course['is_free'] === 1);

if (!$isFree) {
    // Must be logged in
    if (!Auth::check()) {
        header('Location: /auth/login.php?redirect=' . urlencode('/api/courses/download.php?id=' . $course_id));
        exit;
    }

    // Must have a verified purchase in the orders table
    $checkStmt = $pdo->prepare(
        'SELECT oi.id
           FROM order_items oi
           JOIN orders o ON oi.order_id = o.id
          WHERE o.user_id = ?
            AND oi.course_id = ?
            AND o.payment_status = \'verified\'
          LIMIT 1'
    );
    $checkStmt->execute([Auth::user()['id'], $course_id]);

    if (!$checkStmt->fetch()) {
        http_response_code(403);
        exit('Access denied. Please purchase this course first.');
    }
}

/* ── 4. Resolve the file path ─────────────────────────────── */
$storedPath = $course['download_file_path'] ?? '';

if (empty($storedPath)) {
    http_response_code(404);
    exit('Course material is not available yet. Please check back soon.');
}

// If it's an external URL, redirect (for future CDN/video support)
if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
    header('Location: ' . $storedPath);
    exit;
}

// Build absolute path — stored paths are relative to project root
// e.g.  "private/courses/Java Masterclass for Beginners.pdf"
$rootDir = rtrim(dirname(__DIR__, 2), '/\\');
$absPath = realpath($rootDir . DIRECTORY_SEPARATOR . ltrim($storedPath, '/\\'));

// Security: ensure the resolved path is actually inside private/courses/
$allowedBase = realpath($rootDir . '/private/courses');
if (!$absPath || !$allowedBase || strncmp($absPath, $allowedBase, strlen($allowedBase)) !== 0) {
    http_response_code(403);
    error_log("Download blocked — path traversal attempt: {$storedPath}");
    exit('Access denied.');
}

if (!file_exists($absPath)) {
    http_response_code(404);
    exit('File not found on server. Please contact support.');
}

/* ── 5. Stream the file securely ─────────────────────────── */
// Build a clean download filename from course title
$safeTitle    = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $course['title']);
$safeTitle    = preg_replace('/\s+/', '-', trim($safeTitle));
$ext          = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
$downloadName = $safeTitle . '.' . $ext;

$mime         = mime_content_type($absPath) ?: 'application/octet-stream';
$fileSize     = filesize($absPath);

// Disable output buffering to allow large file streaming
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

readfile($absPath);
exit;
