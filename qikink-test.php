<?php
/**
 * Qikink Sandbox API Connection Test — v4 (Final)
 * Uses correct designs structure per Qikink support confirmation.
 * DELETE this file after successful test!
 */

require_once __DIR__ . '/config/app.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== Qikink Sandbox API Test (v4 - Final) ===\n\n";
echo "Client ID : " . QIKINK_CLIENT_ID . "\n";
echo "API URL   : " . QIKINK_API_URL . "\n\n";

// ── Test 1: Token ─────────────────────────────────────────────
echo "--- Test 1: Token Generation ---\n";
$ch = curl_init(QIKINK_API_URL . '/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'ClientId'      => QIKINK_CLIENT_ID,
        'client_secret' => QIKINK_SECRET,
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
$data  = json_decode($response, true);
$token = $data['Accesstoken'] ?? $data['access_token'] ?? null;

if (!$token) {
    echo "❌ TOKEN FAILED.\nResponse: $response\n";
    exit;
}
echo "✅ TOKEN RECEIVED: " . substr($token, 0, 25) . "...\n\n";

// ── Test 2: Create Sandbox Order ──────────────────────────────
echo "--- Test 2: Create Sandbox Test Order ---\n";

$orderPayload = json_encode([
    'order_number'      => 'CBT20260809',    // ≤15 chars
    'qikink_shipping'   => '1',
    'gateway'           => 'Prepaid',
    'total_order_value' => '500',
    'line_items'        => [[
        'search_from_my_products' => '0',        // 0 = dynamic design injection
        'sku'                     => 'MVnHs-Bk-S', // Male V Neck T-Shirt Black S
        'quantity'                => '1',
        'price'                   => '500',
        'designs'                 => [[
            'design_code'   => 'CBT001',          // unique design identifier
            'placement'     => 'Front',
            'height_inches' => '7.61',
            'width_inches'  => '7.61',
            'design_url'    => 'https://codebytushu.com/android-chrome-512x512.png',
        ]],
    ]],
    'shipping_address' => [
        'first_name'   => 'Tushpendra',
        'last_name'    => 'Kumar',
        'address1'     => '123 Test Street Sector 5',
        'address2'     => '',
        'phone'        => '9999999999',
        'email'        => 'tushpendrakumar@gmail.com',
        'city'         => 'Delhi',
        'zip'          => '110001',
        'province'     => 'Delhi',
        'country_code' => 'IN',
    ],
]);

$ch2 = curl_init(QIKINK_API_URL . '/order/create');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $orderPayload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'ClientId: '    . QIKINK_CLIENT_ID,
        'Accesstoken: ' . $token,
    ],
    CURLOPT_SSL_VERIFYPEER => false,
]);
$orderResponse = curl_exec($ch2);
$orderCode     = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Status: $orderCode\n";
echo "Response: " . json_encode(json_decode($orderResponse), JSON_PRETTY_PRINT) . "\n\n";

$orderData = json_decode($orderResponse, true);
if (isset($orderData['order_id']) || (isset($orderData['status_code']) && $orderData['status_code'] == '200')) {
    echo "✅ SANDBOX ORDER CREATED SUCCESSFULLY!\n";
    echo "Qikink Order ID: " . ($orderData['order_id'] ?? 'See response above') . "\n";
} else {
    echo "⚠️ See response above for details.\n";
}

echo "\n=== Test Complete ===\n";
echo "IMPORTANT: Delete this file (qikink-test.php) after testing!\n";
