<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireLogin();

$order_number = isset($_GET['order']) ? $_GET['order'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Submitted - CodeByTushu</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container { max-width: 600px; margin: 80px auto; padding: 40px; background: #111; border-radius: 12px; border: 1px solid #333; text-align: center; }
        .icon { font-size: 5rem; color: #ffc400; margin-bottom: 20px; }
        h1 { color: #fff; margin-bottom: 10px; }
        p { color: #aaa; line-height: 1.6; margin-bottom: 30px; }
        .order-number { font-size: 1.5rem; color: #ffc400; font-weight: bold; background: #222; padding: 10px; border-radius: 6px; display: inline-block; margin-bottom: 30px; }
        .btn-dash { background: #ffc400; color: #000; padding: 12px 30px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn-dash:hover { background: #e6b000; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="success-container">
        <i class="fas fa-clock icon"></i>
        <h1>Payment Submitted!</h1>
        <p>Thank you! Your payment request has been submitted successfully and is currently <strong>pending verification</strong> by our admin.</p>
        
        <div>Order Number:</div>
        <div class="order-number"><?= htmlspecialchars($order_number) ?></div>
        
        <p>Once verified, your course(s) will automatically unlock in your Dashboard.</p>
        
        <a href="/user/purchases.php" class="btn-dash">View Purchase History</a>
    </div>

</body>
</html>
