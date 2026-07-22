<?php
/**
 * API: /api/checkout/submit_single.php
 * Creates an order for a SINGLE course and marks it verified instantly.
 * Used by the inline UPI payment flow on the Courses page.
 */
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

$user_id   = $_SESSION['user_id'];
$course_id = intval($_POST['course_id'] ?? 0);

if (!$course_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid course.']);
    exit;
}

try {
    $pdo = db();

    // Get course price
    $stmt = $pdo->prepare("SELECT id, price FROM courses WHERE id = ? AND is_published = 1");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if (!$course) {
        echo json_encode(['success' => false, 'error' => 'Course not found.']);
        exit;
    }

    // Check already purchased
    $check = $pdo->prepare("
        SELECT oi.id FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.course_id = ? AND o.payment_status = 'verified'
    ");
    $check->execute([$user_id, $course_id]);
    if ($check->fetch()) {
        // Already purchased – just allow download
        echo json_encode(['success' => true, 'already_owned' => true]);
        exit;
    }

    $pdo->beginTransaction();

    $order_number = 'ORD-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    $total        = (float) $course['price'];

    // Create order (instantly verified – trust-based UPI flow)
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status)
        VALUES (?, ?, ?, 'upi', 'verified')
    ");
    $stmt->execute([$user_id, $order_number, $total]);
    $order_id = $pdo->lastInsertId();

    // Create order item
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, course_id, price) VALUES (?, ?, ?)");
    $stmt->execute([$order_id, $course_id, $total]);

    $pdo->commit();

    echo json_encode(['success' => true, 'order_number' => $order_number]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("submit_single.php Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
