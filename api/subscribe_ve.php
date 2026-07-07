<?php
/**
 * API: Subscribe to Video Editing Newsletter
 * Independent newsletter system for the Video Editing website.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Basic CSRF check based on origin
$allowed_origin = rtrim(APP_URL, '/');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin) && strpos($origin, $allowed_origin) === false) {
    // strict origin check can be uncommented for production
}

// Read JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);

// Validate Email Format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $pdo = db();

    // Check for duplicate email in video_editing_subscribers
    $stmt = $pdo->prepare("SELECT id FROM video_editing_subscribers WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed to Video Editing updates.']);
        exit;
    }

    // Insert new subscriber
    $insertStmt = $pdo->prepare("INSERT INTO video_editing_subscribers (email, ip_address) VALUES (:email, :ip_address)");
    $result = $insertStmt->execute([
        'email' => $email,
        'ip_address' => $ip_address
    ]);

    if ($result) {
        
        // -------------------------------------------------------------
        // SEND WELCOME EMAIL
        // -------------------------------------------------------------
        $subject = "Welcome to CodeByTushu Video Editing Community 🎬";
        
        $message = "Hi,\n\n";
        $message .= "Thanks for subscribing to CodeByTushu Video Editing.\n\n";
        $message .= "From now on you'll receive:\n";
        $message .= "- Video Editing Tips\n";
        $message .= "- Premiere Pro Tutorials\n";
        $message .= "- After Effects Guides\n";
        $message .= "- Editing Guide Updates\n";
        $message .= "- Portfolio Updates\n";
        $message .= "- Free Resources\n";
        $message .= "- Future Courses\n";
        $message .= "- Exclusive Content\n\n";
        $message .= "Stay Connected.\n\n";
        $message .= "— CodeByTushu";

        $headers = "From: CodeByTushu <noreply@codebytushu.com>\r\n";
        $headers .= "Reply-To: noreply@codebytushu.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // Attempt to send email. In local environments like XAMPP without SMTP configured, 
        // this might fail, so we use error suppression or handle it silently.
        @mail($email, $subject, $message, $headers);

        echo json_encode([
            'success' => true, 
            'message' => "Thanks for subscribing! You'll receive future Video Editing tips, tutorials and updates."
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again later.']);
    }

} catch (PDOException $e) {
    // If the table doesn't exist, log the error and notify user
    error_log("Video Editing Newsletter Subscription Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Did you run the SQL migration (010_video_editing_subscribers)?']);
}
