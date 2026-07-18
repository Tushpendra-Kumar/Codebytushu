<?php
/**
 * Shared Public Navbar
 * Needs $user variable initialized from Auth::user() before including.
 */
$isLoggedIn = isset($user) && $user !== null;
$userName = $isLoggedIn ? ($user['full_name'] ?? $user['username'] ?? 'User') : '';
$userEmail = $isLoggedIn ? ($user['email'] ?? '') : '';
$userPhoto = $isLoggedIn ? ($user['profile_image'] ?? '') : '';
$userInitials = $isLoggedIn ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $userName), 0, 2)) : '';
?>
    <nav class="cbt-navbar navbar" id="mainNavbar" role="navigation" aria-label="Main navigation">
        <div class="cbt-nav-inner">

            <!-- ── Logo ───────────────────────────────────────────── -->
            <div class="cbt-logo" id="cbt-logo">
                <a href="<?= SITE_URL ?>/" id="cbt-logo-link" aria-label="CodeByTushu Home">
                    <img src="<?= SITE_URL ?>/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>

            <!-- ── Center Navigation (Desktop ≥1024px only) ──────── -->
            <ul class="cbt-center-nav" id="cbt-center-nav" role="menubar" aria-label="Primary navigation">
                <li role="none"><a href="<?= SITE_URL ?>/" class="cbt-nav-link" id="nav-home" role="menuitem" tabindex="0">Home</a></li>
                <li role="none"><a href="<?= SITE_URL ?>/Leetcode/" class="cbt-nav-link" id="nav-leetcode" role="menuitem" tabindex="0">LeetCode</a></li>
                <li role="none"><a href="<?= SITE_URL ?>/blogs.php" class="cbt-nav-link" id="nav-blogs" role="menuitem" tabindex="0">Blogs</a></li>
                <li role="none"><a href="<?= SITE_URL ?>/courses/" class="cbt-nav-link" id="nav-courses" role="menuitem" tabindex="0">Courses</a></li>
                <li role="none"><a href="<?= SITE_URL ?>/video-editing/" class="cbt-nav-link" id="nav-videoediting" role="menuitem" tabindex="0">Video Editing</a></li>
                <li role="none"><a href="<?= SITE_URL ?>/store/" class="cbt-nav-link" id="nav-store" role="menuitem" tabindex="0">Store</a></li>
                <li role="none"><a href="<?= SITE_URL ?>/Leetcode/donate.php" class="cbt-nav-link" id="nav-donate" role="menuitem" tabindex="0">Donate</a></li>
            </ul>

            <!-- ── Right Side: Auth + Hamburger (Desktop ≥1024px) ── -->
            <div class="cbt-nav-right" id="cbt-nav-right">
                <div id="cbt-auth-area" style="display:inline-flex;align-items:center;gap:8px;">
                    <?php if ($isLoggedIn): ?>
                        <div class="cbt-user-dropdown" id="cbt-user-dropdown">
                            <div id="cbt-avatar-trigger" style="cursor:pointer;">
                                <?php if ($userPhoto): ?>
                                    <img src="<?= htmlspecialchars($userPhoto) ?>" class="cbt-user-avatar" alt="<?= htmlspecialchars($userName) ?>" id="cbt-user-avatar-img" onerror="this.style.display='none';document.getElementById('cbt-user-init').style.display='inline-flex';">
                                    <span class="cbt-user-initials" id="cbt-user-init" style="display:none;"><?= htmlspecialchars($userInitials) ?></span>
                                <?php else: ?>
                                    <span class="cbt-user-initials" id="cbt-user-init"><?= htmlspecialchars($userInitials) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="cbt-user-menu" id="cbt-user-menu">
                                <div class="cbt-user-menu-header">
                                    <div class="cbt-user-menu-name"><?= htmlspecialchars($userName) ?></div>
                                    <div class="cbt-user-menu-email"><?= htmlspecialchars($userEmail) ?></div>
                                </div>
                                <a href="<?= SITE_URL ?>/user/dashboard.php" class="cbt-user-menu-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.58-7 8-7s8 3 8 7"/></svg>My Dashboard
                                </a>
                                <a href="#" onclick="document.getElementById('logout-form').submit(); return false;" class="cbt-user-menu-item logout">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout
                                </a>
                                <form id="logout-form" action="<?= SITE_URL ?>/api/auth/logout.php" method="POST" style="display: none;">
                                    <?= csrfField() ?>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/auth/login.php" class="cbt-login-btn" id="cbt-login-btn" aria-label="Login to your account">
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn" aria-label="Open more links" aria-expanded="false" aria-controls="cbt-ham-panel">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>

            <!-- ── Mobile Right: Auth + Hamburger (<1024px) ─────── -->
            <div class="cbt-mobile-right" id="cbt-mobile-right">
                <div id="cbt-auth-area-mob" style="display:inline-flex;align-items:center;">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($userPhoto): ?>
                            <img src="<?= htmlspecialchars($userPhoto) ?>" class="cbt-user-avatar" style="width:32px;height:32px;" alt="<?= htmlspecialchars($userName) ?>" onclick="window.location.href='<?= SITE_URL ?>/user/dashboard.php'">
                        <?php else: ?>
                            <span class="cbt-user-initials" style="width:32px;height:32px;font-size:12px;" onclick="window.location.href='<?= SITE_URL ?>/user/dashboard.php'"><?= htmlspecialchars($userInitials) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/auth/login.php" class="cbt-login-btn" id="cbt-login-btn-mob" aria-label="Login">
                            <span>Login</span>
                        </a>
                    <?php endif; ?>
                </div>
                <button class="cbt-mobile-ham-btn" id="cbt-mobile-ham-btn" aria-label="Open mobile menu" aria-expanded="false" aria-controls="cbt-mobile-drawer">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>

        </div><!-- /.cbt-nav-inner -->

        <!-- ══ Desktop Hamburger Panel (right slide-in) ════════════ -->
        <div class="cbt-panel-overlay" id="cbt-panel-overlay" aria-hidden="true"></div>
        <div class="cbt-ham-panel" id="cbt-ham-panel" role="dialog" aria-modal="true" aria-label="More links" aria-hidden="true">
            <button class="cbt-panel-close" id="cbt-panel-close" aria-label="Close menu">&#x2715;</button>
            <p class="cbt-panel-label">More</p>
            <nav class="cbt-panel-nav" aria-label="Secondary navigation">
                <a href="<?= SITE_URL ?>/about-platform.php" class="cbt-panel-link" id="panel-about">About</a>
                <a href="<?= SITE_URL ?>/privacy-policy/" class="cbt-panel-link" id="panel-privacy">Privacy Policy</a>
                <a href="<?= SITE_URL ?>/terms/" class="cbt-panel-link" id="panel-terms">Terms &amp; Conditions</a>
                <a href="<?= SITE_URL ?>/disclaimer/" class="cbt-panel-link" id="panel-disclaimer">Disclaimer</a>
                <a href="<?= SITE_URL ?>/support/" class="cbt-panel-link" id="panel-support">Support</a>
            </nav>
        </div>

        <!-- ══ Mobile Full Drawer ═══════════════════════════════════ -->
        <div class="cbt-mobile-overlay" id="cbt-mobile-overlay" aria-hidden="true"></div>
        <div class="cbt-mobile-drawer" id="cbt-mobile-drawer" role="dialog" aria-modal="true" aria-label="Mobile menu" aria-hidden="true">
            <div class="cbt-drawer-header">
                <div class="cbt-logo">
                    <a href="<?= SITE_URL ?>/" aria-label="CodeByTushu Home" tabindex="-1">
                        <span class="cbt-logo-bracket">&lt;/&gt;</span>
                        <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                    </a>
                </div>
                <button class="cbt-drawer-close" id="cbt-drawer-close" aria-label="Close menu">&#x2715;</button>
            </div>
            <div class="cbt-drawer-body">
                <ul class="cbt-drawer-primary" role="menu" aria-label="Main navigation">
                    <li role="none"><a href="<?= SITE_URL ?>/" class="cbt-drawer-link" id="drawer-home" role="menuitem">Home</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/Leetcode/" class="cbt-drawer-link" id="drawer-leetcode" role="menuitem">LeetCode</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/blogs.php" class="cbt-drawer-link" id="drawer-blogs" role="menuitem">Blogs</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/courses/" class="cbt-drawer-link" id="drawer-courses" role="menuitem">Courses</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/video-editing/" class="cbt-drawer-link" id="drawer-videoediting" role="menuitem">Video Editing</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/store/" class="cbt-drawer-link" id="drawer-store" role="menuitem">Store</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/Leetcode/donate.php" class="cbt-drawer-link" id="drawer-donate" role="menuitem">Donate</a></li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li role="none"><a href="<?= SITE_URL ?>/user/dashboard.php" class="cbt-drawer-link" id="drawer-dashboard" role="menuitem" style="color:var(--accent);">Dashboard</a></li>
                        <li role="none"><a href="#" onclick="document.getElementById('logout-form').submit(); return false;" class="cbt-drawer-link" id="drawer-logout" role="menuitem" style="color:#ff6b6b;">Logout</a></li>
                    <?php else: ?>
                        <li role="none"><a href="<?= SITE_URL ?>/auth/login.php" class="cbt-drawer-link" id="drawer-login" role="menuitem">Login</a></li>
                    <?php endif; ?>
                </ul>
                <div class="cbt-drawer-divider" role="separator"></div>
                <p class="cbt-drawer-label">More</p>
                <ul class="cbt-drawer-secondary" role="menu" aria-label="Secondary navigation">
                    <li role="none"><a href="<?= SITE_URL ?>/about-platform.php" class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-about" role="menuitem">About</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/privacy-policy/" class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-privacy" role="menuitem">Privacy Policy</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/terms/" class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-terms" role="menuitem">Terms &amp; Conditions</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/disclaimer/" class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-disclaimer" role="menuitem">Disclaimer</a></li>
                    <li role="none"><a href="<?= SITE_URL ?>/support/" class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-support" role="menuitem">Support</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <script>
    (function(){
        // Toggle dropdown on avatar click
        document.addEventListener('click', function(e) {
            var trigger = document.getElementById('cbt-avatar-trigger');
            var menu = document.getElementById('cbt-user-menu');
            if (!trigger || !menu) return;
            if (trigger.contains(e.target)) {
                menu.classList.toggle('open');
            } else {
                menu.classList.remove('open');
            }
        });
        
        // Mobile Drawer Toggle Fallback (in case main.js is missing on the page)
        var mobBtn = document.getElementById('cbt-mobile-ham-btn');
        var drawer = document.getElementById('cbt-mobile-drawer');
        var overlay = document.getElementById('cbt-mobile-overlay');
        var closeBtn = document.getElementById('cbt-drawer-close');
        
        function openDrawer() {
            if(drawer) drawer.classList.add('open');
            if(overlay) overlay.classList.add('show');
            if(mobBtn) mobBtn.classList.add('active');
        }
        function closeDrawer() {
            if(drawer) drawer.classList.remove('open');
            if(overlay) overlay.classList.remove('show');
            if(mobBtn) mobBtn.classList.remove('active');
        }
        
        if (mobBtn && !mobBtn.hasAttribute('data-bound')) {
            mobBtn.addEventListener('click', openDrawer);
            mobBtn.setAttribute('data-bound', 'true');
        }
        if (overlay && !overlay.hasAttribute('data-bound')) {
            overlay.addEventListener('click', closeDrawer);
            overlay.setAttribute('data-bound', 'true');
        }
        if (closeBtn && !closeBtn.hasAttribute('data-bound')) {
            closeBtn.addEventListener('click', closeDrawer);
            closeBtn.setAttribute('data-bound', 'true');
        }
    })();
    </script>
