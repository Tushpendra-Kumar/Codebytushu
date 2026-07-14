<?php
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Support Leetcode Daily by CodeByTushu â€” Buy a boba!">
    <title>Support Leetcode Daily | CodeByTushu</title>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         FAVICON â€” root-level, cache-busted (consistent with all pages)
         PHP MIGRATION NOTE: In PHP, these will move into includes/head.php
         â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <link rel="icon"             href="/favicon.ico?v=6"                 sizes="any">
    <link rel="icon"             href="/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
    <link rel="icon"             href="/favicon-48x48.png?v=6"           type="image/png" sizes="48x48">
    <link rel="icon"             href="/favicon-16x16.png?v=6"           type="image/png" sizes="16x16">
    <link rel="icon"             href="/android-chrome-192x192.png?v=6"  type="image/png" sizes="192x192">
    <link rel="icon"             href="/android-chrome-512x512.png?v=6"  type="image/png" sizes="512x512">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=6"        sizes="180x180">
    <link rel="manifest"         href="/site.webmanifest?v=6">
    <meta name="theme-color"     content="#ffc400">

    <!-- Theme init â€” prevents flash. NOTE: donate.html uses its own
         inline dark/light toggle (body class) which is separate from
         the global data-theme system. Both coexist safely. -->
    <script src="/theme.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Main Website Styling System (root-relative) -->
    <link rel="stylesheet" href="/styles.css?v=40">

    <!-- LeetCode Specific Overrides (root-relative) -->
    <link rel="stylesheet" href="/Leetcode/CSS/style.css">
    <link rel="stylesheet" href="/Leetcode/CSS/donate.css">
