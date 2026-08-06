<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();
Auth::requireLogin('/store/checkout/success.php');

$orderNumber = trim($_GET['order'] ?? '');
$method      = trim($_GET['method'] ?? 'upi');

if (empty($orderNumber)) {
    header('Location: /store/index.php');
    exit;
}

// Fetch order from DB
$pdo   = db();
$stmt  = $pdo->prepare("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.user_id = ? AND o.order_type = 'store'
    LIMIT 1
");
$stmt->execute([$orderNumber, Auth::id()]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: /store/index.php');
    exit;
}

// Fetch order items
$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$order['id']]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed! | CodeByTushu Store</title>

    <link rel="icon" href="../../favicon.ico?v=6" sizes="any">
    <meta name="theme-color" content="#ffc400">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../../styles.css?v=40">
    <link rel="stylesheet" href="../store.css?v=1">

    <style>
        .success-wrapper {
            max-width: 680px;
            margin: 110px auto 80px;
            padding: 0 20px;
        }
        .success-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(34,197,94,0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem;
            color: #22c55e;
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .success-title {
            color: var(--text-heading);
            font-size: 1.9rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .success-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 30px;
        }
        .order-details-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: left;
            margin-bottom: 25px;
        }
        .order-details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .order-details-row:last-child { border-bottom: none; }
        .order-details-row .label { color: var(--text-muted); }
        .order-details-row .value { color: var(--text-heading); font-weight: 600; }
        .order-number-badge {
            background: rgba(255,196,0,0.1);
            border: 1px solid rgba(255,196,0,0.3);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .items-section { text-align: left; margin-bottom: 25px; }
        .items-section h3 {
            color: var(--text-heading);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .order-item-row:last-child { border-bottom: none; }
        .order-item-name { color: var(--text-heading); font-weight: 500; }
        .upi-note {
            background: rgba(255,196,0,0.06);
            border: 1px solid rgba(255,196,0,0.2);
            border-radius: 12px;
            padding: 18px;
            text-align: left;
            margin-bottom: 25px;
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.7;
        }
        .upi-note .upi-note-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .cod-note {
            background: rgba(34,197,94,0.05);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 12px;
            padding: 18px;
            text-align: left;
            margin-bottom: 25px;
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.7;
        }
        .cod-note .cod-note-title {
            color: #22c55e;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .action-buttons {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <script src="../../theme.js"></script>

    <!-- Animated Background -->
    <div class="cbt-hero-bg" style="position:fixed;z-index:-1;top:0;left:0;width:100vw;height:100vh;pointer-events:none;">
        <div class="cbt-glow-center"></div>
        <div class="cbt-streak cbt-streak-1"></div>
        <div class="cbt-streak cbt-streak-2"></div>
    </div>

    <!-- NAVBAR -->
    <nav class="cbt-navbar navbar" id="mainNavbar">
        <div class="cbt-nav-inner">
            <div class="cbt-logo">
                <a href="../../index.html" id="cbt-logo-link">
                    <img src="../../image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>
            <div class="cbt-nav-right">
                <a href="../cart/index.html" class="cbt-nav-cart-btn" aria-label="Cart">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <span class="cbt-cart-counter" style="display:none;" id="cartCounter">0</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="success-wrapper">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <div class="success-title">Order Placed Successfully! 🎉</div>
            <div class="success-subtitle">Thank you, <?= htmlspecialchars($order['shipping_name']) ?>! Your order has been received.</div>

            <!-- Order Details -->
            <div class="order-details-box">
                <div class="order-details-row">
                    <span class="label">Order Number</span>
                    <span class="order-number-badge"><?= htmlspecialchars($order['order_number']) ?></span>
                </div>
                <div class="order-details-row">
                    <span class="label">Total Amount</span>
                    <span class="value">₹<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="order-details-row">
                    <span class="label">Payment Method</span>
                    <span class="value"><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></span>
                </div>
                <div class="order-details-row">
                    <span class="label">Payment Status</span>
                    <span class="value" style="color:<?= $order['payment_method'] === 'cod' ? '#22c55e' : '#f59e0b' ?>">
                        <?= $order['payment_method'] === 'cod' ? 'COD – Pay on Delivery' : 'Pending Verification' ?>
                    </span>
                </div>
                <div class="order-details-row">
                    <span class="label">Shipping To</span>
                    <span class="value" style="text-align:right; max-width:60%;">
                        <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?>,
                        <?= htmlspecialchars($order['shipping_state']) ?> – <?= htmlspecialchars($order['shipping_pincode']) ?>
                    </span>
                </div>
            </div>

            <!-- Items -->
            <?php if (!empty($orderItems)): ?>
            <div class="items-section">
                <h3><i class="fa-solid fa-box" style="color:var(--primary);"></i> Items Ordered</h3>
                <?php foreach ($orderItems as $item): ?>
                <div class="order-item-row">
                    <span class="order-item-name"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></span>
                    <span>Qty: <?= intval($item['quantity'] ?? 1) ?> &times; ₹<?= number_format($item['price'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- UPI Note -->
            <?php if ($order['payment_method'] === 'upi'): ?>
            <div class="upi-note">
                <div class="upi-note-title"><i class="fa-solid fa-clock"></i> What happens next?</div>
                <p>Our team will verify your UPI payment (UTR: <strong><?= htmlspecialchars($order['payment_reference'] ?? 'N/A') ?></strong>) within 24 hours. Once verified, your order will be processed and shipped.</p>
                <p style="margin-top:8px;">You can track your order status by contacting us with your Order Number.</p>
            </div>
            <?php else: ?>
            <div class="cod-note">
                <div class="cod-note-title"><i class="fa-solid fa-truck"></i> Cash on Delivery</div>
                <p>Your order has been confirmed! Our team will process and dispatch your order. Please keep the payment amount <strong>₹<?= number_format($order['total_amount'], 2) ?></strong> ready at the time of delivery.</p>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="/store/order-tracking/?order=<?= urlencode($order['order_number']) ?>"
                   class="cbt-btn cbt-btn-primary">
                    <i class="fa-solid fa-location-dot"></i> Track My Order
                </a>
                <a href="../index.php" class="cbt-btn" style="border:1px solid var(--border-color); color:var(--text-heading); text-decoration:none; padding:12px 24px; border-radius:8px;">
                    <i class="fa-solid fa-store"></i> Continue Shopping
                </a>
                <a href="/api/store/invoice.php?order=<?= urlencode($order['order_number']) ?>"
                   target="_blank"
                   class="cbt-btn" style="border:1px solid var(--border-color); color:var(--text-heading); text-decoration:none; padding:12px 24px; border-radius:8px;">
                    <i class="fa-solid fa-file-invoice"></i> Invoice
                </a>
            </div>
        </div>
    </div>

    <script>
        // Update cart counter
        const cart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
        const counter = document.getElementById('cartCounter');
        let total = 0;
        cart.forEach(i => total += (i.quantity || 1));
        if (counter && total > 0) { counter.textContent = total; counter.style.display = 'flex'; }
    </script>
    <script src="/scroll-to-top.js"></script>
<?php require_once __DIR__ . '/../../includes/ai-widget-loader.php'; ?>
</body>
</html>