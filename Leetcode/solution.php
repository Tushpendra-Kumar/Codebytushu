<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
Auth::requireLogin();

/**
 * CodeByTushu — LeetCode Dynamic Solution Page
 * Single template rendering ALL problem pages from the database.
 *
 * Routes:
 *   /leetcode/problem/{slug}   → ?slug=...  (primary SEO URL)
 *   /leetcode/problem/?date=YYYY-MM-DD      (legacy redirect)
 *
 * This file requires NO hardcoded HTML per problem.
 * All content is loaded from the `leetcode_solutions` and
 * `solution_code_blocks` database tables.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   1. Resolve the solution from month/day, slug, or date
   â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
$monthId = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$day     = isset($_GET['day']) ? (int)$_GET['day'] : 0;
$slug    = trim($_GET['slug'] ?? '');
$date    = trim($_GET['date'] ?? '');

$sol = null;
$notFound = false;
$isUpcoming = false;

if ($monthId > 0 && $day > 0) {
    // 1. Validate month and day
    $stmtM = $pdo->prepare('SELECT id, year_id, year, month_num, month_name, total_days FROM leetcode_months WHERE id = ? LIMIT 1');
    $stmtM->execute([$monthId]);
    $monthRow = $stmtM->fetch();

    if (!$monthRow || $day < 1 || $day > $monthRow['total_days']) {
        $notFound = true;
    } else {
        $dateStr = sprintf("%04d-%02d-%02d", $monthRow['year'], $monthRow['month_num'], $day);

        $stmt = $pdo->prepare('SELECT * FROM leetcode_solutions WHERE solution_date = ? LIMIT 1');
        $stmt->execute([$dateStr]);
        $sol = $stmt->fetch();

        if (!$sol) {
            // Generate placeholder for valid day without a published solution
            $sol = [
                'id' => 0,
                'problem_title' => 'Problem Coming Soon',
                'problem_number' => null,
                'solution_date' => $dateStr,
                'difficulty' => 'Medium',
                'explanation' => '<p>This solution will be published soon.</p>',
                'approach_summary' => 'This solution will be published soon.',
                'meta_description' => 'Placeholder solution page for ' . $dateStr,
                'slug' => 'coming-soon-' . $dateStr,
                'is_published' => 0,
                'youtube_url' => '',
                'month_id' => $monthId
            ];
            $isUpcoming = true;
        }
    }
} elseif ($slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM leetcode_solutions WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $sol = $stmt->fetch();
} elseif ($date !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        $sol = null;
    } else {
        $stmt = $pdo->prepare('SELECT * FROM leetcode_solutions WHERE solution_date = ? LIMIT 1');
        $stmt->execute([$date]);
        $sol = $stmt->fetch();
        if ($sol) {
            header('Location: ' . SITE_URL . '/leetcode/problem/' . $sol['slug'], true, 301);
            exit;
        }
    }
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   2. Handle not-found / upcoming state
   â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
if (!$sol && !$notFound) {
    http_response_code(404);
    $notFound = true;
} elseif (!$notFound) {

    /* â”€â”€â”€ Is this an upcoming / unpublished problem? â”€â”€â”€ */
    $currentDate = new \DateTime('today');
    $problemDate = new \DateTime($sol['solution_date']);
    
    if (!$isUpcoming) {
        $isUpcoming = ($sol['is_published'] == 0 || $problemDate > $currentDate);
    }

    $isComingSoon2026 = false;
    $isFutureYear = false;
    
    $year = (int)$problemDate->format('Y');
    if ($year >= 2027) {
        $isFutureYear = true;
        $isUpcoming = true;
    } elseif ($year == 2026) {
        $cutoff = new \DateTime('2026-04-13');
        // Removed custom cutoff for 2026 since we now dynamically show placeholders based on database absence.
    }

    /* â”€â”€â”€ Track view count (only for published, real visits) â”€â”€â”€ */
    if (!$isUpcoming) {
        try {
            $pdo->prepare(
                'UPDATE leetcode_solutions SET view_count = view_count + 1 WHERE id = ?'
            )->execute([$sol['id']]);
        } catch (\Throwable) { /* non-fatal */ }
    }

    /* â”€â”€â”€ Load code blocks â”€â”€â”€ */
    $codeBlocks = [];
    if (!$isUpcoming && $sol['id'] > 0) {
        $stmtCode = $pdo->prepare(
            'SELECT language, language_slug, code
               FROM solution_code_blocks
              WHERE solution_id = ?
              ORDER BY sort_order ASC, id ASC'
        );
        $stmtCode->execute([$sol['id']]);
        foreach ($stmtCode->fetchAll() as $b) {
            $langKey = strtolower(trim($b['language']));
            $codeBlocks[$langKey] = $b['code'];
        }
    } elseif ($sol['id'] == 0) {
        $codeBlocks = [
            'java'       => "// Java Solution\n// Code will be updated soon.\nclass Solution {\n    public void solve() {\n        \n    }\n}",
            'python'     => "# Python Solution\n# Code will be updated soon.\nclass Solution:\n    def solve(self):\n        pass",
            'c++'        => "// C++ Solution\n// Code will be updated soon.\nclass Solution {\npublic:\n    void solve() {\n        \n    }\n};",
            'javascript' => "// JavaScript Solution\n// Code will be updated soon.\nvar solve = function() {\n    \n};"
        ];
    }

    /* â”€â”€â”€ Map language keys â†’ display names & IDs â”€â”€â”€ */
    $languageMap = [
        'java'       => ['label' => 'Java',       'id' => 'java'],
        'python'     => ['label' => 'Python',      'id' => 'python'],
        'c++'        => ['label' => 'C++',         'id' => 'cpp'],
        'javascript' => ['label' => 'JavaScript',  'id' => 'javascript'],
        'typescript' => ['label' => 'TypeScript',  'id' => 'typescript'],
        'go'         => ['label' => 'Go',          'id' => 'go'],
        'rust'       => ['label' => 'Rust',        'id' => 'rust'],
        'c#'         => ['label' => 'C#',          'id' => 'csharp'],
        'kotlin'     => ['label' => 'Kotlin',      'id' => 'kotlin'],
        'swift'      => ['label' => 'Swift',       'id' => 'swift'],
        'c'          => ['label' => 'C',           'id' => 'c'],
    ];

    /* Determine which languages actually have REAL code (not placeholders) */
    $availableLangs = [];
    foreach ($languageMap as $key => $meta) {
        if (isset($codeBlocks[$key])) {
            $trimmed = trim($codeBlocks[$key]);
            // Skip empty strings or common placeholder phrases
            $isPlaceholder = (
                $trimmed === '' ||
                stripos($trimmed, 'WRITE YOUR') !== false ||
                stripos($trimmed, 'YOUR CODE HERE') !== false ||
                $trimmed === '// TODO'
            );
            if (!$isPlaceholder) {
                $availableLangs[$key] = $meta;
            }
        }
    }

    /* Fallback: if DB has code blocks with unknown language keys */
    foreach ($codeBlocks as $key => $code) {
        if (!isset($availableLangs[$key]) && trim($code) !== '') {
            $availableLangs[$key] = [
                'label' => ucfirst($key),
                'id'    => preg_replace('/[^a-z0-9]/', '', strtolower($key)),
            ];
        }
    }

    /* â”€â”€â”€ First available language (default active tab) â”€â”€â”€ */
    $firstLangKey = array_key_first($availableLangs) ?? null;

    /* â”€â”€â”€ Load tags â”€â”€â”€ */
    $tags = [];
    if ($sol['id'] == 0) {
        $tags = [['name' => 'Placeholder', 'color_hex' => '#ffc400']];
    } else {
        try {
            $stmtTags = $pdo->prepare(
                'SELECT t.name, t.color_hex
                   FROM solution_tag_map tm
                   JOIN solution_tags t ON t.id = tm.tag_id
                  WHERE tm.solution_id = ?
                  ORDER BY t.name ASC'
            );
            $stmtTags->execute([$sol['id']]);
            $tags = $stmtTags->fetchAll();
        } catch (\Throwable) { /* non-fatal */ }
    }

    /* â”€â”€â”€ Prev / Next solutions â”€â”€â”€ */
    $prevSol = null;
    $nextSol = null;
    try {
        $stmtPrev = $pdo->prepare(
            'SELECT slug, problem_title, problem_number, solution_date
               FROM leetcode_solutions
              WHERE solution_date < ? AND is_published = 1
              ORDER BY solution_date DESC
              LIMIT 1'
        );
        $stmtPrev->execute([$sol['solution_date']]);
        $prevSol = $stmtPrev->fetch() ?: null;

        $stmtNext = $pdo->prepare(
            'SELECT slug, problem_title, problem_number, solution_date
               FROM leetcode_solutions
              WHERE solution_date > ? AND is_published = 1
              ORDER BY solution_date ASC
              LIMIT 1'
        );
        $stmtNext->execute([$sol['solution_date']]);
        $nextSol = $stmtNext->fetch() ?: null;
    } catch (\Throwable) { /* non-fatal */ }

    /* â”€â”€â”€ Convert YouTube watch URL â†’ embed URL â”€â”€â”€ */
    $youtubeEmbed = null;
    if (!empty($sol['youtube_url'])) {
        $ytUrl = $sol['youtube_url'];
        // Handle https://youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $ytUrl, $m)) {
            $youtubeEmbed = 'https://www.youtube.com/embed/' . $m[1];
        }
        // Handle https://www.youtube.com/watch?v=VIDEO_ID
        elseif (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $ytUrl, $m)) {
            $youtubeEmbed = 'https://www.youtube.com/embed/' . $m[1];
        }
        // Already an embed URL
        elseif (str_contains($ytUrl, '/embed/')) {
            $youtubeEmbed = $ytUrl;
        }
    }

    /* â”€â”€â”€ Difficulty badge class â”€â”€â”€ */
    $diffClass = match(strtolower($sol['difficulty'] ?? '')) {
        'easy'   => 'badge-easy',
        'medium' => 'badge-medium',
        'hard'   => 'badge-hard',
        default  => 'badge-medium',
    };

    /* â”€â”€â”€ Page title â”€â”€â”€ */
    $numberPrefix = $sol['problem_number'] ? $sol['problem_number'] . '. ' : '';
    $pageTitle    = $numberPrefix . $sol['problem_title'];
    $metaDesc     = $sol['meta_description'] ?: ('LeetCode ' . $pageTitle . ' — Solution in Java, Python, C++, JavaScript with time and space complexity analysis.');
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   3. HTML Output
   â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if ($notFound): ?>
    <title>Problem Not Found — CodeByTushu LeetCode</title>
    <meta name="robots" content="noindex,nofollow">
    <?php else: ?>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — LeetCode Solution | CodeByTushu</title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= $isUpcoming ? 'noindex,nofollow' : 'index,follow' ?>">
    <link rel="canonical" href="<?= SITE_URL ?>/leetcode/problem/<?= htmlspecialchars($sol['slug'], ENT_QUOTES, 'UTF-8') ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="article">
    <meta property="og:title"       content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — LeetCode Solution">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url"         content="<?= SITE_URL ?>/leetcode/problem/<?= htmlspecialchars($sol['slug'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($sol['og_image_url'])): ?>
    <meta property="og:image"       content="<?= htmlspecialchars($sol['og_image_url'], ENT_QUOTES, 'UTF-8') ?>">
    <?php else: ?>
    <meta property="og:image"       content="<?= SITE_URL ?>/android-chrome-512x512.png">
    <?php endif; ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon"             href="/favicon.ico?v=6" sizes="any">
    <link rel="icon"             href="/favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=6">
    <link rel="manifest"         href="/site.webmanifest?v=6">
    <meta name="theme-color"     content="#ffc400">

    <!-- Theme init (prevents flash) -->
    <script src="/theme.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Main Website Styling System (root-relative) -->
    <link rel="stylesheet" href="/styles.css?v=40">
    <!-- LeetCode Module CSS -->
    <link rel="stylesheet" href="/Leetcode/CSS/style.css">
    <!-- Solution page specific overrides -->
    <link rel="stylesheet" href="/Leetcode/CSS/solution.css">

    <style>
        /* Prevent horizontal overflow site-wide */
        html, body { max-width: 100%; overflow-x: hidden; }
    </style>
