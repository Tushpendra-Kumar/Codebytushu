<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

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
            $tracking_number = trim($input['tracking_number'] ?? '');
            $admin_notes = trim($input['admin_notes'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE orders 
                SET fulfillment_status=?, tracking_number=?, admin_notes=?
                WHERE id=? AND order_type='store'
            ");
            $stmt->execute([$fulfillment_status, $tracking_number, $admin_notes, $id]);
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
            exit;
        } elseif ($action === 'verify_payment') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='verified' WHERE id=? AND order_type='store'");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Payment verified']);
            exit;
        } elseif ($action === 'reject_payment') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE orders SET payment_status='rejected' WHERE id=? AND order_type='store'");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Payment rejected']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
