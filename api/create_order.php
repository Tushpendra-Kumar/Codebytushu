<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = isset($input['amount']) ? (int)$input['amount'] : 0; // Amount in INR

if ($amount < 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Minimum amount is ₹100.']);
    exit;
}

$keyId = RAZORPAY_KEY_ID;
$keySecret = RAZORPAY_KEY_SECRET;

if (empty($keyId) || empty($keySecret)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Razorpay keys not configured.']);
    exit;
}

try {
    $api = new Api($keyId, $keySecret);
    $orderData = [
        'receipt'         => 'rcptid_' . time() . '_' . rand(1000, 9999),
        'amount'          => $amount * 100, // Razorpay takes amount in paise
        'currency'        => 'INR',
        'payment_capture' => 1 // auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);

    echo json_encode([
        'success'  => true,
        'order_id' => $razorpayOrder['id'],
        'amount'   => $razorpayOrder['amount'],
        'currency' => $razorpayOrder['currency'],
        'key_id'   => $keyId
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create order: ' . $e->getMessage()]);
}
