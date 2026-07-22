    <nav class="cbt-navbar navbar" id="mainNavbar" role="navigation" aria-label="Main navigation">
        <div class="cbt-nav-inner">

            <!-- ── Logo ───────────────────────────────────────────── -->
            <div class="cbt-logo" id="cbt-logo">
                <a href="/#home" id="cbt-logo-link" aria-label="CodeByTushu Home">
                    <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>

            <!-- ── Center Navigation (Desktop ≥1024px only) ──────── -->
            <ul class="cbt-center-nav" id="cbt-center-nav" role="menubar" aria-label="Primary navigation">
                <li role="none"><a href="/#home"                class="cbt-nav-link" id="nav-home"         role="menuitem" tabindex="0">Home</a></li>
                <li role="none"><a href="/#leetcode"            class="cbt-nav-link"        id="nav-leetcode"      role="menuitem" tabindex="0">LeetCode</a></li>
                <li role="none"><a href="/#blog"                class="cbt-nav-link"        id="nav-blogs"         role="menuitem" tabindex="0">Blogs</a></li>
                <li role="none"><a href="/#courses"             class="cbt-nav-link"        id="nav-courses"       role="menuitem" tabindex="0">Courses</a></li>
                <li role="none"><a href="/#video-editing"       class="cbt-nav-link"        id="nav-videoediting"  role="menuitem" tabindex="0">Video Editing</a></li>
                <li role="none"><a href="/#store"               class="cbt-nav-link"        id="nav-store"         role="menuitem" tabindex="0">Store</a></li>
                <li role="none"><a href="/Leetcode/donate.php" class="cbt-nav-link" id="nav-donate" role="menuitem" tabindex="0">Donate</a></li>
            </ul>

            <!-- ── Right Side: Auth + Hamburger (Desktop ≥1024px) ── -->
            <div class="cbt-nav-right" id="cbt-nav-right">
                <?php if (strpos($_SERVER['REQUEST_URI'] ?? '', '/store') !== false): ?>
                <a href="/store/cart/index.html" class="cbt-nav-cart-btn" aria-label="Cart" style="display:flex; align-items:center; color:#fff; text-decoration:none; margin-right:15px; position:relative;">
                    <span class="material-symbols-rounded" style="font-size:24px;">shopping_cart</span>
                    <span class="cbt-cart-counter" style="display:none; position:absolute; top:-5px; right:-10px; background:#ffc400; color:#000; font-size:0.7rem; font-weight:bold; padding:2px 6px; border-radius:50%;">0</span>
                </a>
                <?php endif; ?>
                <!-- Auth area - dynamically swapped by JS -->
                <div id="cbt-auth-area" style="display:inline-flex;align-items:center;gap:8px;">
                    <!-- Default: Login button (replaced if logged in) -->
                    <a href="/auth/login.php" class="cbt-login-btn" id="cbt-login-btn" aria-label="Login to your account">
                        <span>Login</span>
                    </a>
                </div>
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn"
                        aria-label="Open more links" aria-expanded="false" aria-controls="cbt-ham-panel">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>

            <!-- ── Mobile Right: Auth + Hamburger (<1024px) ─────── -->
            <div class="cbt-mobile-right" id="cbt-mobile-right">
                <div id="cbt-auth-area-mob" style="display:inline-flex;align-items:center;">
                    <a href="/auth/login.php" class="cbt-login-btn" id="cbt-login-btn-mob" aria-label="Login">
                        <span>Login</span>
                    </a>
                </div>
                <button class="cbt-mobile-ham-btn" id="cbt-mobile-ham-btn"
                        aria-label="Open mobile menu" aria-expanded="false" aria-controls="cbt-mobile-drawer">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>

        </div><!-- /.cbt-nav-inner -->

        <!-- ══ Desktop Hamburger Panel (right slide-in) ════════════ -->
        <!-- Overlay -->
        <div class="cbt-panel-overlay" id="cbt-panel-overlay" aria-hidden="true"></div>
        <!-- Panel -->
        <div class="cbt-ham-panel" id="cbt-ham-panel" role="dialog" aria-modal="true" aria-label="More links" aria-hidden="true">
            <button class="cbt-panel-close" id="cbt-panel-close" aria-label="Close menu">&#x2715;</button>
            <p class="cbt-panel-label">More</p>
            <nav class="cbt-panel-nav" aria-label="Secondary navigation">
                <a href="/#about"           class="cbt-panel-link" id="panel-about">About</a>

                <a href="privacy-policy"   class="cbt-panel-link" id="panel-privacy">Privacy Policy</a>
                <a href="terms"            class="cbt-panel-link" id="panel-terms">Terms &amp; Conditions</a>
                <a href="disclaimer"       class="cbt-panel-link" id="panel-disclaimer">Disclaimer</a>
                <a href="/#support"         class="cbt-panel-link" id="panel-support">Support</a>
            </nav>
        </div>

        <!-- ══ Mobile Full Drawer ═══════════════════════════════════ -->
        <!-- Overlay -->
        <div class="cbt-mobile-overlay" id="cbt-mobile-overlay" aria-hidden="true"></div>
        <!-- Drawer -->
        <div class="cbt-mobile-drawer" id="cbt-mobile-drawer" role="dialog" aria-modal="true" aria-label="Mobile menu" aria-hidden="true">
            <!-- Drawer Header -->
            <div class="cbt-drawer-header">
                <div class="cbt-logo">
                    <a href="/#home" aria-label="CodeByTushu Home" tabindex="-1">
                        <span class="cbt-logo-bracket">&lt;/&gt;</span>
                        <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                    </a>
                </div>
                <button class="cbt-drawer-close" id="cbt-drawer-close" aria-label="Close menu">&#x2715;</button>
            </div>
            <!-- Drawer Body -->
            <div class="cbt-drawer-body">
                <!-- Primary Links -->
                <ul class="cbt-drawer-primary" role="menu" aria-label="Main navigation">
                    <li role="none"><a href="/#home"                class="cbt-drawer-link"          id="drawer-home"        role="menuitem">Home</a></li>
                    <li role="none"><a href="/#leetcode"            class="cbt-drawer-link"          id="drawer-leetcode"    role="menuitem">LeetCode</a></li>
                    <li role="none"><a href="/#blog"                class="cbt-drawer-link"          id="drawer-blogs"       role="menuitem">Blogs</a></li>
                    <li role="none"><a href="/#courses"             class="cbt-drawer-link"          id="drawer-courses"     role="menuitem">Courses</a></li>
                    <li role="none"><a href="/#video-editing"       class="cbt-drawer-link"          id="drawer-videoediting" role="menuitem">Video Editing</a></li>
                    <li role="none"><a href="/#store"               class="cbt-drawer-link"          id="drawer-store"       role="menuitem">Store</a></li>
                    <li role="none"><a href="/Leetcode/donate.php" class="cbt-drawer-link" id="drawer-donate" role="menuitem">Donate</a></li>
                    <li role="none" id="drawer-auth-li"><a href="/auth/login.php" class="cbt-drawer-link" id="drawer-login" role="menuitem">Login</a></li>
                </ul>
                <!-- Divider -->
                <div class="cbt-drawer-divider" role="separator"></div>
                <p class="cbt-drawer-label">More</p>
                <!-- Secondary Links -->
                <ul class="cbt-drawer-secondary" role="menu" aria-label="Secondary navigation">
                    <li role="none"><a href="/#about"          class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-about"      role="menuitem">About</a></li>

                    <li role="none"><a href="privacy-policy"  class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-privacy"    role="menuitem">Privacy Policy</a></li>
                    <li role="none"><a href="terms"           class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-terms"      role="menuitem">Terms &amp; Conditions</a></li>
                    <li role="none"><a href="disclaimer"      class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-disclaimer" role="menuitem">Disclaimer</a></li>
                    <li role="none"><a href="/#support"        class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-support"    role="menuitem">Support</a></li>
                </ul>
            </div><!-- /.cbt-drawer-body -->
        </div><!-- /.cbt-mobile-drawer -->

    </nav><!-- /.cbt-navbar -->

