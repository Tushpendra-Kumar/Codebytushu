<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Support Leetcode Daily by CodeByTushu — Buy a boba!">
    <title>Support Leetcode Daily | CodeByTushu</title>

    <!-- ══════════════════════════════════════════════════════════
         FAVICON — root-level, cache-busted (consistent with all pages)
         PHP MIGRATION NOTE: In PHP, these will move into includes/head.php
         ══════════════════════════════════════════════════════════ -->
    <link rel="icon"             href="/favicon.ico?v=6"                 sizes="any">
    <link rel="icon"             href="/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
    <link rel="icon"             href="/favicon-48x48.png?v=6"           type="image/png" sizes="48x48">
    <link rel="icon"             href="/favicon-16x16.png?v=6"           type="image/png" sizes="16x16">
    <link rel="icon"             href="/android-chrome-192x192.png?v=6"  type="image/png" sizes="192x192">
    <link rel="icon"             href="/android-chrome-512x512.png?v=6"  type="image/png" sizes="512x512">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=6"        sizes="180x180">
    <link rel="manifest"         href="/site.webmanifest?v=6">
    <meta name="theme-color"     content="#ffc400">

    <!-- Theme init — prevents flash. NOTE: donate.html uses its own
         inline dark/light toggle (body class) which is separate from
         the global data-theme system. Both coexist safely. -->
    <script src="/theme.js"></script>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/donate.css">
