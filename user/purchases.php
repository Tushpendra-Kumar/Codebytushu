<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireLogin();

$pdo = db();
$user_id = $_SESSION['user_id'];

// Fetch user orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchase History - CodeByTushu</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .table-wrapper { background: #111; border-radius: 8px; overflow: hidden; border: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid #222; }
        th { background: #1a1a1a; color: #ffc400; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        h1 { color: #fff; margin-bottom: 20px; }
        .empty-state { padding: 40px; text-align: center; color: #aaa; }
        .status { padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
        .status.pending { background: #ffc107; color: #000; }
        .status.verified { background: #28a745; color: #fff; }
        .status.rejected { background: #dc3545; color: #fff; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="dashboard-container">
        <h1><i class="fas fa-history"></i> Purchase History</h1>
        
        <div class="table-wrapper">
            <?php if (empty($orders)): ?>
                <div class="empty-state">You haven't made any purchases yet.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_number']) ?></td>
                                <td><?= date('M j, Y h:i A', strtotime($order['created_at'])) ?></td>
                                <td style="color:#ffc400; font-weight:bold;">₹<?= number_format($order['total_amount'], 2) ?></td>
                                <td style="text-transform: uppercase;"><?= htmlspecialchars($order['payment_method']) ?></td>
                                <td><span class="status <?= $order['payment_status'] ?>"><?= htmlspecialchars($order['payment_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
