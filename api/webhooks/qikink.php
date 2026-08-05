<?php
/**
 * Qikink Webhook Listener
 * Receives order status updates from Qikink (e.g. Shipped, In-Transit, Delivered).
 * Endpoint: /api/webhooks/qikink.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Mailer.php';

// 1. Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 2. Read the JSON Payload
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);

// 3. Setup Logging
$logDir = ROOT_DIR . '/private/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/webhooks.log';
$timestamp = date('Y-m-d H:i:s');
$logEntry = "[$timestamp] QIKINK WEBHOOK:\n" . $rawPayload . "\n----------------------------------------\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// 4. Acknowledge Receipt Immediately to prevent timeouts
http_response_code(200);

if (!$payload) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// 5. Process Payload (Structure based on standard Qikink webhooks)
// Usually Qikink sends the order ID or Order Number, along with the status and tracking details.
// Note: Actual keys may vary based on exact Qikink subscription setup.
$qikink_order_id = $payload['order_id'] ?? $payload['message']['order_id'] ?? null;
$status          = $payload['status'] ?? $payload['message']['status'] ?? null;
$tracking_number = $payload['shipment_tracking_key'] ?? $payload['message']['shipment_tracking_key'] ?? $payload['awb'] ?? null;
$courier_name    = $payload['courier'] ?? $payload['message']['courier'] ?? null;

if (!$qikink_order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing qikink_order_id']);
    exit;
}

try {
    $pdo = db();
    
    // Convert Qikink status to our internal fulfillment_status
    // Qikink statuses typically: Live, Printed, Manifested, In-Transit, Delivered, RTO, Cancelled
    $normalized_status = strtolower($status);
    $our_fulfillment_status = 'processing';
    
    if (in_array($normalized_status, ['shipped', 'in-transit', 'dispatched', 'manifested'])) {
        $our_fulfillment_status = 'shipped';
    } elseif (in_array($normalized_status, ['delivered', 'completed'])) {
        $our_fulfillment_status = 'delivered';
    } elseif (in_array($normalized_status, ['cancelled', 'rto initiated', 'rto delivered'])) {
        $our_fulfillment_status = 'cancelled';
    }

    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET 
            qikink_status = ?, 
            fulfillment_status = ?,
            awb_number = COALESCE(?, awb_number),
            courier_name = COALESCE(?, courier_name),
            tracking_number = COALESCE(?, tracking_number)
        WHERE qikink_order_id = ?
    ");
    
    $updateStmt->execute([
        $status, 
        $our_fulfillment_status,
        $tracking_number,
        $courier_name,
        $tracking_number,
        $qikink_order_id
    ]);
    
    // Phase 5: Send shipped email when Qikink notifies dispatch
    if ($our_fulfillment_status === 'shipped') {
        try {
            $orderRow = $pdo->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.qikink_order_id = ?");
            $orderRow->execute([$qikink_order_id]);
            $fullOrder = $orderRow->fetch(PDO::FETCH_ASSOC);
            if ($fullOrder) { (new Mailer())->sendOrderShipped($fullOrder); }
        } catch (Exception $me) { error_log('Webhook email error: ' . $me->getMessage()); }
    }
    if ($our_fulfillment_status === 'delivered') {
        try {
            $orderRow = $pdo->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.qikink_order_id = ?");
            $orderRow->execute([$qikink_order_id]);
            $fullOrder = $orderRow->fetch(PDO::FETCH_ASSOC);
            if ($fullOrder) { (new Mailer())->sendOrderDelivered($fullOrder); }
        } catch (Exception $me) { error_log('Webhook email error: ' . $me->getMessage()); }
    }
    
    echo json_encode(['success' => true, 'message' => 'Order updated']);
} catch (Exception $e) {
    error_log("Webhook DB Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
