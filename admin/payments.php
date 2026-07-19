<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireAdmin();

$pdo = db();

// Fetch pending orders
$stmt = $pdo->prepare("
    SELECT o.id, o.order_number, o.total_amount, o.created_at, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.payment_status = 'pending' 
    ORDER BY o.created_at ASC
");
$stmt->execute();
$pending_orders = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Payments</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .table-wrapper { background: #111; border-radius: 8px; overflow: hidden; border: 1px solid #333; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid #222; }
        th { background: #1a1a1a; color: #ffc400; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        .btn { padding: 8px 15px; border-radius: 6px; cursor: pointer; border: none; font-weight: bold; font-size: 0.9rem; transition: 0.3s; }
        .btn-verify { background: #28a745; color: #fff; margin-right: 10px; }
        .btn-verify:hover { background: #218838; }
        .btn-reject { background: #dc3545; color: #fff; }
        .btn-reject:hover { background: #c82333; }
        h1 { color: #fff; margin-bottom: 20px; }
        .empty-state { padding: 40px; text-align: center; color: #aaa; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-container">
        <h1>Pending Payment Verifications (UPI)</h1>
        
        <div class="table-wrapper">
            <?php if (empty($pending_orders)): ?>
                <div class="empty-state">No pending payments to verify.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_orders as $order): ?>
                            <tr id="row-<?= $order['id'] ?>">
                                <td><?= htmlspecialchars($order['order_number']) ?></td>
                                <td><?= date('M j, Y h:i A', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($order['full_name']) ?><br>
                                    <small style="color:#aaa;"><?= htmlspecialchars($order['email']) ?></small>
                                </td>
                                <td style="color:#ffc400; font-weight:bold;">₹<?= number_format($order['total_amount'], 2) ?></td>
                                <td>
                                    <button class="btn btn-verify" onclick="processPayment(<?= $order['id'] ?>, 'verify')">Verify</button>
                                    <button class="btn btn-reject" onclick="processPayment(<?= $order['id'] ?>, 'reject')">Reject</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
    async function processPayment(orderId, action) {
        if (!confirm(`Are you sure you want to ${action} this payment?`)) return;
        
        try {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('action', action);
            
            const res = await fetch('/api/admin/payments.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('row-' + orderId).remove();
                alert(data.message);
            } else {
                alert('Error: ' + data.error);
            }
        } catch(e) {
            alert('A network error occurred.');
        }
    }
    </script>
</body>
</html>
