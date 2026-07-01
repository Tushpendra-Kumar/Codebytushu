<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
$razorpay_order_id = $input['razorpay_order_id'] ?? '';
$razorpay_signature = $input['razorpay_signature'] ?? '';

if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing payment parameters.']);
    exit;
}

$keyId = RAZORPAY_KEY_ID;
$keySecret = RAZORPAY_KEY_SECRET;

try {
    $api = new Api($keyId, $keySecret);
    $attributes = [
        'razorpay_order_id'   => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature'  => $razorpay_signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    echo json_encode(['success' => true, 'message' => 'Payment verified successfully.']);
} catch (SignatureVerificationError $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payment signature.']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Verification failed: ' . $e->getMessage()]);
}
