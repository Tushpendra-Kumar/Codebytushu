<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireLogin();

$pdo = db();
$user_id = $_SESSION['user_id'];

// Get cart total
$stmt = $pdo->prepare("
    SELECT c.course_id, co.price 
    FROM cart_items c 
    JOIN courses co ON c.course_id = co.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

if (empty($items)) {
    header("Location: /cart/");
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += (float)$item['price'];
}

$upi_id = "tushpendrakum@slc";
// Generate UPI Intent Link
$upi_link = "upi://pay?pa={$upi_id}&pn=CodeByTushu&am={$total}&cu=INR";
// Generate QR code URL using a free API
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_link);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - CodeByTushu</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-container { max-width: 600px; margin: 50px auto; padding: 30px; background: #111; border-radius: 12px; border: 1px solid #333; text-align: center; }
        .checkout-container h1 { color: #ffc400; margin-top: 0; margin-bottom: 10px; }
        .amount-display { font-size: 2.5rem; font-weight: bold; color: #fff; margin: 20px 0; }
        .upi-box { background: #222; border: 1px dashed #555; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .upi-id { font-size: 1.2rem; color: #ffc400; font-weight: bold; letter-spacing: 1px; user-select: all; cursor: copy; }
        .qr-code { margin: 20px 0; padding: 10px; background: #fff; border-radius: 8px; display: inline-block; }
        .qr-code img { display: block; border-radius: 4px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; border: none; font-size: 1.1rem; transition: 0.3s; width: 100%; margin-bottom: 15px; }
        .btn-pay { background: #007bff; color: #fff; }
        .btn-pay:hover { background: #0056b3; }
        .btn-verify { background: #28a745; color: #fff; }
        .btn-verify:hover { background: #218838; }
        .instructions { color: #aaa; font-size: 0.9rem; text-align: left; margin-top: 30px; line-height: 1.6; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="checkout-container">
        <h1>Complete Your Payment</h1>
        <p style="color:#aaa;">You are paying for <?= count($items) ?> course(s).</p>
        
        <div class="amount-display">
            ₹<?= number_format($total, 2) ?>
        </div>
        
        <div class="upi-box">
            <p style="margin: 0 0 10px; color: #ccc;">Scan QR Code to Pay</p>
            <div class="qr-code">
                <img src="<?= $qr_url ?>" alt="UPI QR Code">
            </div>
            <p style="margin: 10px 0 5px; color: #ccc;">Or pay to UPI ID:</p>
            <div class="upi-id"><?= $upi_id ?></div>
        </div>
        
        <!-- Deep link for Mobile Users -->
        <a href="<?= $upi_link ?>" class="btn btn-pay"><i class="fas fa-mobile-alt"></i> Pay via UPI App (Mobile Only)</a>
        
        <button id="btn-submit" class="btn btn-verify" onclick="submitOrder()">
            <i class="fas fa-check-circle"></i> I Have Completed Payment
        </button>
        
        <div class="instructions">
            <strong>Important Instructions:</strong>
            <ul style="padding-left: 20px; margin-top: 10px;">
                <li>Please do not refresh this page while paying.</li>
                <li>After completing the payment on your app, click the "I Have Completed Payment" button above.</li>
                <li>Your course will be unlocked <strong>instantly</strong>, and you can download the PDF right away!</li>
            </ul>
        </div>
    </div>

    <script>
    async function submitOrder() {
        if (!confirm('Are you sure you have completed the payment? False submissions may lead to account suspension.')) return;
        
        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Order...';
        
        try {
            const res = await fetch('/api/checkout/submit.php', { method: 'POST' });
            const data = await res.json();
            
            if (data.success) {
                window.location.href = '/checkout/success.php?order=' + data.order_number;
            } else {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> I Have Completed Payment';
            }
        } catch(e) {
            alert('A network error occurred.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> I Have Completed Payment';
        }
    }
    </script>
</body>
</html>
