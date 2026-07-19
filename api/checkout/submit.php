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

$user_id = $_SESSION['user_id'];

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    // Fetch cart items
    $stmt = $pdo->prepare("
        SELECT c.course_id, co.price 
        FROM cart_items c 
        JOIN courses co ON c.course_id = co.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'Your cart is empty.']);
        $pdo->rollBack();
        exit;
    }
    
    $total = 0;
    foreach ($items as $item) {
        $total += (float)$item['price'];
    }
    
    // Generate Order Number
    $order_number = 'ORD-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    
    // Create Order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status) VALUES (?, ?, ?, 'upi', 'verified')");
    $stmt->execute([$user_id, $order_number, $total]);
    $order_id = $pdo->lastInsertId();
    
    // Create Order Items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, course_id, price) VALUES (?, ?, ?)");
    foreach ($items as $item) {
        $stmt->execute([$order_id, $item['course_id'], $item['price']]);
    }
    
    // Clear Cart
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'order_number' => $order_number]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Checkout Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred during checkout.']);
}
