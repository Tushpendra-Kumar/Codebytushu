<?php
/**
 * CodeByTushu — Qikink API Wrapper
 * Handles authentication, dynamic order creation, and tracking via Qikink Open API.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

class QikinkAPI
{
    private string $clientId;
    private string $clientSecret;
    private string $apiUrl;

    public function __construct()
    {
        $this->clientId     = QIKINK_CLIENT_ID;
        $this->clientSecret = QIKINK_SECRET;
        $this->apiUrl       = rtrim(QIKINK_API_URL, '/');
    }

    /**
     * Check if Qikink integration credentials are still placeholder/not configured.
     * Returns true if integration is pending (credentials not yet received).
     */
    public function isIntegrationPending(): bool
    {
        $placeholders = ['sandbox_client_id_here', 'sandbox_secret_here', '', 'YOUR_CLIENT_ID', 'YOUR_SECRET'];
        return in_array($this->clientId, $placeholders) || in_array($this->clientSecret, $placeholders);
    }

    /**
     * Get Access Token (required for subsequent requests).
     * @return string|null The token, or null on failure.
     */
    private function getAccessToken(): ?string
    {
        $ch = curl_init($this->apiUrl . '/token');
        $payload = http_build_query([
            'ClientId'      => $this->clientId,      // Qikink uses 'ClientId' (capital C, I)
            'client_secret' => $this->clientSecret   // Qikink uses 'client_secret'
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Hostinger compatibility
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            error_log("Qikink API Error: Failed to reach token endpoint.");
            return null;
        }

        $data = json_decode($response, true);
        
        // Qikink returns 'Accesstoken' (capital A, T)
        return $data['Accesstoken'] ?? $data['access_token'] ?? $data['token'] ?? null;
    }

    /**
     * Internal helper to make an API request with the token.
     */
    private function request(string $endpoint, array $payload): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Authentication with print partner failed.'];
        }

        $url = $this->apiUrl . $endpoint;
        $ch = curl_init($url);
        
        $jsonPayload = json_encode($payload);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'ClientId: ' . $this->clientId,
            'Accesstoken: ' . $token
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) {
            return ['success' => false, 'message' => 'No response from print partner.'];
        }

        $data = json_decode($response, true);
        
        // Qikink usually returns 200 or 201 on success.
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        if (!$success) {
            error_log("Qikink API Error ($httpCode): " . $response);
        }

        return [
            'success'  => $success,
            'http_code'=> $httpCode,
            'data'     => $data,
            'raw'      => $response
        ];
    }

    /**
     * Push an order to Qikink using Dynamic Design Injection.
     * 
     * @param array $order Must contain: order_number, total_order_value, shipping_address
     * @param array $items Array of items with: qikink_base_sku, quantity, price, design_url, mockup_url
     * @return array ['success' => bool, 'message' => string, 'qikink_order_id' => string]
     */
    public function createOrder(array $order, array $items): array
    {
        // ── Integration Pending Guard ─────────────────────────────────────
        if ($this->isIntegrationPending()) {
            return [
                'success'          => false,
                'pending'          => true,
                'message'          => 'Print partner integration is currently being finalized. Your order has been received and payment verified. Production will begin automatically once integration is complete.',
                'qikink_order_id'  => null,
            ];
        }
        // ─────────────────────────────────────────────────────────────────

        $lineItems = [];
        
        foreach ($items as $item) {
            // Build designs array (Qikink official format)
            $designs = [[
                'placement'  => 'Front',
                'design_url' => $item['design_url'],
                'mockup_url' => $item['mockup_url'] ?? null,
            ]];
            // Add back design if provided
            if (!empty($item['design_back_url'])) {
                $designs[] = [
                    'placement'  => 'Back',
                    'design_url' => $item['design_back_url'],
                    'mockup_url' => $item['mockup_back_url'] ?? null,
                ];
            }

            $lineItems[] = [
                'sku'      => $item['qikink_base_sku'],
                'quantity' => (string)$item['quantity'],
                'price'    => (string)$item['price'],
                'designs'  => $designs,   // ← correct nested structure per Qikink docs
            ];
        }

        $payload = [
            'order_number'      => (string)$order['order_number'],
            'qikink_shipping'   => '1',      // Qikink will ship it
            'gateway'           => 'Prepaid', // Admin verifies payment first, so to Qikink it's prepaid
            'total_order_value' => (string)$order['total_order_value'],
            'line_items'        => $lineItems,
            'shipping_address'  => [
                'first_name'   => $order['shipping_address']['first_name'],
                'last_name'    => $order['shipping_address']['last_name'] ?? '.',
                'address1'     => $order['shipping_address']['address1'],
                'address2'     => $order['shipping_address']['address2'] ?? '',
                'phone'        => $order['shipping_address']['phone'],
                'email'        => $order['shipping_address']['email'] ?? 'customer@codebytushu.com',
                'city'         => $order['shipping_address']['city'],
                'zip'          => $order['shipping_address']['zip'],
                'province'     => $order['shipping_address']['province'],
                'country_code' => 'IN'
            ]
        ];

        $res = $this->request('/order/create', $payload);

        if ($res['success']) {
            // Qikink typically returns order id in the response
            return [
                'success' => true,
                'message' => 'Order successfully forwarded to print partner.',
                'qikink_order_id' => $res['data']['order_id'] ?? null,
                'raw_response' => $res['data']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to push order to print partner: ' . ($res['data']['message'] ?? 'Unknown error')
            ];
        }
    }
}
