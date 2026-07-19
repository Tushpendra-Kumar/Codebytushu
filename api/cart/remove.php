<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

Auth::boot();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : (isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0);
$user_id = $_SESSION['user_id'];

try {
    $pdo = db();
    // Allow deleting by either course_id (if passed from toggle) or cart_id (if passed from cart page)
    if (isset($_POST['course_id'])) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE course_id = ? AND user_id = ?");
        $stmt->execute([$course_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$course_id, $user_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
    
} catch (PDOException $e) {
    error_log("Cart Remove Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
