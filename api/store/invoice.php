<?php
/**
 * Invoice Generator — CodeByTushu Store
 * URL: /api/store/invoice.php?order=CBT-2026-XXXXXX
 * Generates a professional, printable HTML invoice.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();

$orderNum = trim($_GET['order'] ?? '');
if (!$orderNum) {
    http_response_code(400);
    exit('<h2>Missing order number.</h2>');
}

$pdo  = db();
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.order_type = 'store'
    LIMIT 1
");
$stmt->execute([$orderNum]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit('<h2>Order not found.</h2>');
}

// Security: only the owner (or admin) can view
if (!Auth::isAdmin() && Auth::id() != $order['user_id']) {
    http_response_code(403);
    exit('<h2>Access denied.</h2>');
}

$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$invoiceDate = date('d M Y', strtotime($order['created_at']));
$total       = number_format((float)($order['total_amount'] ?? 0), 2);
$subtotal    = 0;
foreach ($items as $i) { $subtotal += ($i['price'] ?? 0) * ($i['quantity'] ?? 1); }
$shipping    = (float)($order['total_amount'] ?? 0) - $subtotal;

$statusLabel = match($order['fulfillment_status'] ?? 'pending') {
    'processing' => 'In Production',
    'shipped'    => 'Shipped',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled',
    default => 'Processing'
};

$payStatusLabel = match($order['payment_status'] ?? 'pending') {
    'verified' => 'Paid',
    'rejected' => 'Rejected',
    default    => ($order['payment_method'] === 'cod' ? 'Pay on Delivery' : 'Pending Verification')
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars($orderNum) ?> — CodeByTushu</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f8f8;
            color: #1a1a1a;
            font-size: 14px;
            line-height: 1.6;
        }
        .invoice-page {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Header */
        .inv-header {
            background: #111118;
            color: #fff;
            padding: 32px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .inv-brand-name {
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        .inv-brand-name span { color: #ffc400; }
        .inv-brand-sub { color: #888; font-size: 0.8rem; margin-top: 2px; }
        .inv-label-block { text-align: right; }
        .inv-label { font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .inv-order-num { font-family: monospace; font-size: 1.1rem; font-weight: 700; color: #ffc400; }
        .inv-date { color: #aaa; font-size: 0.88rem; margin-top: 4px; }

        /* Info Section */
        .inv-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0;
            border-bottom: 1px solid #eee;
        }
        .inv-info-block {
            padding: 20px 28px;
            border-right: 1px solid #eee;
        }
        .inv-info-block:last-child { border-right: none; }
        .inv-info-block .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 6px;
        }
        .inv-info-block .info-value { font-weight: 600; color: #1a1a1a; font-size: 0.9rem; }
        .inv-info-block .info-sub { color: #666; font-size: 0.82rem; line-height: 1.5; }

        /* Status Badges */
        .pay-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .pay-badge.paid      { background: #dcfce7; color: #166534; }
        .pay-badge.pending   { background: #fef9c3; color: #713f12; }
        .pay-badge.rejected  { background: #fee2e2; color: #991b1b; }
        .pay-badge.cod       { background: #e0f2fe; color: #0369a1; }

        /* Items Table */
        .inv-items { padding: 28px 28px 0; }
        .inv-items h3 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 14px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.items-table thead tr { background: #f5f5f5; }
        table.items-table thead th {
            padding: 10px 14px;
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #777;
            font-weight: 600;
        }
        table.items-table thead th:last-child { text-align: right; }
        table.items-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        table.items-table tbody td:last-child { text-align: right; font-weight: 600; }
        table.items-table .product-name { font-weight: 600; color: #111; }
        table.items-table .product-sku  { font-size: 0.78rem; color: #999; margin-top: 2px; }

        /* Totals */
        .inv-totals {
            padding: 0 28px 28px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 280px;
            margin-top: 16px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            font-size: 0.9rem;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
        }
        .total-row:last-child {
            border-bottom: none;
            font-weight: 800;
            font-size: 1.05rem;
            color: #111;
            padding-top: 12px;
        }
        .total-row:last-child .tr-val { color: #d97706; }

        /* Notes */
        .inv-notes {
            padding: 16px 28px;
            background: #fafafa;
            border-top: 1px solid #eee;
            font-size: 0.82rem;
            color: #888;
        }

        /* Footer */
        .inv-footer {
            background: #111118;
            color: #666;
            padding: 16px 28px;
            font-size: 0.78rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .inv-footer strong { color: #ffc400; }

        /* Print button */
        .print-bar {
            text-align: center;
            padding: 16px;
        }
        .print-btn {
            background: #ffc400;
            color: #000;
            border: none;
            padding: 11px 28px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        .print-btn:hover { opacity: 0.85; }
        .back-link {
            color: #777;
            text-decoration: none;
            font-size: 0.88rem;
        }
        .back-link:hover { color: #333; }

        @media print {
            body { background: #fff; }
            .print-bar { display: none; }
            .invoice-page { box-shadow: none; margin: 0; border-radius: 0; }
        }
    </style>
</head>
<body>

<div class="print-bar">
    <button class="print-btn" onclick="window.print()">
        🖨️ Print / Save as PDF
    </button>
    <a href="/store/order-tracking/?order=<?= urlencode($orderNum) ?>" class="back-link">← Back to Tracking</a>
</div>

<div class="invoice-page">
    <!-- Header -->
    <div class="inv-header">
        <div>
            <div class="inv-brand-name">CODE<span>BY</span>TUSHU</div>
            <div class="inv-brand-sub">codebytushu.com</div>
        </div>
        <div class="inv-label-block">
            <div class="inv-label">Invoice</div>
            <div class="inv-order-num"><?= htmlspecialchars($order['order_number']) ?></div>
            <div class="inv-date">Date: <?= $invoiceDate ?></div>
        </div>
    </div>

    <!-- Info Row -->
    <div class="inv-info">
        <div class="inv-info-block">
            <div class="info-label">Bill To</div>
            <div class="info-value"><?= htmlspecialchars($order['shipping_name'] ?? 'N/A') ?></div>
            <div class="info-sub"><?= htmlspecialchars($order['email'] ?? '') ?></div>
        </div>
        <div class="inv-info-block">
            <div class="info-label">Ship To</div>
            <div class="info-sub">
                <?= htmlspecialchars($order['shipping_address'] ?? '') ?><br>
                <?= htmlspecialchars($order['shipping_city'] ?? '') ?>,
                <?= htmlspecialchars($order['shipping_state'] ?? '') ?> – <?= htmlspecialchars($order['shipping_pincode'] ?? '') ?><br>
                📞 <?= htmlspecialchars($order['shipping_phone'] ?? '') ?>
            </div>
        </div>
        <div class="inv-info-block">
            <div class="info-label">Payment</div>
            <div class="info-value"><?= strtoupper(htmlspecialchars($order['payment_method'] ?? '')) ?></div>
            <div style="margin-top:6px;">
                <?php
                $badgeClass = match($order['payment_status'] ?? '') {
                    'verified' => 'paid',
                    'rejected' => 'rejected',
                    default    => ($order['payment_method'] === 'cod' ? 'cod' : 'pending')
                };
                ?>
                <span class="pay-badge <?= $badgeClass ?>"><?= $payStatusLabel ?></span>
            </div>
            <div class="info-sub" style="margin-top:6px;">Order: <?= $statusLabel ?></div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="inv-items">
        <h3>Order Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:50%;">Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item):
                    $lineTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                ?>
                <tr>
                    <td>
                        <div class="product-name"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></div>
                        <?php if (!empty($item['variant_info'])): ?>
                        <div class="product-sku"><?= htmlspecialchars($item['variant_info']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= intval($item['quantity'] ?? 1) ?></td>
                    <td>₹<?= number_format((float)($item['price'] ?? 0), 2) ?></td>
                    <td>₹<?= number_format($lineTotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="inv-totals">
        <div class="totals-box">
            <div class="total-row">
                <span class="tr-label">Subtotal</span>
                <span class="tr-val">₹<?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if ($shipping > 0): ?>
            <div class="total-row">
                <span class="tr-label">Shipping</span>
                <span class="tr-val">₹<?= number_format($shipping, 2) ?></span>
            </div>
            <?php else: ?>
            <div class="total-row">
                <span class="tr-label">Shipping</span>
                <span class="tr-val" style="color:#16a34a;">Free</span>
            </div>
            <?php endif; ?>
            <div class="total-row">
                <span class="tr-label">Grand Total</span>
                <span class="tr-val">₹<?= $total ?></span>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="inv-notes">
        <strong>Note:</strong>
        <?php if ($order['payment_method'] === 'cod'): ?>
            Please keep ₹<?= $total ?> ready at the time of delivery.
        <?php elseif ($order['payment_status'] === 'verified'): ?>
            Payment received and verified. Thank you!
        <?php else: ?>
            UPI payment verification is pending. Please contact us if you have any queries.
        <?php endif; ?>
        &nbsp;|&nbsp; For support: codebytushu@gmail.com
    </div>

    <!-- Footer -->
    <div class="inv-footer">
        <span>© <?= date('Y') ?> <strong>CodeByTushu</strong>. All rights reserved.</span>
        <span>This is a computer-generated invoice.</span>
    </div>
</div>

</body>
</html>
