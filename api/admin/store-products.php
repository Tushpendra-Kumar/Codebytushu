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
            $search = $_GET['search'] ?? '';

            $where = ['1=1'];
            $params = [];
            if ($search !== '') {
                $where[] = '(title LIKE ? OR description LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $whereClause = implode(' AND ', $where);

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM store_products WHERE $whereClause");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM store_products WHERE $whereClause ORDER BY sort_order ASC, created_at DESC LIMIT $perPage OFFSET $offset");
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            // decode json fields
            foreach ($products as &$p) {
                $p['images'] = $p['images'] ? json_decode($p['images'], true) : [];
                $p['features'] = $p['features'] ? json_decode($p['features'], true) : [];
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $perPage)
                ]
            ]);
            exit;
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM store_products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            if ($product) {
                $product['images'] = $product['images'] ? json_decode($product['images'], true) : [];
                $product['features'] = $product['features'] ? json_decode($product['features'], true) : [];
                echo json_encode(['success' => true, 'data' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
            exit;
        }
    } elseif ($method === 'POST') {
        // Note: CSRF is handled by the admin front-end via _cbt_csrf header if applicable
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? $action;

        if ($action === 'create' || $action === 'update') {
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $price = (float)($input['price'] ?? 0);
            $category = trim($input['category'] ?? '');
            $stock_status = trim($input['stock_status'] ?? 'in-stock');
            $thumbnail = trim($input['thumbnail'] ?? '');
            $images = json_encode($input['images'] ?? []);
            $features = json_encode($input['features'] ?? []);
            $is_active = (int)($input['is_active'] ?? 1);
            $is_new_arrival = (int)($input['is_new_arrival'] ?? 0);
            $sort_order = (int)($input['sort_order'] ?? 0);
            $qikink_base_sku = trim($input['qikink_base_sku'] ?? '');
            $print_file_path = trim($input['print_file_path'] ?? '');

            if (empty($title)) throw new Exception('Title is required');
            if ($price <= 0) throw new Exception('Price must be positive');
            if (empty($category)) throw new Exception('Category is required');

            if ($action === 'create') {
                $stmt = $pdo->prepare("
                    INSERT INTO store_products 
                    (title, description, price, category, stock_status, thumbnail, images, features, is_active, is_new_arrival, sort_order, qikink_base_sku, print_file_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $price, $category, $stock_status, $thumbnail, $images, $features, $is_active, $is_new_arrival, $sort_order, $qikink_base_sku, $print_file_path]);
                echo json_encode(['success' => true, 'message' => 'Product created successfully']);
            } else {
                $id = (int)($input['id'] ?? 0);
                $stmt = $pdo->prepare("
                    UPDATE store_products 
                    SET title=?, description=?, price=?, category=?, stock_status=?, thumbnail=?, images=?, features=?, is_active=?, is_new_arrival=?, sort_order=?, qikink_base_sku=?, print_file_path=?
                    WHERE id=?
                ");
                $stmt->execute([$title, $description, $price, $category, $stock_status, $thumbnail, $images, $features, $is_active, $is_new_arrival, $sort_order, $qikink_base_sku, $print_file_path, $id]);
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            }
            exit;
        } elseif ($action === 'toggle_active') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE store_products SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Active status toggled']);
            exit;
        } elseif ($action === 'toggle_stock') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE store_products SET stock_status = IF(stock_status='in-stock', 'out-of-stock', 'in-stock') WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Stock status toggled']);
            exit;
        } elseif ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE store_products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Product deleted (soft)']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
