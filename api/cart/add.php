<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

Auth::boot();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to add items to your cart.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid course ID.']);
    exit;
}

try {
    $pdo = db();
    
    // Check if already purchased/enrolled
    $stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND user_id = ?");
    $stmt->execute([$course_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'You already own this course.']);
        exit;
    }
    
    // Check if already in cart
    $stmt = $pdo->prepare("SELECT id FROM cart_items WHERE course_id = ? AND user_id = ?");
    $stmt->execute([$course_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Course is already in your cart.']);
        exit;
    }
    
    // Insert into cart
    $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $course_id]);
    
    echo json_encode(['success' => true, 'message' => 'Course added to cart!']);
    
} catch (PDOException $e) {
    error_log("Cart Add Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
