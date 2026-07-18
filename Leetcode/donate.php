<?php
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Support CodeByTushu — Help keep programming education free for everyone.">
    <title>Support CodeByTushu | Donate</title>

    <!-- Favicon -->
    <link rel="icon"             href="/favicon.ico?v=6"                 sizes="any">
    <link rel="icon"             href="/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
    <link rel="icon"             href="/favicon-16x16.png?v=6"           type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=6"        sizes="180x180">
    <link rel="manifest"         href="/site.webmanifest?v=6">
    <meta name="theme-color"     content="#ffc400">

    <!-- Theme init -->
    <script src="/theme.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Styles -->
    <link rel="stylesheet" href="/styles.css?v=40">
    <link rel="stylesheet" href="/Leetcode/CSS/style.css">
    <link rel="stylesheet" href="/Leetcode/CSS/donate.css?v=5">
</head>
<body class="dark-mode">

    <!-- ===== ANIMATED PREMIUM BACKGROUND ===== -->
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
        <div class="currency-option" data-currency="AUD" data-symbol="A$" data-rate="0.018" data-base="1">
            <span class="c-symbol">A$</span><span class="c-name">AUD</span>
        </div>
        <div class="currency-option" data-currency="GBP" data-symbol="£" data-rate="0.0095" data-base="1">
            <span class="c-symbol">£</span><span class="c-name">GBP</span>
        </div>
        <div class="currency-option" data-currency="CAD" data-symbol="C$" data-rate="0.016" data-base="1">
            <span class="c-symbol">C$</span><span class="c-name">CAD</span>
        </div>
        <div class="currency-option" data-currency="EUR" data-symbol="€" data-rate="0.011" data-base="1">
            <span class="c-symbol">€</span><span class="c-name">EUR</span>
        </div>
        <div class="currency-option" data-currency="SGD" data-symbol="S$" data-rate="0.016" data-base="1">
            <span class="c-symbol">S$</span><span class="c-name">SGD</span>
        </div>
        <div class="currency-option" data-currency="MYR" data-symbol="RM" data-rate="0.056" data-base="1">
            <span class="c-symbol">RM</span><span class="c-name">MYR</span>
        </div>
    </div>

    <!-- ===== CURRENCY BADGE (Top Left) ===== -->
    <button class="currency-badge" id="currencyBadge" onclick="toggleSidebar()" aria-label="Select currency">
        <i class="fa-solid fa-indian-rupee-sign" id="badgeIcon" style="font-size:11px;"></i>
        <span id="badgeName">INR</span>
    </button>

    <!-- ===== MAIN PAGE WRAPPER ===== -->
    <div class="donate-page-wrapper">

        <!-- ===== DONATE CARD ===== -->
        <div class="donate-card" role="main">

            <!-- Logo Circle -->
            <div class="brand-logo">
                <div class="logo-ring">
                    <div class="logo-inner">
                        <span class="logo-arrow"><i class="fa-solid fa-arrow-up"></i></span><span class="logo-d">D</span>
                    </div>
                </div>
            </div>

            <!-- Support Us Badge -->
            <div class="support-badge">SUPPORT US</div>

            <!-- Heading -->
            <h1 class="donate-heading">
                Support<br>
                <span class="gold">CodeByTushu</span>
            </h1>

            <!-- Description -->
            <p class="donate-desc">
                CodeByTushu is dedicated to providing high-quality programming content, daily LeetCode solutions, and practical development resources — completely free for everyone.
            </p>

            <!-- Feature Cards -->
            <div class="features-row" aria-label="Why your support matters">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div class="feature-title">Free Content</div>
                    <div class="feature-desc">Always free, always will be.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="feature-title">Daily Updates</div>
                    <div class="feature-desc">New LeetCode solutions every single day.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-code"></i></div>
                    <div class="feature-title">Built for You</div>
                    <div class="feature-desc">Made to help you crack interviews and grow.</div>
                </div>
            </div>

            <!-- Heart Divider -->
            <div class="heart-divider" aria-hidden="true">
                <i class="fa-regular fa-heart"></i>
            </div>

            <!-- Choose Amount Label -->
            <p class="choose-label">Choose an amount</p>

            <!-- Amount Selector -->
            <div class="amount-row">
                <button id="btnMinus" class="qty-btn" aria-label="Decrease amount">&#8722;</button>
                <div class="amount-display">
                    <span class="amount-symbol" id="currencySymbolPrefix">₹</span>
                    <input
                        type="number"
                        id="amountInput"
                        value="10"
                        min="10"
                        aria-label="Donation amount"
                    >
                </div>
                <button id="btnPlus" class="qty-btn" aria-label="Increase amount">+</button>
            </div>

            <!-- Support Button -->
            <button class="support-btn" id="supportBtn" aria-label="Proceed to payment">
                Support <span id="supportAmount">₹10</span>
            </button>

            <!-- Secure Notes -->
            <div class="secure-notes">
                <p class="secure-note">
                    <i class="fa-solid fa-lock"></i>
                    Secure payment via direct UPI
                </p>
                <p class="secure-note">
                    <i class="fa-solid fa-circle-check"></i>
                    Zero transaction fees via UPI
                </p>
            </div>

        </div><!-- /.donate-card -->

        <!-- ===== FOOTER ===== -->
        <footer class="donate-footer" role="contentinfo">

            <div class="footer-heart-wrap">
                <div class="footer-heart-icon">
                    <i class="fa-regular fa-heart"></i>
                </div>
            </div>

            <p class="footer-thankyou">Thank you for supporting free education.</p>
            <p class="footer-sub">
                Your support helps thousands of developers<br>
                learn and grow every single day.
            </p>

            <div class="footer-divider"></div>

            <p class="footer-copy">
                &copy; 2025 <a href="/" aria-label="CodeByTushu homepage">CodeByTushu</a> &nbsp;&bull;&nbsp; All Rights Reserved
            </p>

            <nav class="footer-links" aria-label="Footer navigation">
                <a href="/privacy-policy/" class="footer-link" id="footer-privacy">
                    <i class="fa-solid fa-shield-halved"></i>
                    Privacy Policy
                </a>
                <span class="footer-dot" aria-hidden="true">&#x2022;</span>
                <a href="/terms/" class="footer-link" id="footer-terms">
                    <i class="fa-regular fa-file-lines"></i>
                    Terms of Service
                </a>
                <span class="footer-dot" aria-hidden="true">&#x2022;</span>
                <a href="mailto:codebytushu@gmail.com" class="footer-link" id="footer-contact">
                    <i class="fa-regular fa-envelope"></i>
                    Contact Us
                </a>
            </nav>

        </footer>

    </div><!-- /.donate-page-wrapper -->

    <!-- ===== UPI QR MODAL ===== -->
    <div id="upiModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
            <button class="modal-close" id="closeModal" aria-label="Close modal">&times;</button>
            <p class="modal-title" id="modalTitle">Scan &amp; Pay</p>
            <p class="modal-subtitle">Use GPay, PhonePe, Paytm or any UPI app to scan the QR below.</p>

            <div id="qrcode"></div>

            <p class="modal-amount-display">
                Amount: <span id="modalAmount">₹10</span>
            </p>

            <!-- Deep link for mobile users -->
            <a href="#" id="upiDeepLink" class="modal-open-btn" role="button">
                <i class="fa-solid fa-mobile-screen-button" style="margin-right:6px;"></i>
                Open UPI App
            </a>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast" role="alert" aria-live="polite"></div>

    <!-- QR Code library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
    /* =============================================
       DONATE PAGE — Full JS
       ============================================= */

    // ---- Toast ----
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(() => { toast.classList.remove('show'); }, 4000);
    }

    // ---- State ----
    let currentCurrency = { symbol: '₹', rate: 1, base: 10, name: 'INR' };
    let currentAmount   = 10;

    // ---- Sidebar toggle ----
    function toggleSidebar() {
        document.getElementById('currencySidebar').classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('currencySidebar');
        const badge   = document.getElementById('currencyBadge');
        if (sidebar && badge && !sidebar.contains(e.target) && !badge.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    // ---- Currency icons map ----
    const currencyIcons = {
        INR: 'fa-indian-rupee-sign',
        USD: 'fa-dollar-sign',
        AUD: 'fa-dollar-sign',
        GBP: 'fa-sterling-sign',
        CAD: 'fa-dollar-sign',
        EUR: 'fa-euro-sign',
        SGD: 'fa-dollar-sign',
        MYR: 'fa-dollar-sign',
    };

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

            const icon = currencyIcons[currentCurrency.name] || 'fa-dollar-sign';
            document.getElementById('badgeIcon').className = `fa-solid ${icon}`;
            document.getElementById('badgeName').textContent = currentCurrency.name;
            document.getElementById('currencySymbolPrefix').textContent = currentCurrency.symbol;

            document.getElementById('currencySidebar').classList.remove('open');

            currentAmount = currentCurrency.base;
            document.getElementById('amountInput').value  = currentAmount;
            document.getElementById('amountInput').min    = currentCurrency.base;
            updateAmountDisplay();
        });
    });

    // ---- Amount input ----
    const amountInput = document.getElementById('amountInput');

    amountInput.addEventListener('input', function () {
        const val = parseFloat(this.value);
        if (!isNaN(val) && val > 0) {
            currentAmount = val;
            updateAmountDisplay();
        }
    });

    amountInput.addEventListener('blur', function () {
        let val = parseFloat(this.value);
        if (isNaN(val) || val < currentCurrency.base) {
            currentAmount = currentCurrency.base;
        } else {
            currentAmount = val;
        }
        this.value = currentAmount;
        updateAmountDisplay();
    });

    // ---- +/- Buttons ----
    document.getElementById('btnMinus').addEventListener('click', function () {
        if (currentAmount > currentCurrency.base) {
            currentAmount = Math.max(currentCurrency.base, currentAmount - currentCurrency.base);
            amountInput.value = currentAmount;
            updateAmountDisplay();
        }
    });

    document.getElementById('btnPlus').addEventListener('click', function () {
        currentAmount += currentCurrency.base;
        amountInput.value = currentAmount;
        updateAmountDisplay();
    });

    // ---- Update support button display ----
    function updateAmountDisplay() {
        const isDecimal = currentCurrency.base < 10;
        const formatted = isDecimal
            ? currentCurrency.symbol + parseFloat(currentAmount).toFixed(2)
            : currentCurrency.symbol + Math.round(currentAmount);
        document.getElementById('supportAmount').textContent = formatted;
    }

    // ---- Modal ----
    const modal      = document.getElementById('upiModal');
    const closeModal = document.getElementById('closeModal');
    let qrCodeInstance = null;

    closeModal.addEventListener('click', () => modal.classList.remove('show'));
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.remove('show');
    });

    // ---- Support button → UPI QR ----
    document.getElementById('supportBtn').addEventListener('click', function () {
        // Validate
        if (isNaN(currentAmount) || currentAmount < currentCurrency.base) {
            currentAmount = currentCurrency.base;
            amountInput.value = currentAmount;
            updateAmountDisplay();
        }

        // Convert to INR (always integer for UPI)
        const inrAmount = Math.round(currentAmount / currentCurrency.rate);

        const upiId  = 'tushpendrakum@slc';
        const name   = 'Tushpendra Kumar';

        // Clean UPI deep link — integer amount, no decimals
        const upiUrl = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(name)}&am=${inrAmount}&cu=INR&tn=${encodeURIComponent('CodeByTushu Donation')}`;

        // Update modal
        document.getElementById('modalAmount').textContent =
            currentCurrency.symbol + (currentCurrency.base < 10 ? parseFloat(currentAmount).toFixed(2) : Math.round(currentAmount));
        document.getElementById('upiDeepLink').href = upiUrl;

        // Generate QR
        const qrContainer = document.getElementById('qrcode');
        qrContainer.innerHTML = '';
        if (qrCodeInstance) { try { qrCodeInstance.clear(); } catch(e) {} }

        qrCodeInstance = new QRCode(qrContainer, {
            text:         upiUrl,
            width:        200,
            height:       200,
            colorDark:    '#000000',
            colorLight:   '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        modal.classList.add('show');
    });

    // ---- Keyboard: Escape closes modal ----
    document.addEventListener('keydown', e => {
        if ((e.key === 'Escape' || e.keyCode === 27) && modal.classList.contains('show')) {
            modal.classList.remove('show');
        }
    });
    </script>

    <script src="/back-home.js"></script>
</body>
</html>