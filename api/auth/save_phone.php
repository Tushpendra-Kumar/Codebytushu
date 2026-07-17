<?php
/**
 * CodeByTushu — Save Phone Number Endpoint (AJAX)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Auth.php';

Auth::boot();

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$phone = sanitize(post('phone_number'));

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

try {
    $pdo = db();
    $user = Auth::user(true);
    
    $stmt = $pdo->prepare('UPDATE users SET phone_number=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$phone, $user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Phone number verified and saved successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
