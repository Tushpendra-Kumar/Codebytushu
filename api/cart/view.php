<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

Auth::boot();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

$user_id = Auth::id();

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, co.id as course_id, co.title, co.thumbnail_path, co.price 
        FROM cart_items c 
        JOIN courses co ON c.course_id = co.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
    
    $total = 0;
    foreach ($items as $item) {
        $total += (float)$item['price'];
    }
    
    echo json_encode([
        'success' => true, 
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ]);
    
} catch (PDOException $e) {
    error_log("Cart View Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
