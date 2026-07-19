<?php
/**
 * CodeByTushu - Secure Course PDF Download Controller
 * 
 * Verifies that the user has an active, paid enrollment for the course
 * before streaming the PDF from a private directory.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();
Auth::requireLogin();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    die("Invalid course ID.");
}

try {
    $pdo = db();
    
    // 1. Verify Enrollment
    $stmt = $pdo->prepare("
        SELECT payment_status 
        FROM course_enrollments 
        WHERE course_id = ? AND user_id = ?
    ");
    $stmt->execute([$course_id, $user_id]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment || ($enrollment['payment_status'] !== 'paid' && $enrollment['payment_status'] !== 'free')) {
        http_response_code(403);
        die("You do not have permission to download this course. Please purchase it first or wait for admin verification.");
    }
    
    // 2. Fetch Course File Path
    $stmt = $pdo->prepare("SELECT title, download_file_path FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course || empty($course['download_file_path'])) {
        http_response_code(404);
        die("Download file not found for this course.");
    }
    
    // 3. Serve the File securely
    $filePath = ROOT_DIR . '/' . ltrim($course['download_file_path'], '/');
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die("The requested file does not exist on the server.");
    }
    
    $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $course['title']) . '.pdf';
    
    // Disable caching
    header("Expires: 0");
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache");
    
    // Set PDF headers
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Content-Length: " . filesize($filePath));
    
    // Output the file
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    error_log("Download Error: " . $e->getMessage());
    http_response_code(500);
    die("A server error occurred while processing your download.");
}
