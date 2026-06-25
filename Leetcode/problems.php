<?php
/**
 * CodeByTushu — LeetCode Problems Archive
 * MODE A: No ?year= param  → show year-selector cards (2026, 2027, 2028)
 * MODE B: ?year=XXXX        → show only that year's 12 monthly cards
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

// ─── Determine mode ────────────────────────────────────────────────────────
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if ($selectedYear) {
    /* ── MODE B: fetch the single requested year + its months ── */
    $stmtY = $pdo->prepare(
        "SELECT id, year, badge_label, description
         FROM leetcode_years
         WHERE year = ? AND is_visible = 1
         LIMIT 1"
    );
    $stmtY->execute([$selectedYear]);
    $yearRow = $stmtY->fetch();

    if (!$yearRow) {
        // Year not found → bounce back to year-selector
        header('Location: /Leetcode/problems.php');
        exit;
    }

    $stmtM = $pdo->prepare(
        "SELECT id, month_num, month_name, total_days, published_solutions
         FROM leetcode_months
         WHERE year_id = ? AND is_visible = 1
         ORDER BY month_num ASC"
    );
    $stmtM->execute([$yearRow['id']]);
    $months = $stmtM->fetchAll();

} else {
    /* ── MODE A: fetch all visible years for the selector ── */
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

    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/Leetcode/auth.css">

    <style>
        /* ── Auth overlay overrides ── */
        .cbt-content-locked      { filter: none !important; -webkit-filter: none !important; }
        #cbt-auth-overlay        { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; background: rgba(0,0,0,0.9) !important; }
        #cbt-auth-overlay *      { filter: none !important; -webkit-filter: none !important; }
        .cbt-auth-modal          { filter: none !important; -webkit-filter: none !important; }

        /* ── Breadcrumb ── */
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
    <header class="navbar">
        <div class="logo-area">
            <div class="top-logo">
                <h1 class="main-logo">
                    <span class="white-text">CODEBY</span><span class="gold-text">TUSHU</span>
                </h1>
            </div>
            <div class="bottom-logo">
                <h2 class="sub-logo">LEETCODE <span>UNLOCKED</span></h2>
                <img src="<?= SITE_URL ?>/Leetcode/assets/logo.png/unlocked.png" alt="unlock icon" class="unlock-icon">
            </div>
        </div>

        <nav>
            <ul>
                <li><a href="<?= SITE_URL ?>/Leetcode/index.html">Home</a></li>
                <li><a href="<?= SITE_URL ?>/Leetcode/problems.php" class="active">Problems</a></li>
                <li><a href="<?= SITE_URL ?>/Leetcode/donate.php">Donate</a></li>
                <li><a href="/#contact">Contact Me</a></li>
                <li id="cbt-nav-auth-slot"></li>
            </ul>
        </nav>
    </header>

    <br><br><br><br>

    <section class="problems-page">
        <div class="problems-content">

<?php if ($selectedYear && isset($yearRow)): ?>
<!-- ═══════════════════════════════════════════════════
     MODE B — MONTHLY ARCHIVE FOR A SPECIFIC YEAR
═══════════════════════════════════════════════════ -->

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
                    <p><?= e($yearDesc[$yearRow['year']] ?? 'Choose a month below.') ?></p>
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

<?php else: ?>
<!-- ═══════════════════════════════════════════════════
     MODE A — YEAR SELECTOR (no year chosen yet)
═══════════════════════════════════════════════════ -->

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
                        Explore <?= $yNum ?> &rarr;
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

<?php endif; ?>

        </div>
    </section>

    <script src="<?= SITE_URL ?>/Leetcode/JS/cbt-auth.js"></script>
    <script>cbtNavAuth();</script>
</body>
</html>

