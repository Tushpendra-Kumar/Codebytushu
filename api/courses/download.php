<?php
/**
 * API: /api/courses/download.php
 * Serves a course PDF download securely.
 *
 * - Free courses  → anyone can download (no login required)
 * - Paid courses  → only users with a verified purchase via orders table
 *
 * Future-proof: also supports 'video' content_type (returns redirect to video URL).
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();

$course_id = intval($_GET['id'] ?? 0);

if (!$course_id) {
    http_response_code(400);
    die('Invalid request.');
}

$pdo = db();

// Fetch the course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND is_published = 1");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    die('Course not found.');
}

$isFree      = ((float) $course['price'] == 0);
$contentType = $course['content_type'] ?? 'pdf'; // future-proof: 'pdf' | 'video'

// --- Access check for paid courses ---
if (!$isFree) {
    if (!Auth::check()) {
        header('Location: /auth/login.php?redirect=' . urlencode('/api/courses/download.php?id=' . $course_id));
        exit;
    }

    $check = $pdo->prepare("
        SELECT oi.id FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.course_id = ? AND o.payment_status = 'verified'
    ");
    $check->execute([Auth::user()['id'], $course_id]);

    if (!$check->fetch()) {
        http_response_code(403);
        die('Access denied. Please purchase this course first.');
    }
}

// --- Serve the file ---
$filePath = $course['download_file_path'] ?? null;

if (empty($filePath)) {
    http_response_code(404);
    die('Course material is not available yet. Please check back soon.');
}

// If it's a URL (starts with http), redirect directly
if (filter_var($filePath, FILTER_VALIDATE_URL)) {
    header('Location: ' . $filePath);
    exit;
}

// Otherwise serve local file
$absPath = realpath(__DIR__ . '/../../' . ltrim($filePath, '/'));

if (!$absPath || !file_exists($absPath)) {
    http_response_code(404);
    die('File not found on server.');
}

$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course['title']) . '.pdf';
$mime     = mime_content_type($absPath) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($absPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($absPath);
exit;
