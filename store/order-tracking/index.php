<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();

$order      = null;
$orderItems = [];
$error      = '';
$searchNum  = trim($_GET['order'] ?? '');

if ($searchNum) {
    $pdo  = db();
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ? AND o.order_type = 'store'
        LIMIT 1
    ");
    $stmt->execute([$searchNum]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Security: only the owner or admin can see
        if (Auth::isLoggedIn() && Auth::id() != $order['user_id'] && !Auth::isAdmin()) {
            $order = null;
            $error = 'You do not have permission to view this order.';
        } else {
            $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$order['id']]);
            $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $error = 'Order not found. Please check your Order Number and try again.';
    }
}

// Determine timeline steps
function getTimeline(array $order): array {
    $ps = $order['payment_status'] ?? 'pending';
    $fs = $order['fulfillment_status'] ?? 'pending';
    $pm = $order['payment_method'] ?? 'upi';

    $steps = [
        ['key' => 'placed',     'label' => 'Order Placed',          'icon' => '🛒'],
        ['key' => 'verified',   'label' => 'Payment Confirmed',      'icon' => '✅'],
        ['key' => 'processing', 'label' => 'In Production',          'icon' => '🖨️'],
        ['key' => 'shipped',    'label' => 'Shipped',                 'icon' => '🚀'],
        ['key' => 'delivered',  'label' => 'Delivered',               'icon' => '🎁'],
    ];

    // Determine current active step
    $active = 'placed';
    if ($pm === 'cod' || $ps === 'verified') $active = 'verified';
    if ($fs === 'processing') $active = 'processing';
    if ($fs === 'shipped') $active = 'shipped';
    if ($fs === 'delivered') $active = 'delivered';
    if ($fs === 'cancelled') $active = 'cancelled';

    $activeIndex = 0;
    foreach ($steps as $i => $s) {
        if ($s['key'] === $active) { $activeIndex = $i; break; }
    }

    foreach ($steps as $i => &$s) {
        $s['status'] = $i < $activeIndex ? 'done' : ($i === $activeIndex ? 'active' : 'pending');
    }

    return ['steps' => $steps, 'active' => $active, 'cancelled' => ($fs === 'cancelled')];
}

