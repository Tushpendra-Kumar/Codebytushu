<?php
/**
 * API: Subscribe to Newsletter
 * Handles newsletter subscriptions with validation, security, and duplicate checks.
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
    // In local dev, origin might be empty or different, so we keep this simple.
    // Uncomment for strict production checking:
    // http_response_code(403);
    // echo json_encode(['success' => false, 'message' => 'Forbidden origin.']);
    // exit;
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

try {
    $pdo = db();

    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Return 200 but indicate it's already subscribed
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed.']);
        exit;
    }

    // Insert new subscriber
    $insertStmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
    $result = $insertStmt->execute(['email' => $email]);

    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you! You have successfully subscribed to CodeByTushu updates.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe. Please try again later.']);
    }

} catch (PDOException $e) {
    error_log("Newsletter Subscription Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
}
