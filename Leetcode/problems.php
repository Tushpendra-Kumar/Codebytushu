<?php
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
Auth::requireLogin();

/**
 * CodeByTushu — LeetCode Problems Archive
 * MODE A: No ?year= param  â†’ show year-selector cards (2026, 2027, 2028)
 * MODE B: ?year=XXXX        â†’ show only that year's 12 monthly cards
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

// â”€â”€â”€ Determine mode â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if ($selectedYear) {
    /* â”€â”€ MODE B: fetch the single requested year + its months â”€â”€ */
    $stmtY = $pdo->prepare(
        "SELECT id, year, badge_label, description
         FROM leetcode_years
         WHERE year = ? AND is_visible = 1
         LIMIT 1"
    );
    $stmtY->execute([$selectedYear]);
    $yearRow = $stmtY->fetch();

    if (!$yearRow) {
        // Year not found â†’ bounce back to year-selector
        header('Location: /Leetcode/problems.php');
        exit;
    }

    // Fetch months only if it's not 2028 (Future Archive)
    if ((int)$yearRow['year'] !== 2028) {
        $stmtM = $pdo->prepare(
            "SELECT id, month_num, month_name, total_days, published_solutions
             FROM leetcode_months
             WHERE year_id = ?
             ORDER BY month_num ASC"
        );
        $stmtM->execute([$yearRow['id']]);
        $months = $stmtM->fetchAll();

        // Auto-seed months if missing from database (e.g. migration failed on shared hosting)
        if (empty($months)) {
            $yearInt = (int)$yearRow['year'];
            for ($m = 1; $m <= 12; $m++) {
                $mName = date('F', mktime(0, 0, 0, $m, 10));
                $mShort = date('M', mktime(0, 0, 0, $m, 10));
                $days = (int)date('t', mktime(0, 0, 0, $m, 1, $yearInt));
                
                $ins = $pdo->prepare("INSERT IGNORE INTO leetcode_months (year_id, year, month_num, month_name, month_short, total_days) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$yearRow['id'], $yearInt, $m, $mName, $mShort, $days]);
            }
            // Re-fetch after seeding
            $stmtM->execute([$yearRow['id']]);
            $months = $stmtM->fetchAll();
        }
    } else {
        $months = [];
    }

} else {
    /* â”€â”€ MODE A: fetch all visible years for the selector â”€â”€ */
    $stmtAll = $pdo->query(
        "SELECT id, year, badge_label, description
         FROM leetcode_years
         WHERE is_visible = 1
         ORDER BY year ASC"
    );
    $allYears = $stmtAll->fetchAll();
}