<script src="/auth-ui.js"></script>

<!-- ══ CBT NAVBAR JS ═══════════════════════════════════ -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var cbtNav = document.getElementById('mainNavbar');
        if (cbtNav) {
            window.addEventListener('scroll', function () {
                if (window.scrollY > 20) {
                    cbtNav.classList.add('sticky');
                } else {
                    cbtNav.classList.remove('sticky');
                }
            }, { passive: true });
        }

        var deskHamBtn    = document.getElementById('cbt-hamburger-btn');
        var hamPanel      = document.getElementById('cbt-ham-panel');
        var panelOverlay  = document.getElementById('cbt-panel-overlay');
        var panelClose    = document.getElementById('cbt-panel-close');
        var panelIsOpen   = false;

        function openPanel() {
            if (!hamPanel) return;
            panelIsOpen = true;
            panelOverlay.style.display = 'block';
            hamPanel.style.display     = 'flex';
            requestAnimationFrame(function () {
                panelOverlay.classList.add('active');
                hamPanel.classList.add('is-open');
            });
            deskHamBtn.classList.add('is-open');
            deskHamBtn.setAttribute('aria-expanded', 'true');
            hamPanel.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closePanel() {
            if (!hamPanel) return;
            panelIsOpen = false;
            panelOverlay.classList.remove('active');
            hamPanel.classList.remove('is-open');
            deskHamBtn.classList.remove('is-open');
            deskHamBtn.setAttribute('aria-expanded', 'false');
            hamPanel.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            setTimeout(function () {
                if (!panelIsOpen) {
                    panelOverlay.style.display = 'none';
                    hamPanel.style.display     = 'none';
                }
            }, 350);
        }

        if (deskHamBtn && hamPanel) {
            hamPanel.style.display    = 'none';
            panelOverlay.style.display = 'none';

            deskHamBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                panelIsOpen ? closePanel() : openPanel();
            });
            if (panelClose)   panelClose.addEventListener('click', closePanel);
            if (panelOverlay) panelOverlay.addEventListener('click', closePanel);
        }

        var mobHamBtn     = document.getElementById('cbt-mobile-ham-btn');
        var mobDrawer     = document.getElementById('cbt-mobile-drawer');
        var mobOverlay    = document.getElementById('cbt-mobile-overlay');
        var drawerClose   = document.getElementById('cbt-drawer-close');
        var drawerIsOpen  = false;

        function openDrawer() {
            if (!mobDrawer) return;
            drawerIsOpen = true;
            mobOverlay.style.display = 'block';
            requestAnimationFrame(function () {
                mobOverlay.classList.add('active');
                mobDrawer.classList.add('is-open');
            });
            mobHamBtn.classList.add('is-open');
            mobHamBtn.setAttribute('aria-expanded', 'true');
            mobDrawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            if (!mobDrawer) return;
            drawerIsOpen = false;
            mobOverlay.classList.remove('active');
            mobDrawer.classList.remove('is-open');
            mobHamBtn.classList.remove('is-open');
            mobHamBtn.setAttribute('aria-expanded', 'false');
            mobDrawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            setTimeout(function () {
                if (!drawerIsOpen) {
                    mobOverlay.style.display = 'none';
                }
            }, 350);
        }

        if (mobHamBtn && mobDrawer) {
            mobOverlay.style.display = 'none';
            mobHamBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                drawerIsOpen ? closeDrawer() : openDrawer();
            });
            if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
            if (mobOverlay)  mobOverlay.addEventListener('click', closeDrawer);

            mobDrawer.querySelectorAll('.cbt-drawer-link').forEach(function (link) {
                link.addEventListener('click', closeDrawer);
            });
        }
    });
</script>
