<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

Auth::boot();
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : ''; // 'verify' or 'reject'

if (!$order_id || !in_array($action, ['verify', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    // Check order
    $stmt = $pdo->prepare("SELECT user_id, payment_status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found.']);
        $pdo->rollBack();
        exit;
    }
    
    if ($order['payment_status'] !== 'pending') {
        echo json_encode(['success' => false, 'error' => 'Order is already processed.']);
        $pdo->rollBack();
        exit;
    }
    
    $new_status = ($action === 'verify') ? 'verified' : 'rejected';
    
    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    if ($action === 'verify') {
        // Enroll user in courses
        $stmt = $pdo->prepare("SELECT course_id, price FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        $enrollStmt = $pdo->prepare("
            INSERT INTO course_enrollments (course_id, user_id, payment_status, amount_paid) 
            VALUES (?, ?, 'paid', ?) 
            ON DUPLICATE KEY UPDATE payment_status = 'paid'
        ");
        
        foreach ($items as $item) {
            $enrollStmt->execute([$item['course_id'], $order['user_id'], $item['price']]);
            
            // Increment course enrollment count
            $pdo->prepare("UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE id = ?")->execute([$item['course_id']]);
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Order successfully {$new_status}."]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin Payment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