$timeline = $order ? getTimeline($order) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order | CodeByTushu Store</title>
    <meta name="description" content="Track your CodeByTushu order status in real-time.">
    <link rel="icon" href="../../favicon.ico?v=6" sizes="any">
    <meta name="theme-color" content="#ffc400">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="../../styles.css?v=40">
    <link rel="stylesheet" href="../store.css?v=1">
    <style>
        .tracking-wrapper {
            max-width: 720px;
            margin: 110px auto 80px;
            padding: 0 20px;
        }
        .tracking-hero {
            text-align: center;
            margin-bottom: 36px;
        }
        .tracking-hero h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 8px;
        }
        .tracking-hero p { color: var(--text-muted); font-size: 0.95rem; }

        /* Search Form */
        .search-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
        }
        .search-row {
            display: flex;
            gap: 12px;
        }
        .search-row input {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 13px 18px;
            color: var(--text-heading);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-row input:focus { border-color: var(--primary); }
        .search-row button {
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 13px 24px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }
        .search-row button:hover { opacity: 0.85; }

        /* Error */
        .error-box {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 12px;
            padding: 16px 20px;
            color: #fca5a5;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Result Card */
        .result-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
        }
        .result-header {
            background: linear-gradient(135deg, rgba(255,196,0,0.12), rgba(255,196,0,0.04));
            border-bottom: 1px solid var(--border-color);
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
        }
        .result-order-num {
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--primary);
        }
        .result-date { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }

        /* Status Badge */
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .status-pending    { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .status-verified   { background: rgba(34,197,94,0.12);  color: #4ade80; }
        .status-processing { background: rgba(139,92,246,0.15); color: #a78bfa; }
        .status-shipped    { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .status-delivered  { background: rgba(34,197,94,0.15);  color: #22c55e; }
        .status-cancelled  { background: rgba(239,68,68,0.12);  color: #f87171; }

        /* Timeline */
        .timeline-section {
            padding: 28px;
            border-bottom: 1px solid var(--border-color);
        }
        .timeline-section h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-color);
            z-index: 0;
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .timeline-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 10px;
            border: 2px solid var(--border-color);
            background: var(--bg-card);
            transition: all 0.3s;
        }
        .timeline-step.done   .timeline-icon { background: rgba(34,197,94,0.15);  border-color: #22c55e; }
        .timeline-step.active .timeline-icon { background: rgba(255,196,0,0.2);   border-color: #ffc400; box-shadow: 0 0 0 4px rgba(255,196,0,0.1); animation: pulse 2s infinite; }
        .timeline-step.cancelled .timeline-icon { background: rgba(239,68,68,0.12); border-color: #ef4444; }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(255,196,0,0.1); }
            50%       { box-shadow: 0 0 0 8px rgba(255,196,0,0.05); }
        }
        .timeline-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: center;
            max-width: 70px;
            line-height: 1.3;
        }
        .timeline-step.done .timeline-label, .timeline-step.active .timeline-label {
            color: var(--text-heading);
            font-weight: 600;
        }

        /* Tracking Info */
        .tracking-info {
            padding: 20px 28px;
            background: rgba(59,130,246,0.04);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        .tracking-info-item { flex: 1; min-width: 140px; }
        .tracking-info-item .ti-label { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px; }
        .tracking-info-item .ti-value { font-weight: 700; color: var(--text-heading); font-family: monospace; }

        /* Order Info Grid */
        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border-bottom: 1px solid var(--border-color);
        }
        .order-info-cell {
            padding: 16px 28px;
            border-right: 1px solid var(--border-color);
        }
        .order-info-cell:nth-child(even) { border-right: none; }
        .order-info-cell .cell-label { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px; }
        .order-info-cell .cell-value { font-weight: 600; color: var(--text-heading); font-size: 0.9rem; }

        /* Items */
        .items-section { padding: 24px 28px; }
        .items-section h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .item-row:last-child { border-bottom: none; }
        .item-name { color: var(--text-heading); font-weight: 500; }
        .item-meta { color: var(--text-muted); }
        .items-total {
            display: flex;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
            margin-top: 8px;
            font-weight: 700;
        }
        .items-total .total-label { color: var(--text-muted); }
        .items-total .total-value { color: var(--primary); font-size: 1.05rem; }

        /* Invoice button */
        .card-footer {
            padding: 16px 28px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        @media(max-width: 600px) {
            .timeline-label { font-size: 0.65rem; max-width: 55px; }
            .order-info-grid { grid-template-columns: 1fr; }
            .order-info-cell { border-right: none; }
            .search-row { flex-direction: column; }
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

<div class="tracking-wrapper">
    <div class="tracking-hero">
        <h1>📦 Track Your Order</h1>
        <p>Enter your Order Number to get real-time status updates.</p>
    </div>

    <!-- Search Form -->
    <div class="search-card">
        <form method="GET" action="">
            <div class="search-row">
                <input
                    type="text"
                    name="order"
                    placeholder="e.g. CBT-2026-XXXXXX"
                    value="<?= htmlspecialchars($searchNum) ?>"
                    autocomplete="off"
                    required
                >
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Track</button>
            </div>
        </form>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="error-box">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Result -->
    <?php if ($order && $timeline): ?>
    <?php
        $fs = $order['fulfillment_status'] ?? 'pending';
        $ps = $order['payment_status'] ?? 'pending';
        $pm = $order['payment_method'] ?? 'upi';
        $statusLabel = match($fs) {
            'processing' => 'In Production',
            'shipped'    => 'Shipped',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
            default => ($pm === 'cod' ? 'Confirmed' : ($ps === 'verified' ? 'Payment Verified' : 'Pending Payment'))
        };
        $statusClass = match($fs) {
            'processing' => 'status-processing',
            'shipped'    => 'status-shipped',
            'delivered'  => 'status-delivered',
            'cancelled'  => 'status-cancelled',
            default => ($ps === 'verified' || $pm === 'cod' ? 'status-verified' : 'status-pending')
        };
    ?>
    <div class="result-card">
        <!-- Header -->
        <div class="result-header">
            <div>
                <div class="result-order-num"><?= htmlspecialchars($order['order_number']) ?></div>
                <div class="result-date">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
            </div>
            <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
        </div>

        <!-- Timeline -->
        <div class="timeline-section">
            <h3>Order Progress</h3>
            <?php if ($timeline['cancelled']): ?>
            <div style="text-align:center;padding:20px;color:#f87171;font-weight:600;">
                <i class="fa-solid fa-circle-xmark" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                This order has been cancelled.
            </div>
            <?php else: ?>
            <div class="timeline">
                <?php foreach ($timeline['steps'] as $step): ?>
                <div class="timeline-step <?= $step['status'] ?>">
                    <div class="timeline-icon">
                        <?php if ($step['status'] === 'done'): ?>✓
                        <?php else: ?><?= $step['icon'] ?><?php endif; ?>
                    </div>
                    <div class="timeline-label"><?= $step['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tracking info (if shipped) -->
        <?php if (!empty($order['tracking_number']) || !empty($order['awb_number'])): ?>
        <div class="tracking-info">
            <?php if (!empty($order['courier_name'])): ?>
            <div class="tracking-info-item">
                <div class="ti-label">Courier</div>
                <div class="ti-value"><?= htmlspecialchars($order['courier_name']) ?></div>
            </div>
            <?php endif; ?>
            <div class="tracking-info-item">
                <div class="ti-label">Tracking / AWB Number</div>
                <div class="ti-value"><?= htmlspecialchars($order['tracking_number'] ?? $order['awb_number']) ?></div>
            </div>
            <?php if (!empty($order['qikink_order_id'])): ?>
            <div class="tracking-info-item">
                <div class="ti-label">Qikink Order ID</div>
                <div class="ti-value" style="font-size:0.85rem;"><?= htmlspecialchars($order['qikink_order_id']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Order Info Grid -->
        <div class="order-info-grid">
            <div class="order-info-cell">
                <div class="cell-label">Customer</div>
                <div class="cell-value"><?= htmlspecialchars($order['shipping_name'] ?? '') ?></div>
            </div>
            <div class="order-info-cell">
                <div class="cell-label">Payment Method</div>
                <div class="cell-value"><?= strtoupper(htmlspecialchars($order['payment_method'] ?? '')) ?></div>
            </div>
            <div class="order-info-cell">
                <div class="cell-label">Shipping Address</div>
                <div class="cell-value" style="font-size:0.82rem;line-height:1.5;">
                    <?= htmlspecialchars($order['shipping_address'] ?? '') ?>,
                    <?= htmlspecialchars($order['shipping_city'] ?? '') ?>,
                    <?= htmlspecialchars($order['shipping_state'] ?? '') ?> –
                    <?= htmlspecialchars($order['shipping_pincode'] ?? '') ?>
                </div>
            </div>
            <div class="order-info-cell">
                <div class="cell-label">Total Amount</div>
                <div class="cell-value" style="color:var(--primary);font-size:1.05rem;">
                    ₹<?= number_format((float)($order['total_amount'] ?? 0), 2) ?>
                </div>
            </div>
        </div>

        <!-- Items -->
        <?php if (!empty($orderItems)): ?>
        <div class="items-section">
            <h3>Items Ordered</h3>
            <?php foreach ($orderItems as $item): ?>
            <div class="item-row">
                <span class="item-name"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></span>
                <span class="item-meta">
                    <?= intval($item['quantity'] ?? 1) ?> × ₹<?= number_format((float)($item['price'] ?? 0), 2) ?>
                </span>
            </div>
            <?php endforeach; ?>
            <div class="items-total">
                <span class="total-label">Order Total</span>
                <span class="total-value">₹<?= number_format((float)($order['total_amount'] ?? 0), 2) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div class="card-footer">
            <a href="/api/store/invoice.php?order=<?= urlencode($order['order_number']) ?>"
               target="_blank"
               class="cbt-btn"
               style="border:1px solid var(--border-color);color:var(--text-heading);text-decoration:none;padding:10px 20px;border-radius:8px;font-size:0.88rem;display:inline-flex;align-items:center;gap:6px;">
                <i class="fa-solid fa-file-invoice"></i> Download Invoice
            </a>
            <a href="../index.php" class="cbt-btn cbt-btn-primary" style="padding:10px 20px;font-size:0.88rem;">
                <i class="fa-solid fa-store"></i> Continue Shopping
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    const cart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
    const counter = document.getElementById('cartCounter');
    let total = 0;
    cart.forEach(i => total += (i.quantity || 1));
    if (counter && total > 0) { counter.textContent = total; counter.style.display = 'flex'; }
</script>
<script src="/scroll-to-top.js"></script>
</body>
</html>
