<?php
/**
 * Store Checkout API
 * POST /api/store/checkout.php
 * Creates a store order (with shipping address) in the database
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Auth check
Auth::boot();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in to place an order.']);
    exit;
}

$userId = Auth::id();

// Parse request body
$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

// Validate items
$items = $body['items'] ?? [];
if (empty($items) || !is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit;
}

// Validate shipping
$shipping = $body['shipping'] ?? [];
$required_fields = ['name', 'phone', 'address', 'city', 'state', 'pincode'];
foreach ($required_fields as $field) {
    if (empty($shipping[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Shipping field '{$field}' is required."]);
        exit;
    }
}

// Validate phone and pincode
if (!preg_match('/^\d{10}$/', $shipping['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number. Must be 10 digits.']);
    exit;
}
if (!preg_match('/^\d{6}$/', $shipping['pincode'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid PIN code. Must be 6 digits.']);
    exit;
}

// Validate payment method
$paymentMethod = $body['payment_method'] ?? 'upi';
if (!in_array($paymentMethod, ['upi', 'cod'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment method.']);
    exit;
}

// Require UTR for UPI
$paymentReference = trim($body['payment_reference'] ?? '');
if ($paymentMethod === 'upi' && empty($paymentReference)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'UTR / Transaction ID is required for UPI payment.']);
    exit;
}

// Calculate total
$totalAmount = 0;
foreach ($items as $item) {
    $price = floatval($item['price'] ?? 0);
    $qty   = intval($item['quantity'] ?? 1);
    if ($price < 0 || $qty < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
        exit;
    }
    $totalAmount += $price * $qty;
}

if ($totalAmount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order total must be greater than zero.']);
    exit;
}

// Generate unique order number
function generateOrderNumber(): string {
    return 'CBT-S-' . strtoupper(substr(uniqid('', true), -8)) . '-' . date('Ymd');
}

$pdo = db();

try {
    // Ensure course_id allows NULL (Fix for store products)
    try {
        $pdo->exec("ALTER TABLE `order_items` MODIFY COLUMN `course_id` INT UNSIGNED NULL");
    } catch (\Exception $ex) {
        // Ignore if already null or insufficient permissions
    }

    $pdo->beginTransaction();

    // Determine payment status
    $paymentStatus = ($paymentMethod === 'cod') ? 'pending' : 'pending';

    // Generate unique order number
    do {
        $orderNumber = generateOrderNumber();
        $check = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
        $check->execute([$orderNumber]);
    } while ($check->fetch());

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, order_type, order_number, total_amount,
            payment_method, payment_status,
            shipping_name, shipping_phone, shipping_address,
            shipping_city, shipping_state, shipping_pincode,
            payment_reference, fulfillment_status
        ) VALUES (
            :user_id, 'store', :order_number, :total_amount,
            :payment_method, :payment_status,
            :shipping_name, :shipping_phone, :shipping_address,
            :shipping_city, :shipping_state, :shipping_pincode,
            :payment_reference, 'pending'
        )
    ");

    $stmt->execute([
        ':user_id'           => $userId,
        ':order_number'      => $orderNumber,
        ':total_amount'      => $totalAmount,
        ':payment_method'    => $paymentMethod,
        ':payment_status'    => $paymentStatus,
        ':shipping_name'     => substr(trim($shipping['name']), 0, 255),
        ':shipping_phone'    => $shipping['phone'],
        ':shipping_address'  => substr(trim($shipping['address']), 0, 1000),
        ':shipping_city'     => substr(trim($shipping['city']), 0, 100),
        ':shipping_state'    => substr(trim($shipping['state']), 0, 100),
        ':shipping_pincode'  => $shipping['pincode'],
        ':payment_reference' => $paymentReference ?: null,
    ]);

    $orderId = $pdo->lastInsertId();

    // Insert order items
    // Since this is a store order, course_id is NULL, product_id is the product ID
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, course_id, product_id, product_name, price, quantity)
        VALUES (:order_id, NULL, :product_id, :product_name, :price, :quantity)
    ");

    foreach ($items as $item) {
        $itemStmt->execute([
            ':order_id'     => $orderId,
            ':product_id'   => $item['id'] ?? null,
            ':product_name' => substr(trim($item['title'] ?? 'Unknown Product'), 0, 255),
            ':price'        => floatval($item['price'] ?? 0),
            ':quantity'     => intval($item['quantity'] ?? 1),
        ]);
    }

    $pdo->commit();

    // ── Phase 5: Send Email Notifications ────────────────────────────
    try {
        require_once __DIR__ . '/../../classes/Mailer.php';
        $mailer = new Mailer();
        
        // Fetch full order row for email
        $orderRow = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $orderRow->execute([$orderId]);
        $fullOrder = $orderRow->fetch(PDO::FETCH_ASSOC);
        
        // Format items for email
        $emailItems = array_map(fn($i) => [
            'product_name' => $i['title'] ?? 'Product',
            'quantity'     => intval($i['quantity'] ?? 1),
            'price'        => floatval($i['price'] ?? 0),
        ], $items);
        
        if ($fullOrder) {
            $mailer->sendOrderPlaced($fullOrder, $emailItems);    // Customer
            $mailer->sendAdminNewOrder($fullOrder, $emailItems);  // Admin
        }
    } catch (Exception $mailEx) {
        error_log('Order email failed: ' . $mailEx->getMessage());
        // Don't fail the order if email fails
    }
    // ─────────────────────────────────────────────────────────────────

    echo json_encode([
        'success'        => true,
        'message'        => 'Order placed successfully!',
        'order_number'   => $orderNumber,
        'order_id'       => $orderId,
        'total'          => $totalAmount,
        'payment_method' => $paymentMethod
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Store checkout error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
