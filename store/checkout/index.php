<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();
Auth::requireLogin('/store/checkout/index.php');

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | CodeByTushu Store</title>

    <link rel="icon" href="../../favicon.ico?v=6" sizes="any">
    <link rel="icon" href="../../favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <meta name="theme-color" content="#ffc400">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../../styles.css?v=40">
    <link rel="stylesheet" href="../store.css?v=1">
    <script src="../js/toast.js"></script>

    <style>
        .checkout-wrapper {
            max-width: 1000px;
            margin: 110px auto 80px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        .checkout-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 30px;
        }
        .checkout-card h2 {
            color: var(--text-heading);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 24px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-heading);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .form-group input::placeholder { color: var(--text-muted); }
        .form-group select option { background: #1a1a1a; }

        /* Payment Method Selector */
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }
        .payment-option {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .payment-option:hover { border-color: var(--primary); background: rgba(255,196,0,0.04); }
        .payment-option.selected { border-color: var(--primary); background: rgba(255,196,0,0.07); }
        .payment-option input[type="radio"] { accent-color: var(--primary); width: 18px; height: 18px; }
        .payment-option .pay-label { color: var(--text-heading); font-weight: 600; font-size: 0.95rem; }
        .payment-option .pay-desc { color: var(--text-muted); font-size: 0.78rem; margin-top: 2px; }

        /* UPI Section */
        #upi-section {
            background: rgba(255,196,0,0.04);
            border: 1px solid rgba(255,196,0,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        #upi-section .upi-id {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
            margin: 10px 0;
        }
        #upi-section .upi-steps {
            text-align: left;
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.8;
            margin: 12px 0;
        }
        #upi-section .upi-steps li { margin-bottom: 2px; }
        .utr-input-wrap { margin-top: 12px; }

        /* COD Section */
        #cod-section {
            background: rgba(34,197,94,0.05);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.7;
        }
        #cod-section .cod-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(34,197,94,0.12);
            color: #22c55e;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Order Summary */
        .order-summary-item {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .order-summary-item:last-of-type { border-bottom: none; }
        .order-summary-item img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            background: #222;
        }
        .order-summary-item-info { flex: 1; min-width: 0; }
        .order-summary-item-title {
            color: var(--text-heading);
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .order-summary-item-meta { color: var(--text-muted); font-size: 0.8rem; margin-top: 3px; }
        .order-summary-item-price { color: var(--primary); font-weight: 700; white-space: nowrap; }
        .order-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0 12px;
            border-top: 1px solid var(--border-color);
            margin-top: 10px;
        }
        .order-total-label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        .order-total-amount { color: var(--primary); font-size: 1.4rem; font-weight: 800; }

        .checkout-submit-btn {
            width: 100%;
            padding: 16px;
            font-size: 1.05rem;
            font-weight: 700;
            margin-top: 10px;
        }
        .checkout-submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .empty-cart-msg {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-cart-msg i { font-size: 3rem; margin-bottom: 15px; display: block; color: var(--border-color); }

        /* Security badges */
        .secure-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .secure-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .secure-badge i { color: #22c55e; }

        @media (max-width: 768px) {
            .checkout-wrapper { grid-template-columns: 1fr; margin-top: 80px; }
            .form-row { grid-template-columns: 1fr; }
            .payment-options { grid-template-columns: 1fr; }
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
        <div class="cbt-circle cbt-circle-left"></div>
        <div class="cbt-circle cbt-circle-right"></div>
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
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="../index.php" class="cbt-nav-link">Store</a></li>
                <li><a href="../cart/index.html" class="cbt-nav-link">My Cart</a></li>
                <li><a href="index.php" class="cbt-nav-link active">Checkout</a></li>
            </ul>
            <div class="cbt-nav-right">
                <a href="../cart/index.html" class="cbt-nav-cart-btn" aria-label="Cart">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <span class="cbt-cart-counter" style="display:none;">0</span>
                </a>
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn" aria-label="Menu">
                    <span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- CHECKOUT CONTENT -->
    <div class="checkout-wrapper" id="checkoutWrapper">
        <!-- Loading state -->
        <div style="grid-column:1/-1; text-align:center; padding:80px; color:var(--text-muted);">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--primary);"></i>
            <p style="margin-top:15px;">Loading your cart...</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../courses/js/data.js"></script>
    <script src="../js/data.js"></script>
    <script src="../../courses/js/cart.js"></script>
    <script>
    (function() {
        const UPI_ID = 'tushpendrakumar@okicici';
        const MERCHANT_NAME = 'CodeByTushu';

        const wrapper = document.getElementById('checkoutWrapper');

        // Cart counter
        function updateCartCount() {
            const counters = document.querySelectorAll('.cbt-cart-counter');
            const cart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
            let total = 0;
            cart.forEach(i => total += (i.quantity || 1));
            counters.forEach(el => { el.textContent = total; el.style.display = total > 0 ? 'flex' : 'none'; });
        }
        updateCartCount();

        // Load cart
        const cartItems = JSON.parse(localStorage.getItem('cbt_cart')) || [];
        const storeItems = cartItems.filter(i => !i.isCourse);

        if (storeItems.length === 0) {
            wrapper.innerHTML = `
                <div style="grid-column:1/-1;" class="empty-cart-msg">
                    <i class="material-symbols-rounded">shopping_cart</i>
                    <h2 style="color:var(--text-heading); margin-bottom:10px;">Your cart is empty</h2>
                    <p style="margin-bottom:20px;">Add some products before checking out.</p>
                    <a href="../index.php" class="cbt-btn cbt-btn-primary">Browse Store</a>
                </div>`;
            return;
        }

        // Calculate total
        let grandTotal = 0;
        storeItems.forEach(i => grandTotal += i.price * (i.quantity || 1));

        // Build order summary HTML
        let summaryItemsHTML = storeItems.map(item => {
            const fixPath = p => p && p.startsWith('../') ? '../' + p : (p || '');
            const imgSrc = fixPath(item.image);
            return `
                <div class="order-summary-item">
                    <img src="${imgSrc}" alt="${item.title}" onerror="this.style.display='none'">
                    <div class="order-summary-item-info">
                        <div class="order-summary-item-title">${item.title}</div>
                        <div class="order-summary-item-meta">${item.category} &bull; Qty: ${item.quantity || 1}</div>
                    </div>
                    <div class="order-summary-item-price">₹${item.price * (item.quantity || 1)}</div>
                </div>`;
        }).join('');

        // UPI QR code
        const upiLink = `upi://pay?pa=${UPI_ID}&pn=${encodeURIComponent(MERCHANT_NAME)}&am=${grandTotal}&cu=INR`;
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(upiLink)}`;

        wrapper.innerHTML = `
            <!-- LEFT: Shipping Form -->
            <div>
                <div class="checkout-card">
                    <h2><i class="fa-solid fa-location-dot" style="color:var(--primary)"></i> Shipping Details</h2>
                    <form id="checkoutForm" novalidate>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" id="shipping_name" placeholder="Your full name" required value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" id="shipping_phone" placeholder="10-digit mobile number" required maxlength="10">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Street Address *</label>
                            <input type="text" id="shipping_address" placeholder="House no., street, area" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" id="shipping_city" placeholder="City" required>
                            </div>
                            <div class="form-group">
                                <label>State *</label>
                                <input type="text" id="shipping_state" placeholder="State" required>
                            </div>
                        </div>
                        <div class="form-group" style="max-width:200px;">
                            <label>PIN Code *</label>
                            <input type="text" id="shipping_pincode" placeholder="6-digit PIN" required maxlength="6">
                        </div>
                    </form>
                </div>

                <div class="checkout-card" style="margin-top:20px;">
                    <h2><i class="fa-solid fa-credit-card" style="color:var(--primary)"></i> Payment Method</h2>

                    <div class="payment-options">
                        <label class="payment-option selected" id="opt-upi">
                            <input type="radio" name="payment" value="upi" checked>
                            <div>
                                <div class="pay-label">📱 UPI</div>
                                <div class="pay-desc">Pay via any UPI app</div>
                            </div>
                        </label>
                        <label class="payment-option" id="opt-cod">
                            <input type="radio" name="payment" value="cod">
                            <div>
                                <div class="pay-label">🚚 Cash on Delivery</div>
                                <div class="pay-desc">Pay when delivered</div>
                            </div>
                        </label>
                    </div>

                    <!-- UPI Section -->
                    <div id="upi-section">
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:8px;">Scan QR or use UPI ID below</p>
                        <img src="${qrUrl}" alt="UPI QR Code" style="border-radius:10px; border:3px solid var(--primary);" width="180" height="180">
                        <div class="upi-id">${UPI_ID}</div>
                        <ol class="upi-steps">
                            <li>Open any UPI app (GPay, PhonePe, Paytm, etc.)</li>
                            <li>Scan QR or enter UPI ID above</li>
                            <li>Pay <strong>₹${grandTotal}</strong> and note down the UTR/transaction ID</li>
                            <li>Enter the UTR below and place your order</li>
                        </ol>
                        <div class="utr-input-wrap">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>UTR / Transaction Reference *</label>
                                <input type="text" id="payment_reference" placeholder="e.g. 123456789012" style="width:100%; box-sizing:border-box;">
                            </div>
                        </div>
                    </div>

                    <!-- COD Section (hidden by default) -->
                    <div id="cod-section" style="display:none;">
                        <span class="cod-badge"><i class="fa-solid fa-check-circle"></i> Cash on Delivery Available</span>
                        <p>Your order will be delivered to your address and you can pay cash at the time of delivery. No advance payment needed.</p>
                        <p style="margin-top:8px; font-size:0.82rem;"><strong style="color:var(--primary)">Note:</strong> COD is available for orders within India only.</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Order Summary -->
            <div>
                <div class="checkout-card" style="position:sticky; top:90px;">
                    <h2><i class="fa-solid fa-bag-shopping" style="color:var(--primary)"></i> Order Summary</h2>
                    ${summaryItemsHTML}
                    <div class="order-total-row">
                        <span class="order-total-label">Total Amount</span>
                        <span class="order-total-amount">₹${grandTotal}</span>
                    </div>
                    <button class="cbt-btn cbt-btn-primary checkout-submit-btn" id="placeOrderBtn" onclick="placeOrder()">
                        Place Order →
                    </button>
                    <div class="secure-badges">
                        <span class="secure-badge"><i class="fa-solid fa-lock"></i> Secure Checkout</span>
                        <span class="secure-badge"><i class="fa-solid fa-shield-halved"></i> Safe & Trusted</span>
                        <span class="secure-badge"><i class="fa-solid fa-truck"></i> Fast Delivery</span>
                    </div>
                </div>
            </div>
        `;

        // Payment toggle logic
        document.querySelectorAll('input[name="payment"]').forEach(radio => {
            radio.addEventListener('change', () => {
                const isUPI = radio.value === 'upi';
                document.getElementById('upi-section').style.display = isUPI ? 'block' : 'none';
                document.getElementById('cod-section').style.display = isUPI ? 'none' : 'block';
                document.getElementById('opt-upi').classList.toggle('selected', isUPI);
                document.getElementById('opt-cod').classList.toggle('selected', !isUPI);
            });
        });

        // Hamburger
        const ham = document.getElementById('cbt-hamburger-btn');
        const nav = document.getElementById('cbt-center-nav');
        if (ham && nav) ham.addEventListener('click', () => nav.classList.toggle('show'));
    })();

    function placeOrder() {
        const btn = document.getElementById('placeOrderBtn');
        const paymentMethod = document.querySelector('input[name="payment"]:checked').value;

        // Collect form values
        const shipping = {
            name:    document.getElementById('shipping_name')?.value.trim(),
            phone:   document.getElementById('shipping_phone')?.value.trim(),
            address: document.getElementById('shipping_address')?.value.trim(),
            city:    document.getElementById('shipping_city')?.value.trim(),
            state:   document.getElementById('shipping_state')?.value.trim(),
            pincode: document.getElementById('shipping_pincode')?.value.trim()
        };
        const utr = document.getElementById('payment_reference')?.value.trim();

        // Validation
        for (const [key, val] of Object.entries(shipping)) {
            if (!val) {
                showToast(`Please fill in your ${key.replace('_',' ')}.`, 'error');
                return;
            }
        }
        if (!/^\d{10}$/.test(shipping.phone)) {
            showToast('Please enter a valid 10-digit phone number.', 'error');
            return;
        }
        if (!/^\d{6}$/.test(shipping.pincode)) {
            showToast('Please enter a valid 6-digit PIN code.', 'error');
            return;
        }
        if (paymentMethod === 'upi' && !utr) {
            showToast('Please enter the UTR / Transaction reference ID after payment.', 'error');
            return;
        }

        // Get cart items
        const cartItems = JSON.parse(localStorage.getItem('cbt_cart')) || [];
        const storeItems = cartItems.filter(i => !i.isCourse);

        if (storeItems.length === 0) {
            showToast('Your cart is empty!', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Placing Order...';

        fetch('/api/store/checkout.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: storeItems,
                shipping: shipping,
                payment_method: paymentMethod,
                payment_reference: utr || null
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clear store items from cart
                const allCart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
                const remaining = allCart.filter(i => i.isCourse);
                localStorage.setItem('cbt_cart', JSON.stringify(remaining));
                window.dispatchEvent(new Event('cartUpdated'));

                // Redirect to success page
                window.location.href = 'success.php?order=' + encodeURIComponent(data.order_number) + '&method=' + paymentMethod;
            } else {
                showToast(data.message || 'Failed to place order. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Place Order →';
            }
        })
        .catch(() => {
            showToast('Network error. Please check your connection and try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Place Order →';
        });
    }
    </script>
    <script src="/scroll-to-top.js"></script>
<?php require_once <?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

Auth::boot();
Auth::requireLogin('/store/checkout/index.php');

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | CodeByTushu Store</title>

    <link rel="icon" href="../../favicon.ico?v=6" sizes="any">
    <link rel="icon" href="../../favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <meta name="theme-color" content="#ffc400">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../../styles.css?v=40">
    <link rel="stylesheet" href="../store.css?v=1">
    <script src="../js/toast.js"></script>

    <style>
        .checkout-wrapper {
            max-width: 1000px;
            margin: 110px auto 80px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        .checkout-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 30px;
        }
        .checkout-card h2 {
            color: var(--text-heading);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 24px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-heading);
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .form-group input::placeholder { color: var(--text-muted); }
        .form-group select option { background: #1a1a1a; }

        /* Payment Method Selector */
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }
        .payment-option {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .payment-option:hover { border-color: var(--primary); background: rgba(255,196,0,0.04); }
        .payment-option.selected { border-color: var(--primary); background: rgba(255,196,0,0.07); }
        .payment-option input[type="radio"] { accent-color: var(--primary); width: 18px; height: 18px; }
        .payment-option .pay-label { color: var(--text-heading); font-weight: 600; font-size: 0.95rem; }
        .payment-option .pay-desc { color: var(--text-muted); font-size: 0.78rem; margin-top: 2px; }

        /* UPI Section */
        #upi-section {
            background: rgba(255,196,0,0.04);
            border: 1px solid rgba(255,196,0,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        #upi-section .upi-id {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 0.5px;
            margin: 10px 0;
        }
        #upi-section .upi-steps {
            text-align: left;
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.8;
            margin: 12px 0;
        }
        #upi-section .upi-steps li { margin-bottom: 2px; }
        .utr-input-wrap { margin-top: 12px; }

        /* COD Section */
        #cod-section {
            background: rgba(34,197,94,0.05);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.7;
        }
        #cod-section .cod-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(34,197,94,0.12);
            color: #22c55e;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Order Summary */
        .order-summary-item {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .order-summary-item:last-of-type { border-bottom: none; }
        .order-summary-item img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            background: #222;
        }
        .order-summary-item-info { flex: 1; min-width: 0; }
        .order-summary-item-title {
            color: var(--text-heading);
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .order-summary-item-meta { color: var(--text-muted); font-size: 0.8rem; margin-top: 3px; }
        .order-summary-item-price { color: var(--primary); font-weight: 700; white-space: nowrap; }
        .order-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0 12px;
            border-top: 1px solid var(--border-color);
            margin-top: 10px;
        }
        .order-total-label { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        .order-total-amount { color: var(--primary); font-size: 1.4rem; font-weight: 800; }

        .checkout-submit-btn {
            width: 100%;
            padding: 16px;
            font-size: 1.05rem;
            font-weight: 700;
            margin-top: 10px;
        }
        .checkout-submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .empty-cart-msg {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-cart-msg i { font-size: 3rem; margin-bottom: 15px; display: block; color: var(--border-color); }

        /* Security badges */
        .secure-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .secure-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .secure-badge i { color: #22c55e; }

        @media (max-width: 768px) {
            .checkout-wrapper { grid-template-columns: 1fr; margin-top: 80px; }
            .form-row { grid-template-columns: 1fr; }
            .payment-options { grid-template-columns: 1fr; }
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
        <div class="cbt-circle cbt-circle-left"></div>
        <div class="cbt-circle cbt-circle-right"></div>
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
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="../index.php" class="cbt-nav-link">Store</a></li>
                <li><a href="../cart/index.html" class="cbt-nav-link">My Cart</a></li>
                <li><a href="index.php" class="cbt-nav-link active">Checkout</a></li>
            </ul>
            <div class="cbt-nav-right">
                <a href="../cart/index.html" class="cbt-nav-cart-btn" aria-label="Cart">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <span class="cbt-cart-counter" style="display:none;">0</span>
                </a>
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn" aria-label="Menu">
                    <span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- CHECKOUT CONTENT -->
    <div class="checkout-wrapper" id="checkoutWrapper">
        <!-- Loading state -->
        <div style="grid-column:1/-1; text-align:center; padding:80px; color:var(--text-muted);">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--primary);"></i>
            <p style="margin-top:15px;">Loading your cart...</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../courses/js/data.js"></script>
    <script src="../js/data.js"></script>
    <script src="../../courses/js/cart.js"></script>
    <script>
    (function() {
        const UPI_ID = 'tushpendrakumar@okicici';
        const MERCHANT_NAME = 'CodeByTushu';

        const wrapper = document.getElementById('checkoutWrapper');

        // Cart counter
        function updateCartCount() {
            const counters = document.querySelectorAll('.cbt-cart-counter');
            const cart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
            let total = 0;
            cart.forEach(i => total += (i.quantity || 1));
            counters.forEach(el => { el.textContent = total; el.style.display = total > 0 ? 'flex' : 'none'; });
        }
        updateCartCount();

        // Load cart
        const cartItems = JSON.parse(localStorage.getItem('cbt_cart')) || [];
        const storeItems = cartItems.filter(i => !i.isCourse);

        if (storeItems.length === 0) {
            wrapper.innerHTML = `
                <div style="grid-column:1/-1;" class="empty-cart-msg">
                    <i class="material-symbols-rounded">shopping_cart</i>
                    <h2 style="color:var(--text-heading); margin-bottom:10px;">Your cart is empty</h2>
                    <p style="margin-bottom:20px;">Add some products before checking out.</p>
                    <a href="../index.php" class="cbt-btn cbt-btn-primary">Browse Store</a>
                </div>`;
            return;
        }

        // Calculate total
        let grandTotal = 0;
        storeItems.forEach(i => grandTotal += i.price * (i.quantity || 1));

        // Build order summary HTML
        let summaryItemsHTML = storeItems.map(item => {
            const fixPath = p => p && p.startsWith('../') ? '../' + p : (p || '');
            const imgSrc = fixPath(item.image);
            return `
                <div class="order-summary-item">
                    <img src="${imgSrc}" alt="${item.title}" onerror="this.style.display='none'">
                    <div class="order-summary-item-info">
                        <div class="order-summary-item-title">${item.title}</div>
                        <div class="order-summary-item-meta">${item.category} &bull; Qty: ${item.quantity || 1}</div>
                    </div>
                    <div class="order-summary-item-price">₹${item.price * (item.quantity || 1)}</div>
                </div>`;
        }).join('');

        // UPI QR code
        const upiLink = `upi://pay?pa=${UPI_ID}&pn=${encodeURIComponent(MERCHANT_NAME)}&am=${grandTotal}&cu=INR`;
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(upiLink)}`;

        wrapper.innerHTML = `
            <!-- LEFT: Shipping Form -->
            <div>
                <div class="checkout-card">
                    <h2><i class="fa-solid fa-location-dot" style="color:var(--primary)"></i> Shipping Details</h2>
                    <form id="checkoutForm" novalidate>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" id="shipping_name" placeholder="Your full name" required value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" id="shipping_phone" placeholder="10-digit mobile number" required maxlength="10">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Street Address *</label>
                            <input type="text" id="shipping_address" placeholder="House no., street, area" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" id="shipping_city" placeholder="City" required>
                            </div>
                            <div class="form-group">
                                <label>State *</label>
                                <input type="text" id="shipping_state" placeholder="State" required>
                            </div>
                        </div>
                        <div class="form-group" style="max-width:200px;">
                            <label>PIN Code *</label>
                            <input type="text" id="shipping_pincode" placeholder="6-digit PIN" required maxlength="6">
                        </div>
                    </form>
                </div>

                <div class="checkout-card" style="margin-top:20px;">
                    <h2><i class="fa-solid fa-credit-card" style="color:var(--primary)"></i> Payment Method</h2>

                    <div class="payment-options">
                        <label class="payment-option selected" id="opt-upi">
                            <input type="radio" name="payment" value="upi" checked>
                            <div>
                                <div class="pay-label">📱 UPI</div>
                                <div class="pay-desc">Pay via any UPI app</div>
                            </div>
                        </label>
                        <label class="payment-option" id="opt-cod">
                            <input type="radio" name="payment" value="cod">
                            <div>
                                <div class="pay-label">🚚 Cash on Delivery</div>
                                <div class="pay-desc">Pay when delivered</div>
                            </div>
                        </label>
                    </div>

                    <!-- UPI Section -->
                    <div id="upi-section">
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:8px;">Scan QR or use UPI ID below</p>
                        <img src="${qrUrl}" alt="UPI QR Code" style="border-radius:10px; border:3px solid var(--primary);" width="180" height="180">
                        <div class="upi-id">${UPI_ID}</div>
                        <ol class="upi-steps">
                            <li>Open any UPI app (GPay, PhonePe, Paytm, etc.)</li>
                            <li>Scan QR or enter UPI ID above</li>
                            <li>Pay <strong>₹${grandTotal}</strong> and note down the UTR/transaction ID</li>
                            <li>Enter the UTR below and place your order</li>
                        </ol>
                        <div class="utr-input-wrap">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>UTR / Transaction Reference *</label>
                                <input type="text" id="payment_reference" placeholder="e.g. 123456789012" style="width:100%; box-sizing:border-box;">
                            </div>
                        </div>
                    </div>

                    <!-- COD Section (hidden by default) -->
                    <div id="cod-section" style="display:none;">
                        <span class="cod-badge"><i class="fa-solid fa-check-circle"></i> Cash on Delivery Available</span>
                        <p>Your order will be delivered to your address and you can pay cash at the time of delivery. No advance payment needed.</p>
                        <p style="margin-top:8px; font-size:0.82rem;"><strong style="color:var(--primary)">Note:</strong> COD is available for orders within India only.</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Order Summary -->
            <div>
                <div class="checkout-card" style="position:sticky; top:90px;">
                    <h2><i class="fa-solid fa-bag-shopping" style="color:var(--primary)"></i> Order Summary</h2>
                    ${summaryItemsHTML}
                    <div class="order-total-row">
                        <span class="order-total-label">Total Amount</span>
                        <span class="order-total-amount">₹${grandTotal}</span>
                    </div>
                    <button class="cbt-btn cbt-btn-primary checkout-submit-btn" id="placeOrderBtn" onclick="placeOrder()">
                        Place Order →
                    </button>
                    <div class="secure-badges">
                        <span class="secure-badge"><i class="fa-solid fa-lock"></i> Secure Checkout</span>
                        <span class="secure-badge"><i class="fa-solid fa-shield-halved"></i> Safe & Trusted</span>
                        <span class="secure-badge"><i class="fa-solid fa-truck"></i> Fast Delivery</span>
                    </div>
                </div>
            </div>
        `;

        // Payment toggle logic
        document.querySelectorAll('input[name="payment"]').forEach(radio => {
            radio.addEventListener('change', () => {
                const isUPI = radio.value === 'upi';
                document.getElementById('upi-section').style.display = isUPI ? 'block' : 'none';
                document.getElementById('cod-section').style.display = isUPI ? 'none' : 'block';
                document.getElementById('opt-upi').classList.toggle('selected', isUPI);
                document.getElementById('opt-cod').classList.toggle('selected', !isUPI);
            });
        });

        // Hamburger
        const ham = document.getElementById('cbt-hamburger-btn');
        const nav = document.getElementById('cbt-center-nav');
        if (ham && nav) ham.addEventListener('click', () => nav.classList.toggle('show'));
    })();

    function placeOrder() {
        const btn = document.getElementById('placeOrderBtn');
        const paymentMethod = document.querySelector('input[name="payment"]:checked').value;

        // Collect form values
        const shipping = {
            name:    document.getElementById('shipping_name')?.value.trim(),
            phone:   document.getElementById('shipping_phone')?.value.trim(),
            address: document.getElementById('shipping_address')?.value.trim(),
            city:    document.getElementById('shipping_city')?.value.trim(),
            state:   document.getElementById('shipping_state')?.value.trim(),
            pincode: document.getElementById('shipping_pincode')?.value.trim()
        };
        const utr = document.getElementById('payment_reference')?.value.trim();

        // Validation
        for (const [key, val] of Object.entries(shipping)) {
            if (!val) {
                showToast(`Please fill in your ${key.replace('_',' ')}.`, 'error');
                return;
            }
        }
        if (!/^\d{10}$/.test(shipping.phone)) {
            showToast('Please enter a valid 10-digit phone number.', 'error');
            return;
        }
        if (!/^\d{6}$/.test(shipping.pincode)) {
            showToast('Please enter a valid 6-digit PIN code.', 'error');
            return;
        }
        if (paymentMethod === 'upi' && !utr) {
            showToast('Please enter the UTR / Transaction reference ID after payment.', 'error');
            return;
        }

        // Get cart items
        const cartItems = JSON.parse(localStorage.getItem('cbt_cart')) || [];
        const storeItems = cartItems.filter(i => !i.isCourse);

        if (storeItems.length === 0) {
            showToast('Your cart is empty!', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Placing Order...';

        fetch('/api/store/checkout.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: storeItems,
                shipping: shipping,
                payment_method: paymentMethod,
                payment_reference: utr || null
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Clear store items from cart
                const allCart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
                const remaining = allCart.filter(i => i.isCourse);
                localStorage.setItem('cbt_cart', JSON.stringify(remaining));
                window.dispatchEvent(new Event('cartUpdated'));

                // Redirect to success page
                window.location.href = 'success.php?order=' + encodeURIComponent(data.order_number) + '&method=' + paymentMethod;
            } else {
                showToast(data.message || 'Failed to place order. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Place Order →';
            }
        })
        .catch(() => {
            showToast('Network error. Please check your connection and try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Place Order →';
        });
    }
    </script>
    <script src="/scroll-to-top.js"></script>
</body>
</html>
SERVER['DOCUMENT_ROOT'] . '/includes/ai-widget-loader.php'; ?>
</body>
</html>