// Badge labels for year cards
$badgeLabels = [
    2026 => 'ACTIVE YEAR',
    2027 => 'UPCOMING ARCHIVE',
    2028 => 'FUTURE ARCHIVE',
];
$yearDescs = [
    2026 => 'Explore all LeetCode daily challenge solutions from January to December 2026 with Java, Python, C++, and JavaScript implementations.',
    2027 => 'Access organised LeetCode solutions for the complete year 2027 including daily coding challenges, optimised approaches, and multi-language code support.',
    2028 => 'Browse the complete 2028 coding archive containing daily LeetCode solutions with clean implementations in Java, Python, C++, and JavaScript.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($selectedYear): ?>
    <meta name="description" content="LeetCode <?= $selectedYear ?> Monthly Archive — All daily solutions by CodeByTushu.">
    <title>LeetCode <?= $selectedYear ?> Problems | CodeByTushu</title>
    <?php else: ?>
    <meta name="description" content="LeetCode Daily Problems Archive — 2026, 2027 & 2028 solutions by CodeByTushu.">
    <title>LeetCode Problems | CodeByTushu</title>
    <?php endif; ?>

    <link rel="icon"             href="/favicon.ico?v=6"                 sizes="any">
    <link rel="icon"             href="/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
    <link rel="icon"             href="/favicon-48x48.png?v=6"           type="image/png" sizes="48x48">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=6"        sizes="180x180">
    <link rel="manifest"         href="/site.webmanifest?v=6">
    <meta name="theme-color"     content="#ffc400">

    <script src="/theme.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Main Website Styling System (root-relative — works at any nested URL) -->
    <link rel="stylesheet" href="/styles.css?v=40">

    <!-- LeetCode Specific Overrides (root-relative) -->
    <link rel="stylesheet" href="/Leetcode/CSS/style.css">
    <link rel="stylesheet" href="/Leetcode/auth.css">

    <style>
        /* â”€â”€ Auth overlay overrides â”€â”€ */
        .cbt-content-locked      { filter: none !important; -webkit-filter: none !important; }
        #cbt-auth-overlay        { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; background: rgba(0,0,0,0.9) !important; }
        #cbt-auth-overlay *      { filter: none !important; -webkit-filter: none !important; }
        .cbt-auth-modal          { filter: none !important; -webkit-filter: none !important; }

        /* â”€â”€ Breadcrumb â”€â”€ */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #888;
            margin-bottom: 28px;
        }
        .breadcrumb a { color: #ffc400; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { color: #555; }
    </style>
</head>

<body>

    <!-- â•â•â• PREMIUM NAVBAR (matches Leetcode index.html exactly) â•â•â• -->
    <nav class="cbt-navbar navbar" id="mainNavbar" role="navigation" aria-label="Main navigation">
        <div class="cbt-nav-inner">
            <!-- Logo -->
            <div class="cbt-logo" id="cbt-logo">
                <a href="/Leetcode/" id="cbt-logo-link" aria-label="CodeByTushu Home" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
                    <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <div style="display:flex;flex-direction:column;align-items:flex-start;gap:0;">
                        <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                        <span style="display:flex;align-items:center;gap:4px;font-size:10px;font-weight:700;letter-spacing:1.5px;color:#aaa;margin-top:-2px;line-height:1;">
                            LEETCODE <span style="color:#ffc400;">UNLOCKED</span>
                            <img src="/Leetcode/assets/logo.png/unlocked.png" alt="" style="width:13px;">
                        </span>
                    </div>
                </a>
            </div>

            <!-- Center Nav (Desktop) -->
            <ul class="cbt-center-nav" id="cbt-center-nav" role="menubar" aria-label="Primary navigation">
                <li role="none"><a href="/Leetcode/"  class="cbt-nav-link" id="nav-home"    role="menuitem" tabindex="0">Home</a></li>
                <li role="none"><a href="/Leetcode/problems.php" class="cbt-nav-link active" id="nav-problems" role="menuitem" tabindex="0">Problems</a></li>
                <li role="none"><a href="/Leetcode/donate.php"  class="cbt-nav-link" id="nav-donate"  role="menuitem" tabindex="0">Donate</a></li>
            </ul>

            <!-- Desktop Hamburger -->
            <div class="cbt-nav-right" id="cbt-nav-right">
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn" aria-label="Open more links" aria-expanded="false" aria-controls="cbt-ham-panel">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>

            <!-- Mobile Hamburger -->
            <div class="cbt-mobile-right" id="cbt-mobile-right">
                <button class="cbt-mobile-ham-btn" id="cbt-mobile-ham-btn" aria-label="Open mobile menu" aria-expanded="false" aria-controls="cbt-mobile-drawer">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>

        <!-- Desktop Panel -->
        <div class="cbt-panel-overlay" id="cbt-panel-overlay" aria-hidden="true"></div>
        <div class="cbt-ham-panel" id="cbt-ham-panel" role="dialog" aria-modal="true" aria-label="More links" aria-hidden="true">
            <button class="cbt-panel-close" id="cbt-panel-close" aria-label="Close menu">&#x2715;</button>
            <p class="cbt-panel-label">CodeByTushu</p>
            <nav class="cbt-panel-nav" aria-label="Secondary navigation">
                <a href="/about-platform.html" class="cbt-panel-link" id="panel-about">About Us</a>
                <a href="/privacy-policy/"     class="cbt-panel-link" id="panel-privacy">Privacy Policy</a>
                <a href="/terms/"              class="cbt-panel-link" id="panel-terms">Terms &amp; Conditions</a>
                <a href="/disclaimer/"         class="cbt-panel-link" id="panel-disclaimer">Disclaimer</a>
            </nav>
        </div>

        <!-- Mobile Drawer -->
        <div class="cbt-mobile-overlay" id="cbt-mobile-overlay" aria-hidden="true"></div>
        <div class="cbt-mobile-drawer" id="cbt-mobile-drawer" role="dialog" aria-modal="true" aria-label="Mobile menu" aria-hidden="true">
            <div class="cbt-drawer-header">
                <div class="cbt-logo">
                    <a href="/Leetcode/" aria-label="CodeByTushu Home" tabindex="-1" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
                        <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img" style="height:35px;">
                        <span class="cbt-logo-text" style="font-size:18px;">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                    </a>
                </div>
                <button class="cbt-drawer-close" id="cbt-drawer-close" aria-label="Close menu">&#x2715;</button>
            </div>
            <div class="cbt-drawer-body">
                <ul class="cbt-drawer-primary" role="menu" aria-label="Main navigation">
                    <li role="none"><a href="/Leetcode/"   class="cbt-drawer-link" id="drawer-home"     role="menuitem">Home</a></li>
                    <li role="none"><a href="/Leetcode/problems.php"  class="cbt-drawer-link" id="drawer-problems" role="menuitem">Problems</a></li>
                    <li role="none"><a href="/Leetcode/donate.php"   class="cbt-drawer-link" id="drawer-donate"   role="menuitem">Donate</a></li>
                </ul>
                <div class="cbt-drawer-divider" role="separator"></div>
                <p class="cbt-drawer-label">More</p>
                <ul class="cbt-drawer-secondary" role="menu" aria-label="Secondary navigation">
                    <li role="none"><a href="/about-platform.html" class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-about"      role="menuitem">About Us</a></li>
                    <li role="none"><a href="/privacy-policy/"     class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-privacy"    role="menuitem">Privacy Policy</a></li>
                    <li role="none"><a href="/terms/"              class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-terms"      role="menuitem">Terms &amp; Conditions</a></li>
                    <li role="none"><a href="/disclaimer/"         class="cbt-drawer-link cbt-drawer-link-sm" id="drawer-disclaimer" role="menuitem">Disclaimer</a></li>
                </ul>
            </div>
        </div>
    </nav><!-- /.cbt-navbar -->

    <!-- BACKGROUND -->
    <div class="cbt-hero-bg" style="position:fixed;width:100vw;height:100vh;z-index:-1;">
        <div class="cbt-glow-center"></div>
        <div class="cbt-streak cbt-streak-1"></div>
        <div class="cbt-streak cbt-streak-2"></div>
        <div class="cbt-streak cbt-streak-3"></div>
        <div class="cbt-circle cbt-circle-left"></div>
        <div class="cbt-circle cbt-circle-right"></div>
        <div class="cbt-particles">
            <span class="cbt-particle p-1"></span><span class="cbt-particle p-2"></span>
            <span class="cbt-particle p-3"></span><span class="cbt-particle p-4"></span>
            <span class="cbt-particle p-5"></span>
        </div>
    </div>

    <section class="problems-page">
        <div class="problems-content">

<?php if ($selectedYear && isset($yearRow)): ?>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MODE B — MONTHLY ARCHIVE FOR A SPECIFIC YEAR
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

<?php if ((int)$yearRow['year'] === 2028): ?>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     COMING SOON — 2028 FUTURE ARCHIVE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- Breadcrumb -->
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= SITE_URL ?>/Leetcode/problems.php">All Years</a>
                <span>›</span>
                <span>2028</span>
            </nav>

            <div style="text-align: center; margin-top: 60px; max-width: 600px; margin-left: auto; margin-right: auto; padding: 50px 30px; background: rgba(18, 18, 20, 0.7); border: 1px solid rgba(255, 196, 0, 0.15); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
                <span class="problem-badge" style="display:inline-block; margin-bottom: 25px; padding: 6px 16px; font-size: 13px; letter-spacing: 1px; color: #ffc400; background: rgba(255, 196, 0, 0.1); border: 1px solid rgba(255, 196, 0, 0.3); border-radius: 50px;">
                    FUTURE ARCHIVE
                </span>
                
                <h1 style="font-size: 34px; margin-bottom: 20px; font-weight: 700; color: #fff;">2028 Daily Problems</h1>
                
                <p style="color: #bbb; line-height: 1.6; margin-bottom: 35px; font-size: 16px;">
                    The 2028 LeetCode Daily Archive is currently under preparation.<br>
                    Daily problems and solutions will be published here once they become available.
                </p>
                
                <div style="font-size: 55px; color: #ffc400; margin-bottom: 40px; opacity: 0.9;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                
                <a href="<?= SITE_URL ?>/Leetcode/problems.php" class="explore-btn" style="display: inline-block; padding: 14px 28px; border-radius: 10px; font-weight: 500; font-size: 15px; text-decoration: none;">
                    <i class="fa-solid fa-arrow-left" style="font-size:inherit;"></i> Back to Daily Archive
                </a>
            </div>

<?php else: ?>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     NORMAL MODE — MONTHLY ARCHIVE FOR ACTIVE YEARS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- Breadcrumb -->
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="<?= SITE_URL ?>/Leetcode/problems.php">All Years</a>
                <span>›</span>
                <span><?= (int)$yearRow['year'] ?></span>
            </nav>

            <h1><?= (int)$yearRow['year'] ?> Monthly Archive</h1>
            <p>
                <?= e($yearRow['description'] ?: 'Select any month to explore daily LeetCode problems.') ?>
            </p>

            <section class="months-section" style="margin-top: 40px;">
                <div class="months-heading">
                    <span class="problem-badge">
                        <?= e($yearRow['badge_label'] ?: $yearRow['year'].' ARCHIVE') ?>
                    </span>
                    <h2 style="margin-top:10px;"><?= (int)$yearRow['year'] ?> MONTHLY ARCHIVE</h2>
                    <p><?= e($yearDescs[$yearRow['year']] ?? 'Choose a month below.') ?></p>
                </div>

                <div class="months-grid">
                    <?php if (empty($months)): ?>
                        <p style="color:#888;">No months available yet.</p>
                    <?php else: ?>
                        <?php foreach ($months as $m): ?>
                        <a href="<?= SITE_URL ?>/Leetcode/month.php?id=<?= (int)$m['id'] ?>" class="month-card">
                            <span><?= str_pad((string)$m['month_num'], 2, '0', STR_PAD_LEFT) ?></span>
                            <h3><?= e($m['month_name']) ?> <?= (int)$yearRow['year'] ?></h3>
                            <?php if ($m['published_solutions'] > 0): ?>
                            <div style="font-size:12px; color:#ffc400; margin-top:5px;">
                                <?= (int)$m['published_solutions'] ?> Solutions
                            </div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
<?php endif; ?>

<?php else: ?>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MODE A — YEAR SELECTOR (no year chosen yet)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

            <h1>LeetCode Daily Problems</h1>
            <p>
                Explore all daily LeetCode coding challenge solutions with optimised
                implementations in Java, Python, C++, and JavaScript.
            </p>

            <!-- Year cards — clicking one goes to problems.php?year=XXXX -->
            <div class="year-card-container" style="margin-top:40px;">
                <?php foreach ($allYears as $y): ?>
                <?php
                    $yNum   = (int)$y['year'];
                    $badge  = $badgeLabels[$yNum] ?? ($y['badge_label'] ?: 'ARCHIVE');
                    $desc   = $yearDescs[$yNum]   ?? ($y['description'] ?: 'Explore daily LeetCode problems.');
                ?>
                <div class="year-card">
                    <span class="year-badge"><?= e($badge) ?></span>
                    <h3><?= $yNum ?> DAILY PROBLEMS</h3>
                    <p><?= e($desc) ?></p>
                    <a href="<?= SITE_URL ?>/Leetcode/problems.php?year=<?= $yNum ?>" class="explore-btn">
                        Explore <?= $yNum ?> <i class="fa-solid fa-arrow-right" style="font-size:inherit;"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

<?php endif; ?>

        </div>
    </section>

    <script src="/back-home.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var cbtNav = document.getElementById('mainNavbar');
            window.addEventListener('scroll', function () {
                if (window.scrollY > 20) cbtNav.classList.add('sticky');
                else cbtNav.classList.remove('sticky');
            }, { passive: true });

            var deskHamBtn = document.getElementById('cbt-hamburger-btn');
            var hamPanel   = document.getElementById('cbt-ham-panel');
            var panelOverlay = document.getElementById('cbt-panel-overlay');
            var panelClose = document.getElementById('cbt-panel-close');
            var panelIsOpen = false;

            function openPanel() {
                panelIsOpen = true;
                panelOverlay.style.display = 'block';
                hamPanel.style.display = 'flex';
                requestAnimationFrame(function () {
                    panelOverlay.classList.add('active');
                    hamPanel.classList.add('is-open');
                });
                deskHamBtn.setAttribute('aria-expanded', 'true');
            }
            function closePanel() {
                panelIsOpen = false;
                panelOverlay.classList.remove('active');
                hamPanel.classList.remove('is-open');
                deskHamBtn.setAttribute('aria-expanded', 'false');
                setTimeout(function () { if (!panelIsOpen) { panelOverlay.style.display='none'; hamPanel.style.display='none'; } }, 350);
            }
            if (deskHamBtn && hamPanel) {
                hamPanel.style.display = 'none';
                panelOverlay.style.display = 'none';
                deskHamBtn.addEventListener('click', function(e) { e.stopPropagation(); panelIsOpen ? closePanel() : openPanel(); });
                if (panelClose)   panelClose.addEventListener('click', closePanel);
                if (panelOverlay) panelOverlay.addEventListener('click', closePanel);
            }

            var mobHamBtn  = document.getElementById('cbt-mobile-ham-btn');
            var mobDrawer  = document.getElementById('cbt-mobile-drawer');
            var mobOverlay = document.getElementById('cbt-mobile-overlay');
            var drawerClose = document.getElementById('cbt-drawer-close');
            var drawerIsOpen = false;

            function openDrawer() {
                drawerIsOpen = true;
                mobOverlay.style.display = 'block';
                requestAnimationFrame(function () { mobOverlay.classList.add('active'); mobDrawer.classList.add('is-open'); });
                mobHamBtn.setAttribute('aria-expanded', 'true');
            }
            function closeDrawer() {
                drawerIsOpen = false;
                mobOverlay.classList.remove('active');
                mobDrawer.classList.remove('is-open');
                mobHamBtn.setAttribute('aria-expanded', 'false');
                setTimeout(function () { if (!drawerIsOpen) mobOverlay.style.display='none'; }, 350);
            }
            if (mobHamBtn && mobDrawer) {
                mobOverlay.style.display = 'none';
                mobHamBtn.addEventListener('click', function(e) { e.stopPropagation(); drawerIsOpen ? closeDrawer() : openDrawer(); });
                if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
                if (mobOverlay)  mobOverlay.addEventListener('click', closeDrawer);
                mobDrawer.querySelectorAll('.cbt-drawer-link').forEach(function(link) { link.addEventListener('click', closeDrawer); });
            }
        });
    </script>

</body>
</html>

