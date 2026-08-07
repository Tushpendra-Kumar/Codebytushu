<?php
/**
 * Qikink Sandbox API Connection Test
 * Run this ONCE to verify credentials work.
 * DELETE or move this file after testing.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/classes/QikinkAPI.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== Qikink Sandbox API Test ===\n\n";
echo "Client ID : " . QIKINK_CLIENT_ID . "\n";
echo "API URL   : " . QIKINK_API_URL . "\n\n";

// Test 1: Token Generation
echo "--- Test 1: Token Generation ---\n";
$ch = curl_init(QIKINK_API_URL . '/token');
$payload = http_build_query([
    'ClientId'      => QIKINK_CLIENT_ID,
    'client_secret' => QIKINK_SECRET,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

$data = json_decode($response, true);
$token = $data['Accesstoken'] ?? $data['access_token'] ?? $data['token'] ?? null;

if ($token) {
    echo "✅ TOKEN RECEIVED: " . substr($token, 0, 20) . "...\n\n";

    // Test 2: Create Sandbox Order
    echo "--- Test 2: Create Sandbox Test Order ---\n";
    $orderPayload = json_encode([
        'order_number'      => 'CBT20260807001',   // Max 15 chars
        'qikink_shipping'   => '1',
        'gateway'           => 'Prepaid',
        'total_order_value' => '500',
        'line_items'        => [[
            'search_from_my_products' => '1',  // 1 = use Qikink product catalog
            'sku'                     => 'RN-WHT-S',
            'quantity'                => '1',
            'price'                   => '500',
        ]],
        'shipping_address'  => [
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
        ]
    ]);

    $ch2 = curl_init(QIKINK_API_URL . '/order/create');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $orderPayload);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'ClientId: ' . QIKINK_CLIENT_ID,
        'Accesstoken: ' . $token,
    ]);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

    $orderResponse = curl_exec($ch2);
    $orderCode     = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    echo "HTTP Status: $orderCode\n";
    echo "Response: $orderResponse\n\n";

    $orderData = json_decode($orderResponse, true);
    if ($orderCode >= 200 && $orderCode < 300) {
        echo "✅ SANDBOX ORDER CREATED SUCCESSFULLY!\n";
        echo "Qikink Order ID: " . ($orderData['order_id'] ?? json_encode($orderData)) . "\n";
    } else {
        echo "⚠️ Order creation response — check above for details.\n";
    }

} else {
    echo "❌ TOKEN FAILED. Check Client ID and Client Secret.\n";
}

echo "\n=== Test Complete ===\n";
echo "IMPORTANT: Delete this file (qikink-test.php) after testing!\n";
