<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Mailer.php';

Auth::boot();
Auth::requireAdmin();

header('Content-Type: application/json');

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($method === 'GET') {
        if ($action === 'list') {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            $statusFilter = $_GET['status'] ?? 'all';

            $where = ["o.order_type = 'store'"];
            $params = [];
            
            if ($statusFilter !== 'all') {
                $where[] = 'o.fulfillment_status = ?';
                $params[] = $statusFilter;
            }
            
            $whereClause = implode(' AND ', $where);

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereClause");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT o.*, u.full_name, u.email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE $whereClause
                ORDER BY o.created_at DESC
                LIMIT $perPage OFFSET $offset
            ");
            $stmt->execute($params);
            $orders = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $perPage)
                ]
            ]);
            exit;
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT o.*, u.full_name, u.email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ? AND o.order_type = 'store'
            ");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if ($order) {
                // Get items
                $itemsStmt = $pdo->prepare("
                    SELECT oi.*, sp.title, sp.thumbnail
                    FROM order_items oi
                    LEFT JOIN store_products sp ON oi.product_id = sp.id
                    WHERE oi.order_id = ?
                ");
                $itemsStmt->execute([$id]);
                $order['items'] = $itemsStmt->fetchAll();
                
                echo json_encode(['success' => true, 'data' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
            exit;
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? $action;

        if ($action === 'update_fulfillment') {
            $id = (int)($input['id'] ?? 0);
            $fulfillment_status = trim($input['fulfillment_status'] ?? '');
            $tracking_number    = trim($input['tracking_number'] ?? '');
            $admin_notes        = trim($input['admin_notes'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE orders 
                SET fulfillment_status=?, tracking_number=?, admin_notes=?
                WHERE id=? AND order_type='store'
            ");
            $stmt->execute([$fulfillment_status, $tracking_number, $admin_notes, $id]);
            
            // Phase 5: Send shipped / delivered email
            try {
                $orderRow = $pdo->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
                $orderRow->execute([$id]);
                $fullOrder = $orderRow->fetch(PDO::FETCH_ASSOC);
                if ($fullOrder) {
                    $mailer = new Mailer();
                    if ($fulfillment_status === 'shipped')   $mailer->sendOrderShipped($fullOrder);
                    if ($fulfillment_status === 'delivered') $mailer->sendOrderDelivered($fullOrder);
                }
            } catch (Exception $me) { error_log('Email error: ' . $me->getMessage()); }
            
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
            exit;
        } elseif ($action === 'verify_payment') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='verified' WHERE id=? AND order_type='store'");
            $stmt->execute([$id]);
            
            // Phase 5: Send payment verified email to customer
            try {
                $orderRow = $pdo->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
                $orderRow->execute([$id]);
                $fullOrder = $orderRow->fetch(PDO::FETCH_ASSOC);
                if ($fullOrder) { (new Mailer())->sendPaymentVerified($fullOrder); }
            } catch (Exception $me) { error_log('Email error: ' . $me->getMessage()); }
            
            // Phase 4: Automatically Push to Print Partner upon Payment Verification
            $pushRes = pushOrderToQikink($pdo, $id);
            
            if ($pushRes['success']) {
                echo json_encode(['success' => true, 'message' => 'Payment verified and order automatically pushed to Qikink! ID: ' . $pushRes['qikink_order_id']]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Payment verified, but Qikink push failed: ' . $pushRes['message']]);
            }
            exit;
        } elseif ($action === 'reject_payment') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='rejected' WHERE id=? AND order_type='store'");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Payment rejected']);
            exit;
        } elseif ($action === 'push_to_qikink') {
            $id = (int)($input['id'] ?? 0);
            $res = pushOrderToQikink($pdo, $id);
            echo json_encode($res);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Helper function to push order to Qikink
 */
function pushOrderToQikink($pdo, $id) {
    // Get order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND order_type='store'");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    
    if ($order['qikink_order_id']) {
        return ['success' => false, 'message' => 'Order already pushed to Qikink'];
    }
    
    // Get items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, sp.qikink_base_sku, sp.print_file_path, sp.thumbnail
        FROM order_items oi
        JOIN store_products sp ON oi.product_id = sp.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$id]);
    $itemsRaw = $itemsStmt->fetchAll();
    
    // Format for Qikink
    $orderFormatted = [
        'order_number' => $order['order_number'],
        'total_order_value' => $order['total_amount'],
        'shipping_address' => [
            'first_name' => explode(' ', $order['shipping_name'] ?? 'Customer')[0],
            'last_name' => explode(' ', $order['shipping_name'] ?? 'Customer')[1] ?? '.',
            'address1' => $order['shipping_address'],
            'phone' => $order['shipping_phone'],
            'city' => $order['shipping_city'],
            'zip' => $order['shipping_pincode'],
            'province' => $order['shipping_state'],
        ]
    ];
    
    $itemsFormatted = [];
    foreach ($itemsRaw as $item) {
        if (empty($item['qikink_base_sku'])) {
            return ['success' => false, 'message' => 'Product missing Qikink SKU: ' . $item['product_name']];
        }
        $itemsFormatted[] = [
            'qikink_base_sku' => $item['qikink_base_sku'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'design_url' => $item['print_file_path'] ?: $item['thumbnail'], // fallback
            'mockup_url' => $item['thumbnail']
        ];
    }
    
    require_once __DIR__ . '/../../classes/QikinkAPI.php';
    $qikink = new QikinkAPI();
    $res = $qikink->createOrder($orderFormatted, $itemsFormatted);
    
    if ($res['success']) {
        $qikink_order_id = $res['qikink_order_id'] ?? 'unknown';
        $updateStmt = $pdo->prepare("
            UPDATE orders 
            SET qikink_order_id = ?, fulfillment_status = 'processing' 
            WHERE id = ?
        ");
        $updateStmt->execute([$qikink_order_id, $id]);
        return ['success' => true, 'message' => 'Pushed to Qikink.', 'qikink_order_id' => $qikink_order_id];
    } else {
        return ['success' => false, 'message' => $res['message']];
    }
}
