<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireLogin();

$order_number = isset($_GET['order']) ? $_GET['order'] : '';
$user_id = $_SESSION['user_id'];
$pdo = db();

// Fetch order details and items
$stmt = $pdo->prepare("
    SELECT o.id, o.order_number, o.total_amount, o.payment_status 
    FROM orders o 
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $user_id]);
$order = $stmt->fetch();

$items = [];
if ($order) {
    $itemStmt = $pdo->prepare("
        SELECT c.title, c.thumbnail_path, c.download_file_path 
        FROM order_items oi
        JOIN courses c ON oi.course_id = c.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$order['id']]);
    $items = $itemStmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - CodeByTushu</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container { max-width: 600px; margin: 50px auto; padding: 40px; background: #111; border-radius: 12px; border: 1px solid #333; text-align: center; }
        .icon { font-size: 5rem; color: #28a745; margin-bottom: 20px; }
        h1 { color: #fff; margin-bottom: 10px; }
        p { color: #aaa; line-height: 1.6; margin-bottom: 20px; font-size: 1.1rem; }
        .order-number { font-size: 1.2rem; color: #ffc400; font-weight: bold; background: #222; padding: 10px 20px; border-radius: 6px; display: inline-block; margin-bottom: 30px; }
        
        .download-list { margin-top: 30px; text-align: left; }
        .download-item { background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .download-info { display: flex; align-items: center; gap: 15px; }
        .download-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
        .download-title { color: #fff; font-weight: bold; font-size: 1.1rem; }
        
        .btn-dl { background: #ffc400; color: #000; padding: 10px 20px; border-radius: 6px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-dl:hover { background: #e6b000; }
        .btn-dl.disabled { background: #555; color: #888; cursor: not-allowed; pointer-events: none; }
        
        .btn-dash { background: transparent; border: 1px solid #ffc400; color: #ffc400; padding: 10px 25px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-block; margin-top: 20px; transition: 0.3s; }
        .btn-dash:hover { background: rgba(255, 196, 0, 0.1); }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="success-container">
        <i class="fas fa-check-circle icon"></i>
        <h1>Thank You So Much!</h1>
        <p>Your payment has been successfully processed and your course access is now instantly unlocked.</p>
        
        <div class="order-number">Order ID: <?= htmlspecialchars($order_number) ?></div>
        
        <?php if (!empty($items)): ?>
            <div class="download-list">
                <h3 style="color:#ffc400; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">Your Downloads</h3>
                <?php foreach ($items as $item): ?>
                    <div class="download-item">
                        <div class="download-info">
                            <img src="<?= htmlspecialchars($item['thumbnail_path'] ?: '/image1/default-course.jpg') ?>" class="download-img" alt="Course">
                            <div class="download-title"><?= htmlspecialchars($item['title']) ?></div>
                        </div>
                        <?php if ($item['download_file_path']): ?>
                            <a href="<?= htmlspecialchars($item['download_file_path']) ?>" target="_blank" class="btn-dl">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn-dl disabled" title="File not uploaded yet">
                                <i class="fas fa-clock"></i> Coming Soon
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#ff6b6b;">No items found for this order.</p>
        <?php endif; ?>
        
        <a href="/user/purchases.php" class="btn-dash">Go to My Courses Dashboard</a>
    </div>

</body>
</html>