</head>
<body class="dark-mode">

    <!-- ===== CURRENCY SIDEBAR ===== -->
    <div class="currency-sidebar" id="currencySidebar">
        <div class="currency-option active" data-currency="INR" data-symbol="₹" data-rate="1" data-base="100">
            <span class="c-symbol">₹</span><span class="c-name">INR</span>
        </div>
        <div class="currency-option" data-currency="USD" data-symbol="$" data-rate="0.012" data-base="2">
            <span class="c-symbol">$</span><span class="c-name">USD</span>
        </div>
        <div class="currency-option" data-currency="AUD" data-symbol="$" data-rate="0.018" data-base="2">
            <span class="c-symbol">$</span><span class="c-name">AUD</span>
        </div>
        <div class="currency-option" data-currency="GBP" data-symbol="£" data-rate="0.0095" data-base="1">
            <span class="c-symbol">£</span><span class="c-name">GBP</span>
        </div>
        <div class="currency-option" data-currency="CAD" data-symbol="$" data-rate="0.016" data-base="2">
            <span class="c-symbol">$</span><span class="c-name">CAD</span>
        </div>
        <div class="currency-option" data-currency="MYR" data-symbol="RM" data-rate="0.056" data-base="5">
            <span class="c-symbol">RM</span><span class="c-name">MYR</span>
        </div>
        <div class="currency-option" data-currency="MXN" data-symbol="$" data-rate="0.2" data-base="20">
            <span class="c-symbol">$</span><span class="c-name">MXN</span>
        </div>
        <div class="currency-option" data-currency="SGD" data-symbol="$" data-rate="0.016" data-base="2">
            <span class="c-symbol">$</span><span class="c-name">SGD</span>
        </div>
        <div class="currency-option" data-currency="PLN" data-symbol="zł" data-rate="0.048" data-base="5">
            <span class="c-symbol">zł</span><span class="c-name">PLN</span>
        </div>
    </div>

    <!-- ===== TOP LEFT CURRENCY BADGE ===== -->
    <button class="currency-badge" id="currencyBadge" onclick="toggleSidebar()">
        <span id="badgeSymbol">₹</span> <span id="badgeName">INR</span>
    </button>

    <!-- ===== TOP RIGHT THEME TOGGLE ===== -->
    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle theme">🌙</button>

    <!-- ===== MAIN CARD ===== -->
    <main class="donate-page">
        <div class="donate-card">

            <!-- Logo Circle -->
            <div class="brand-logo">
                <div class="logo-ring">
                    <div class="logo-inner">
                        <span class="logo-arrow">←</span><span class="logo-d">D</span>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <h1 class="donate-title">Leetcode Daily</h1>
            <p class="donate-subtitle">Support Leetcode Daily by buying them a boba!</p>

            <!-- Amount Selector -->
            <div class="amount-row" style="display:flex;align-items:center;justify-content:center;gap:15px;margin:30px 0;">
                <button id="btnMinus" class="qty-btn" style="width:40px;height:40px;font-size:20px;padding:0;display:flex;align-items:center;justify-content:center;">-</button>
                <div style="font-size:24px;font-weight:700;color:var(--text);min-width:100px;text-align:center;">
                    <span id="displayAmount">₹100</span>
                </div>
                <button id="btnPlus" class="qty-btn" style="width:40px;height:40px;font-size:20px;padding:0;display:flex;align-items:center;justify-content:center;">+</button>
            </div>

            <!-- Support Button -->
            <button class="support-btn" id="supportBtn">
                Support <span id="supportAmount">₹100</span>
            </button>

            <p class="secure-note">🔒 Secure payment via Razorpay</p>
        </div>
    </main>

    <!-- Razorpay Checkout Script -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- Simple Toast Notification CSS for Payment Status -->
    <style>
        .toast {
            position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 8px;
            color: #fff; font-weight: 600; font-family: sans-serif; opacity: 0;
            transform: translateY(-20px); transition: opacity 0.3s, transform 0.3s; z-index: 9999;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.success { background: #22c55e; }
        .toast.error { background: #ef4444; }
    </style>
    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => { toast.classList.remove('show'); }, 4000);
        }

        // ---- State ----
        let currentCurrency = { symbol: '₹', rate: 1, base: 100, name: 'INR' };
        let currentQty = 1;

        // ---- Sidebar toggle ----
        function toggleSidebar() {
            document.getElementById('currencySidebar').classList.toggle('open');
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('currencySidebar');
            const badge = document.getElementById('currencyBadge');
            if (!sidebar.contains(e.target) && !badge.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });

        // ---- Currency selection ----
        document.querySelectorAll('.currency-option').forEach(opt => {
            opt.addEventListener('click', function () {
                document.querySelectorAll('.currency-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');

                currentCurrency = {
                    symbol: this.dataset.symbol,
                    rate:   parseFloat(this.dataset.rate),
                    base:   parseFloat(this.dataset.base),
                    name:   this.dataset.currency
                };

                document.getElementById('badgeSymbol').textContent = currentCurrency.symbol;
                document.getElementById('badgeName').textContent   = currentCurrency.name;

                document.getElementById('currencySidebar').classList.remove('open');
                updateAmount();
            });
        });

        // ---- Qty buttons (Step Selector) ----
        document.getElementById('btnMinus').addEventListener('click', function () {
            if (currentQty > 1) {
                currentQty--;
                updateAmount();
            }
        });

        document.getElementById('btnPlus').addEventListener('click', function () {
            currentQty++;
            updateAmount();
        });

        // ---- Update support button amount ----
        function updateAmount() {
            const amount = (currentCurrency.base * currentQty).toFixed(currentCurrency.base < 10 ? 2 : 0);
            const formatted = currentCurrency.symbol + amount;
            document.getElementById('displayAmount').textContent = formatted;
            document.getElementById('supportAmount').textContent = formatted;
        }

        // ---- Support button click (Razorpay Integration) ----
        document.getElementById('supportBtn').addEventListener('click', async function () {
            const supportBtn = this;
            const originalText = supportBtn.innerHTML;
            
            // For Razorpay, we process the exact INR value regardless of the selected display currency.
            // This ensures they pay the equivalent amount in INR.
            const inrAmount = Math.round((currentCurrency.base * currentQty) / currentCurrency.rate);

            supportBtn.disabled = true;
            supportBtn.innerHTML = 'Processing...';

            try {
                // 1. Create Order on Backend
                const response = await fetch('/api/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount: inrAmount })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to create order.');
                }

                // 2. Open Razorpay Checkout
                const options = {
                    "key": data.key_id,
                    "amount": data.amount, // in paise
                    "currency": data.currency,
                    "name": "CodeByTushu",
                    "description": "Support Leetcode Daily",
                    "order_id": data.order_id,
                    "theme": {
                        "color": "#ffc400"
                    },
                    "handler": async function (response) {
                        // 3. Verify Payment on Backend
                        try {
                            const verifyRes = await fetch('/api/verify_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature
                                })
                            });
                            const verifyData = await verifyRes.json();
                            if (verifyData.success) {
                                showToast('Payment successful! Thank you for your support. 🎉', 'success');
                            } else {
                                showToast('Payment verification failed.', 'error');
                            }
                        } catch (err) {
                            showToast('Error verifying payment.', 'error');
                        }
                    }
                };
                
                const rzp = new Razorpay(options);
                rzp.on('payment.failed', function (response){
                    showToast('Payment failed or cancelled.', 'error');
                });
                rzp.open();
                
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                supportBtn.disabled = false;
                supportBtn.innerHTML = originalText;
            }
        });

        // ---- Theme toggle ----
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            document.body.classList.toggle('light-mode');
            document.getElementById('themeToggle').textContent =
                document.body.classList.contains('dark-mode') ? '🌙' : '☀️';
        }
    </script>
    <script src="/back-home.js"></script>
</body>
</html>