</head>

<div class="problem-wrapper">

    <!-- PREMIUM NAVBAR -->
    <nav class="cbt-navbar navbar" id="mainNavbar" role="navigation" aria-label="Main navigation">
        <div class="cbt-nav-inner">
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
            <ul class="cbt-center-nav" id="cbt-center-nav" role="menubar">
                <li role="none"><a href="/Leetcode/"   class="cbt-nav-link" role="menuitem" tabindex="0">Home</a></li>
                <li role="none"><a href="/Leetcode/problems.php" class="cbt-nav-link active" role="menuitem" tabindex="0">Problems</a></li>
                <li role="none"><a href="/Leetcode/donate.php"   class="cbt-nav-link" role="menuitem" tabindex="0">Donate</a></li>
            </ul>
            <div class="cbt-nav-right" id="cbt-nav-right">
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn" aria-label="Open more links" aria-expanded="false">
                    <span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span>
                </button>
            </div>
            <div class="cbt-mobile-right" id="cbt-mobile-right">
                <button class="cbt-mobile-ham-btn" id="cbt-mobile-ham-btn" aria-label="Open mobile menu" aria-expanded="false">
                    <span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span><span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>
        <div class="cbt-panel-overlay" id="cbt-panel-overlay" aria-hidden="true" style="display:none;"></div>
        <div class="cbt-ham-panel" id="cbt-ham-panel" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;">
            <button class="cbt-panel-close" id="cbt-panel-close" aria-label="Close">&#x2715;</button>
            <p class="cbt-panel-label">CodeByTushu</p>
            <nav class="cbt-panel-nav">
                <a href="/privacy-policy/" class="cbt-panel-link">Privacy Policy</a>
                <a href="/terms/"          class="cbt-panel-link">Terms &amp; Conditions</a>
                <a href="/disclaimer/"     class="cbt-panel-link">Disclaimer</a>
            </nav>
        </div>
        <div class="cbt-mobile-overlay" id="cbt-mobile-overlay" aria-hidden="true" style="display:none;"></div>
        <div class="cbt-mobile-drawer" id="cbt-mobile-drawer" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="cbt-drawer-header">
                <div class="cbt-logo">
                    <a href="/Leetcode/" tabindex="-1" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
                        <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img" style="height:35px;">
                        <span class="cbt-logo-text" style="font-size:18px;">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                    </a>
                </div>
                <button class="cbt-drawer-close" id="cbt-drawer-close" aria-label="Close">&#x2715;</button>
            </div>
            <div class="cbt-drawer-body">
                <ul class="cbt-drawer-primary" role="menu">
                    <li><a href="/Leetcode/"   class="cbt-drawer-link" role="menuitem">Home</a></li>
                    <li><a href="/Leetcode/problems.php" class="cbt-drawer-link" role="menuitem">Problems</a></li>
                    <li><a href="/Leetcode/donate.php"   class="cbt-drawer-link" role="menuitem">Donate</a></li>
                </ul>
            </div>
        </div>
    </nav><!-- /.cbt-navbar -->

    <!-- BACKGROUND -->
    <div class="cbt-hero-bg" style="position:fixed;width:100vw;height:100vh;z-index:-1;">
        <div class="cbt-glow-center"></div>
        <div class="cbt-streak cbt-streak-1"></div>
        <div class="cbt-streak cbt-streak-2"></div>
        <div class="cbt-circle cbt-circle-left"></div>
        <div class="cbt-circle cbt-circle-right"></div>
    </div>

    <?php if ($notFound): ?>
    <div class="not-found">
        <h1>404</h1>
        <p>Problem not found. It may not be published yet or the URL is incorrect.</p>
        <a href="/Leetcode/problems.php" style="color:#ffc400;"><i class="fa-solid fa-arrow-left"></i> Browse All Problems</a>
    </div>

    <?php else: ?>

    <!-- Back button -->
    <a href="/Leetcode/month.php?id=<?= $sol['month_id'] ?>" class="back-btn sol-specific">
        <span style="margin-right: 6px;"><i class="fa-solid fa-arrow-left"></i></span> Back to Month
    </a>

    <!-- Problem meta row: difficulty + date -->
    <div class="problem-meta">
        <span class="<?= $diffClass ?>"><?= htmlspecialchars(strtoupper($sol['difficulty'] ?? 'Medium'), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="problem-date"><?= date('d M Y', strtotime($sol['solution_date'])) ?></span>
        <?php if (!empty($sol['leetcode_url'])): ?>
        <a href="<?= htmlspecialchars($sol['leetcode_url'], ENT_QUOTES, 'UTF-8') ?>"
           target="_blank" rel="noopener noreferrer"
           style="color:#666;font-size:13px;text-decoration:none;border-bottom:1px solid #333;padding-bottom:1px;">
            View on LeetCode <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:inherit; margin-left: 4px;"></i>
        </a>
        <?php endif; ?>
    </div>

    <section class="problem-section">

        <!-- ————————————
             LEFT PANEL
        ———————————— -->
        <div class="left-content">

            <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>

            <!-- Problem description / explanation -->
            <?php if (!empty($sol['explanation']) && !$isUpcoming): ?>
            <div class="problem-description">
                <h2>Problem Explanation</h2>
                <?= nl2br(htmlspecialchars($sol['explanation'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
            <?php endif; ?>

            <!-- Code Box -->
            <div class="code-box">

                <!-- Mac-style top bar -->
                <div class="code-topbar">
                    <div class="dots">
                        <span class="dot-red"></span>
                        <span class="dot-yellow"></span>
                        <span class="dot-green"></span>
                    </div>
                    <p>&lt;/&gt; Solution</p>
                </div>

                <?php if ($isFutureYear): ?>
                <!-- Future Year Message -->
                <div class="upcoming-message">
                    <span class="lock-icon">🔒</span>
                    This solution has not been published yet. Please check back later.
                </div>

                <?php elseif ($isComingSoon2026): ?>
                <!-- 2026 Custom Coming Soon Message -->
                <div class="upcoming-message" style="text-align: left;">
                    <h3 style="color: #ffc400; margin-bottom: 15px;">⏳ Solution Coming Soon</h3>
                    <p style="margin-bottom: 10px;">Sorry! This solution has not been added yet.</p>
                    <p style="margin-bottom: 10px; color: #aaa; line-height: 1.6;">I am gradually uploading all previous LeetCode solutions along with detailed explanations, optimized code, video tutorials, time complexity, and space complexity analysis.</p>
                    <p style="margin-bottom: 15px; color: #aaa;">Please check back later. This page will be updated soon.</p>
                    <p style="color: #fff;">Thank you for your patience and support. ❤️ <br>— CodeByTushu</p>
                </div>

                <?php elseif ($isUpcoming): ?>
                <!-- Upcoming / locked -->
                <div class="upcoming-message">
                    <span class="lock-icon">🔒</span>
                    Solution will be available on
                    <strong><?= date('d F Y', strtotime($sol['solution_date'])) ?></strong>.
                    <br><br>
                    Check back on the problem date — it will appear here automatically.
                </div>

                <?php elseif (empty($availableLangs)): ?>
                <!-- Published but no code added yet -->
                <div class="upcoming-message">
                    <span class="lock-icon">⌛</span>
                    Code solution is being added to the database. Check back shortly.
                </div>

                <?php else: ?>
                <!-- Language tabs -->
                <div class="code-tabs">
                    <div class="languages" id="lang-tabs">
                        <?php $isFirst = true; foreach ($availableLangs as $key => $meta): ?>
                        <button class="tab-btn <?= $isFirst ? 'active-tab' : '' ?>"
                                onclick="showCode('<?= htmlspecialchars($meta['id'], ENT_QUOTES, 'UTF-8') ?>', event)"
                                data-lang="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <?php $isFirst = false; endforeach; ?>
                    </div>
                    <button class="copy-btn" onclick="copyCode()">📋 Copy</button>
                </div>

                <!-- Code blocks — only rendered for languages that have code -->
                <?php $isFirst = true; foreach ($availableLangs as $key => $meta): ?>
                <pre id="code-<?= htmlspecialchars($meta['id'], ENT_QUOTES, 'UTF-8') ?>"
                     class="code-content"
                     <?= $isFirst ? '' : 'style="display:none;"' ?>><?= htmlspecialchars($codeBlocks[$key], ENT_QUOTES, 'UTF-8') ?></pre>
                <?php $isFirst = false; endforeach; ?>
                <?php endif; ?>

            </div><!-- /.code-box -->

        </div><!-- /.left-content -->


        <!-- ————————————
             RIGHT PANEL
        ———————————— -->
        <div class="right-content">

            <!-- YouTube Video -->
            <?php if ($youtubeEmbed && !$isUpcoming): ?>
            <div class="video-card">
                <iframe src="<?= htmlspecialchars($youtubeEmbed, ENT_QUOTES, 'UTF-8') ?>"
                        title="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> Solution Explanation"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        loading="lazy">
                </iframe>
            </div>
            <?php endif; ?>

            <!-- Time Complexity -->
            <?php if (!empty($sol['time_complexity'])): ?>
            <div class="info-card">
                <p>TIME COMPLEXITY</p>
                <h3><?= htmlspecialchars($sol['time_complexity'], ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <?php endif; ?>

            <!-- Space Complexity -->
            <?php if (!empty($sol['space_complexity'])): ?>
            <div class="info-card">
                <p>SPACE COMPLEXITY</p>
                <h3><?= htmlspecialchars($sol['space_complexity'], ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <?php endif; ?>

            <!-- Tags -->
            <?php if (!empty($tags)): ?>
            <div class="tags-card">
                <p>TOPICS</p>
                <div class="tags-list">
                    <?php foreach ($tags as $tag): ?>
                    <span class="tag-chip"
                          style="<?= !empty($tag['color_hex']) ? 'color:' . htmlspecialchars($tag['color_hex'], ENT_QUOTES, 'UTF-8') . ';border-color:' . htmlspecialchars($tag['color_hex'], ENT_QUOTES, 'UTF-8') . '33;' : '' ?>">
                        <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.right-content -->

    </section><!-- /.problem-section -->


    <!-- ————————————————————————————————————————————————————————————————————————
         PREV / NEXT NAVIGATION
    ———————————————————————————————————————————————————————————————————————— -->
    <?php if ($prevSol || $nextSol): ?>
    <nav class="solution-nav" aria-label="Problem navigation">

        <?php if ($prevSol): ?>
        <a href="<?= SITE_URL ?>/leetcode/problem/<?= htmlspecialchars($prevSol['slug'], ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link prev">
            <span class="nav-arrow"><i class="fa-solid fa-arrow-left"></i></span>
            <div class="nav-info">
                <span class="nav-label">Previous</span>
                <span class="nav-title">
                    <?= $prevSol['problem_number'] ? $prevSol['problem_number'] . '. ' : '' ?><?= htmlspecialchars($prevSol['problem_title'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </a>
        <?php else: ?>
        <div class="nav-placeholder"></div>
        <?php endif; ?>

        <?php if ($nextSol): ?>
        <a href="<?= SITE_URL ?>/leetcode/problem/<?= htmlspecialchars($nextSol['slug'], ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link next">
            <span class="nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
            <div class="nav-info">
                <span class="nav-label">Next</span>
                <span class="nav-title">
                    <?= $nextSol['problem_number'] ? $nextSol['problem_number'] . '. ' : '' ?><?= htmlspecialchars($nextSol['problem_title'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </a>
        <?php endif; ?>

    </nav>
    <?php endif; ?>

    <?php endif; /* end notFound else */ ?>

</div><!-- /.problem-wrapper -->


<?php if (!$notFound && !$isUpcoming && !empty($availableLangs)): ?>
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     JAVASCRIPT
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<script>
/* â”€â”€ Language tab switching â”€â”€ */
function showCode(langId, event) {
    // Hide all code blocks
    document.querySelectorAll('.code-content').forEach(function(el) {
        el.style.display = 'none';
    });

    // Show selected
    var target = document.getElementById('code-' + langId);
    if (target) target.style.display = 'block';

    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('active-tab');
    });
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active-tab');
    }
}

/* â”€â”€ Copy active code to clipboard â”€â”€ */
function copyCode() {
    var activeBlock = document.querySelector('.code-content[style*="display: block"], .code-content:not([style*="display: none"])');
    if (!activeBlock) return;

    var text = activeBlock.innerText || activeBlock.textContent;

    navigator.clipboard.writeText(text).then(function() {
        var btn = document.querySelector('.copy-btn');
        var orig = btn.innerHTML;
        btn.innerHTML = 'âœ… Copied!';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    }).catch(function() {
        /* Fallback for non-HTTPS or older browsers */
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(ta);
    });
}
</script>
<?php endif; ?>

<script src="/back-home.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var cbtNav = document.getElementById('mainNavbar');
        if (cbtNav) window.addEventListener('scroll', function () {
            cbtNav.classList.toggle('sticky', window.scrollY > 20);
        }, { passive: true });

        var deskHam = document.getElementById('cbt-hamburger-btn');
        var panel   = document.getElementById('cbt-ham-panel');
        var pOver   = document.getElementById('cbt-panel-overlay');
        var pClose  = document.getElementById('cbt-panel-close');
        var pOpen   = false;
        if (deskHam && panel) {
            function openP() { pOpen=true; pOver.style.display='block'; panel.style.display='flex'; requestAnimationFrame(function(){pOver.classList.add('active');panel.classList.add('is-open');}); deskHam.setAttribute('aria-expanded','true'); }
            function closeP() { pOpen=false; pOver.classList.remove('active'); panel.classList.remove('is-open'); deskHam.setAttribute('aria-expanded','false'); setTimeout(function(){if(!pOpen){pOver.style.display='none';panel.style.display='none';}},350); }
            deskHam.addEventListener('click', function(e){ e.stopPropagation(); pOpen ? closeP() : openP(); });
            if (pClose) pClose.addEventListener('click', closeP);
            if (pOver)  pOver.addEventListener('click', closeP);
        }

        var mobHam  = document.getElementById('cbt-mobile-ham-btn');
        var drawer  = document.getElementById('cbt-mobile-drawer');
        var dOver   = document.getElementById('cbt-mobile-overlay');
        var dClose  = document.getElementById('cbt-drawer-close');
        var dOpen   = false;
        if (mobHam && drawer) {
            function openD() { dOpen=true; dOver.style.display='block'; requestAnimationFrame(function(){dOver.classList.add('active');drawer.classList.add('is-open');}); mobHam.setAttribute('aria-expanded','true'); }
            function closeD() { dOpen=false; dOver.classList.remove('active'); drawer.classList.remove('is-open'); mobHam.setAttribute('aria-expanded','false'); setTimeout(function(){if(!dOpen)dOver.style.display='none';},350); }
            mobHam.addEventListener('click', function(e){ e.stopPropagation(); dOpen ? closeD() : openD(); });
            if (dClose) dClose.addEventListener('click', closeD);
            if (dOver)  dOver.addEventListener('click', closeD);
            drawer.querySelectorAll('.cbt-drawer-link').forEach(function(l){ l.addEventListener('click', closeD); });
        }
    });
</script>

</body>
</html>