</head>
<body class="dark-mode">

    <!-- GLOBAL CONTINUOUS PREMIUM BACKGROUND -->
    <div class="cbt-hero-bg" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1;">
        <div class="cbt-glow-center"></div>
        <div class="cbt-streak cbt-streak-1"></div>
        <div class="cbt-streak cbt-streak-2"></div>
        <div class="cbt-streak cbt-streak-3"></div>
        <div class="cbt-streak cbt-streak-4"></div>
        <div class="cbt-circle cbt-circle-left"></div>
        <div class="cbt-circle cbt-circle-right"></div>
        <div class="cbt-dots cbt-dots-top-left"></div>
        <div class="cbt-dots cbt-dots-bottom-right"></div>
        <div class="cbt-particles">
            <span class="cbt-particle p-1"></span>
            <span class="cbt-particle p-2"></span>
            <span class="cbt-particle p-3"></span>
            <span class="cbt-particle p-4"></span>
            <span class="cbt-particle p-5"></span>
            <span class="cbt-particle p-6"></span>
            <span class="cbt-particle p-7"></span>
            <span class="cbt-particle p-8"></span>
            <span class="cbt-particle p-9"></span>
            <span class="cbt-particle p-10"></span>
        </div>
    </div>

    <!-- ===== CURRENCY SIDEBAR ===== -->
    <div class="currency-sidebar" id="currencySidebar">
        <div class="currency-option active" data-currency="INR" data-symbol="₹" data-rate="1" data-base="10">
            <span class="c-symbol">₹</span><span class="c-name">INR</span>
        </div>
        <div class="currency-option" data-currency="USD" data-symbol="$" data-rate="0.012" data-base="1">
            <span class="c-symbol">$</span><span class="c-name">USD</span>
        </div>
        <div class="currency-option" data-currency="AUD" data-symbol="$" data-rate="0.018" data-base="1">
            <span class="c-symbol">$</span><span class="c-name">AUD</span>
        </div>
        <div class="currency-option" data-currency="GBP" data-symbol="£" data-rate="0.0095" data-base="1">
            <span class="c-symbol">£</span><span class="c-name">GBP</span>
        </div>
        <div class="currency-option" data-currency="CAD" data-symbol="$" data-rate="0.016" data-base="1">
            <span class="c-symbol">$</span><span class="c-name">CAD</span>
        </div>
        <div class="currency-option" data-currency="MYR" data-symbol="RM" data-rate="0.056" data-base="1">
            <span class="c-symbol">RM</span><span class="c-name">MYR</span>
        </div>
        <div class="currency-option" data-currency="MXN" data-symbol="$" data-rate="0.2" data-base="1">
            <span class="c-symbol">$</span><span class="c-name">MXN</span>
        </div>
        <div class="currency-option" data-currency="SGD" data-symbol="$" data-rate="0.016" data-base="1">
            <span class="c-symbol">$</span><span class="c-name">SGD</span>
        </div>
        <div class="currency-option" data-currency="PLN" data-symbol="zł" data-rate="0.048" data-base="1">
            <span class="c-symbol">zł</span><span class="c-name">PLN</span>
        </div>
    </div>

    <!-- ===== TOP LEFT CURRENCY BADGE ===== -->
    <button class="currency-badge" id="currencyBadge" onclick="toggleSidebar()">
        <span id="badgeSymbol">₹</span> <span id="badgeName">INR</span>
    </button>

    <!-- ===== TOP RIGHT THEME TOGGLE ===== -->
    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle theme"><i class="fa-solid fa-moon"></i></button>

    <!-- ===== MAIN CARD ===== -->
    <main class="donate-page">
        <div class="donate-card">

            <!-- Logo Circle -->
            <div class="brand-logo">
                <div class="logo-ring">
                    <div class="logo-inner">
                        <span class="logo-arrow"><i class="fa-solid fa-arrow-up" style="font-size:inherit;"></i></span><span class="logo-d">D</span>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <h1 class="donate-title">Leetcode Daily</h1>
            <p class="donate-subtitle">Support Leetcode Daily by buying them a boba!</p>

            <!-- Amount Selector -->
            <div class="amount-row" style="display:flex;align-items:center;justify-content:center;gap:15px;margin:30px 0;">
                <button id="btnMinus" class="qty-btn" style="width:40px;height:40px;font-size:20px;padding:0;display:flex;align-items:center;justify-content:center;">-</button>
                <div style="display:flex; align-items:center; font-size:24px; font-weight:700; color:var(--text); justify-content:center; gap:2px;">
                    <span id="currencySymbolPrefix">₹</span>
                    <input type="number" id="amountInput" value="10" min="10" style="background:transparent; border:none; color:var(--text); font-size:24px; font-weight:700; width:100px; outline:none; -moz-appearance:textfield; padding:0;">
                </div>
                <button id="btnPlus" class="qty-btn" style="width:40px;height:40px;font-size:20px;padding:0;display:flex;align-items:center;justify-content:center;">+</button>
            </div>

            <!-- Support Button -->
            <button class="support-btn" id="supportBtn">
                Support <span id="supportAmount">₹100</span>
            </button>

            <p class="secure-note"><i class="fa-solid fa-lock"></i> Zero transaction fees via direct UPI</p>
        </div>
    </main>

    <!-- ===== UPI QR MODAL ===== -->
    <div id="upiModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <h2 style="margin-top:0; color:var(--text); font-size:24px;">Scan & Pay</h2>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Use any UPI App (GPay, PhonePe, Paytm) to scan the QR code below.</p>
            
            <div id="qrcode" style="display:flex; justify-content:center; padding:15px; background:#fff; border-radius:10px; width:max-content; margin:0 auto 20px auto;"></div>
            
            <p style="color:var(--text); font-weight:600; font-size:18px; margin-bottom:20px;">Amount: <span id="modalAmount">₹100</span></p>
            
            <!-- Deep link for Mobile Users -->
            <a href="#" id="upiDeepLink" class="support-btn" style="text-decoration:none; display:inline-block;">Open UPI App</a>
        </div>
    </div>

    <!-- qrcode.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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

        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px);
            display: flex; align-items: center; justify-content: center;
            z-index: 10000; opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .modal-overlay.show { opacity: 1; pointer-events: all; }
        
        .modal-content {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: 16px; padding: 30px; text-align: center;
            max-width: 400px; width: 90%; position: relative;
            transform: translateY(20px); transition: transform 0.3s;
        }
        .modal-overlay.show .modal-content { transform: translateY(0); }

        .modal-close {
            position: absolute; top: 15px; right: 15px;
            background: transparent; border: none; color: var(--text-muted);
            font-size: 28px; cursor: pointer; line-height: 1;
            transition: color 0.2s;
        }
        .modal-close:hover { color: var(--accent); }
        
        /* Hide spin buttons for number input */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
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
        let currentCurrency = { symbol: '₹', rate: 1, base: 10, name: 'INR' };
        let currentAmount = 10;

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
                document.getElementById('currencySymbolPrefix').textContent = currentCurrency.symbol;

                document.getElementById('currencySidebar').classList.remove('open');
                
                // Reset amount to the new currency's base
                currentAmount = currentCurrency.base;
                document.getElementById('amountInput').value = currentAmount;
                document.getElementById('amountInput').min = currentCurrency.base;
                updateAmount();
            });
        });

        // ---- Amount Input Handling ----
        const amountInput = document.getElementById('amountInput');
        
        amountInput.addEventListener('input', function() {
            let val = parseInt(this.value, 10);
            if (!isNaN(val)) {
                currentAmount = val;
                updateAmount();
            }
        });

        amountInput.addEventListener('blur', function() {
            let val = parseInt(this.value, 10);
            if (isNaN(val) || val < currentCurrency.base) {
                currentAmount = currentCurrency.base;
            } else {
                currentAmount = val;
            }
            this.value = currentAmount;
            updateAmount();
        });

        // ---- Qty buttons (Step Selector) ----
        document.getElementById('btnMinus').addEventListener('click', function () {
            if (currentAmount > currentCurrency.base) {
                currentAmount = Math.max(currentCurrency.base, currentAmount - currentCurrency.base);
                amountInput.value = currentAmount;
                updateAmount();
            }
        });

        document.getElementById('btnPlus').addEventListener('click', function () {
            currentAmount += currentCurrency.base;
            amountInput.value = currentAmount;
            updateAmount();
        });

        // ---- Update support button amount ----
        function updateAmount() {
            const finalAmount = currentAmount.toFixed(currentCurrency.base < 10 ? 2 : 0);
            const formatted = currentCurrency.symbol + finalAmount;
            document.getElementById('supportAmount').textContent = formatted;
        }

        // ---- Modal Logic ----
        const modal = document.getElementById('upiModal');
        const closeModal = document.getElementById('closeModal');
        let qrCodeInstance = null;

        closeModal.addEventListener('click', () => modal.classList.remove('show'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('show');
        });

        // ---- Support button click (UPI QR Generation) ----
        document.getElementById('supportBtn').addEventListener('click', function () {
            // Strict validation before proceeding
            if (isNaN(currentAmount) || currentAmount < currentCurrency.base) {
                currentAmount = currentCurrency.base;
                amountInput.value = currentAmount;
                updateAmount();
            }
            
            // Calculate exact INR value
            const inrAmount = (currentAmount / currentCurrency.rate).toFixed(2);
            
            const upiId = 'tushpendrakumar@okicici';
            const name = 'Tushpendra Kumar';
            
            // Construct standard UPI intent URL
            const upiUrl = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(name)}&am=${inrAmount}&cu=INR`;

            // Update Modal UI
            document.getElementById('modalAmount').textContent = `₹${inrAmount}`;
            document.getElementById('upiDeepLink').href = upiUrl;

            // Generate QR Code
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = ''; // Clear previous
            
            if (qrCodeInstance) {
                qrCodeInstance.clear();
            }
            
            qrCodeInstance = new QRCode(qrContainer, {
                text: upiUrl,
                width: 200,
                height: 200,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // Show Modal
            modal.classList.add('show');
        });

        // ---- Theme toggle ----
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            document.body.classList.toggle('light-mode');
            document.getElementById('themeToggle').innerHTML =
                document.body.classList.contains('dark-mode') ? '<i class="fa-solid fa-moon"></i>' : '<i class="fa-solid fa-sun"></i>';
        }
    </script>
    <script src="/back-home.js"></script>
</body>
</html>