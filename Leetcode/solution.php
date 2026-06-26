<?php
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
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

/* ─────────────────────────────────────────────────────────────
   1. Resolve the solution from slug or date
   ───────────────────────────────────────────────────────────── */
$slug = trim($_GET['slug'] ?? '');
$date = trim($_GET['date'] ?? '');

$sol = null;

if ($slug !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM leetcode_solutions WHERE slug = ? LIMIT 1'
    );
    $stmt->execute([$slug]);
    $sol = $stmt->fetch();

} elseif ($date !== '') {
    // Legacy date-based URL — find and 301-redirect to slug
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        $sol = null;
    } else {
        $stmt = $pdo->prepare(
            'SELECT * FROM leetcode_solutions WHERE solution_date = ? LIMIT 1'
        );
        $stmt->execute([$date]);
        $sol = $stmt->fetch();

        if ($sol) {
            header('Location: ' . SITE_URL . '/leetcode/problem/' . $sol['slug'], true, 301);
            exit;
        }
    }
}

/* ─────────────────────────────────────────────────────────────
   2. Handle not-found / no parameters
   ───────────────────────────────────────────────────────────── */
if (!$sol) {
    http_response_code(404);
    $notFound = true;
} else {
    $notFound = false;

    /* ─── Is this an upcoming / unpublished problem? ─── */
    $currentDate = new \DateTime('today');
    $problemDate = new \DateTime($sol['solution_date']);
    $isUpcoming  = ($sol['is_published'] == 0 || $problemDate > $currentDate);

    $isComingSoon2026 = false;
    $isFutureYear = false;
    
    $year = (int)$problemDate->format('Y');
    if ($year >= 2027) {
        $isFutureYear = true;
        $isUpcoming = true;
    } elseif ($year == 2026) {
        $cutoff = new \DateTime('2026-04-13');
        if ($problemDate <= $cutoff) {
            $isComingSoon2026 = true;
            $isUpcoming = true;
        }
    }

    /* ─── Track view count (only for published, real visits) ─── */
    if (!$isUpcoming) {
        try {
            $pdo->prepare(
                'UPDATE leetcode_solutions SET view_count = view_count + 1 WHERE id = ?'
            )->execute([$sol['id']]);
        } catch (\Throwable) { /* non-fatal */ }
    }

    /* ─── Load code blocks ─── */
    $codeBlocks = [];
    if (!$isUpcoming) {
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
    }

    /* ─── Map language keys → display names & IDs ─── */
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

    /* ─── First available language (default active tab) ─── */
    $firstLangKey = array_key_first($availableLangs) ?? null;

    /* ─── Load tags ─── */
    $tags = [];
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

    /* ─── Prev / Next solutions ─── */
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

    /* ─── Convert YouTube watch URL → embed URL ─── */
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

    /* ─── Difficulty badge class ─── */
    $diffClass = match(strtolower($sol['difficulty'] ?? '')) {
        'easy'   => 'badge-easy',
        'medium' => 'badge-medium',
        'hard'   => 'badge-hard',
        default  => 'badge-medium',
    };

    /* ─── Page title ─── */
    $numberPrefix = $sol['problem_number'] ? $sol['problem_number'] . '. ' : '';
    $pageTitle    = $numberPrefix . $sol['problem_title'];
    $metaDesc     = $sol['meta_description'] ?: ('LeetCode ' . $pageTitle . ' — Solution in Java, Python, C++, JavaScript with time and space complexity analysis.');
}

/* ─────────────────────────────────────────────────────────────
   3. HTML Output
   ───────────────────────────────────────────────────────────── */
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
    <link rel="icon"             href="<?= SITE_URL ?>/favicon.ico?v=6" sizes="any">
    <link rel="icon"             href="<?= SITE_URL ?>/favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/apple-touch-icon.png?v=6">
    <link rel="manifest"         href="<?= SITE_URL ?>/site.webmanifest?v=6">
    <meta name="theme-color"     content="#f5a623">

    <!-- Shared solution stylesheet (single CSS for ALL problems) -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/Leetcode/CSS/solution.css">

    <!-- Theme init (prevents flash) -->
    <script src="<?= SITE_URL ?>/theme.js" defer></script>
</head>
<body>

<div class="problem-wrapper">

    <!-- ═══════════════════════════════════════════
         NAVBAR
    ═══════════════════════════════════════════ -->
    <header class="navbar">
        <div class="logo-area">
            <a href="<?= SITE_URL ?>/Leetcode/index.html">
                <div class="top-logo">
                    <h1 class="main-logo">
                        <span class="white-text">CODEBY</span><span class="gold-text">TUSHU</span>
                    </h1>
                </div>
                <div class="bottom-logo">
                    <h2 class="sub-logo">LEETCODE <span>UNLOCKED</span></h2>
                    <img src="<?= SITE_URL ?>/Leetcode/assets/logo.png/unlocked.png"
                         alt="LeetCode Unlocked icon"
                         class="unlock-icon"
                         onerror="this.style.display='none'">
                </div>
            </a>
        </div>

        <nav aria-label="Main navigation">
            <ul>
                <li><a href="<?= SITE_URL ?>/Leetcode/index.html">Home</a></li>
                <li><a href="<?= SITE_URL ?>/Leetcode/problems.php" class="active">Problems</a></li>
                <li><a href="<?= SITE_URL ?>/Leetcode/donate.php">Donate</a></li>
                <li><a href="<?= SITE_URL ?>/#contact">Contact</a></li>
            </ul>
        </nav>
    </header>


    <?php if ($notFound): ?>
    <!-- ═══════════════════════════════════════════
         404 NOT FOUND
    ═══════════════════════════════════════════ -->
    <div class="not-found">
        <h1>404</h1>
        <p>Problem not found. It may not be published yet or the URL is incorrect.</p>
        <a href="<?= SITE_URL ?>/Leetcode/problems.php">← Browse All Problems</a>
    </div>

    <?php else: ?>
    <!-- ═══════════════════════════════════════════
         PROBLEM CONTENT
    ═══════════════════════════════════════════ -->

    <!-- Back button -->
    <div class="back-btn">
        <a href="<?= SITE_URL ?>/Leetcode/problems.php">← Back to Problems</a>
    </div>

    <!-- Problem meta row: category tag + difficulty + date -->
    <div class="problem-meta">
        <span class="tag">ALGORITHM</span>
        <span class="<?= $diffClass ?>"><?= htmlspecialchars(strtoupper($sol['difficulty']), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="problem-date"><?= date('d M Y', strtotime($sol['solution_date'])) ?></span>
        <?php if (!empty($sol['leetcode_url'])): ?>
        <a href="<?= htmlspecialchars($sol['leetcode_url'], ENT_QUOTES, 'UTF-8') ?>"
           target="_blank" rel="noopener noreferrer"
           style="color:#666;font-size:13px;text-decoration:none;border-bottom:1px solid #333;padding-bottom:1px;">
            View on LeetCode ↗
        </a>
        <?php endif; ?>
    </div>

    <section class="problem-section">

        <!-- ════════════
             LEFT PANEL
        ════════════ -->
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
                    <p style="color: #fff;">Thank you for your patience and support. ❤️<br>– CodeByTushu</p>
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
                    <span class="lock-icon">⏳</span>
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


        <!-- ════════════
             RIGHT PANEL
        ════════════ -->
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


    <!-- ═══════════════════════════════════════════
         PREV / NEXT NAVIGATION
    ═══════════════════════════════════════════ -->
    <?php if ($prevSol || $nextSol): ?>
    <nav class="solution-nav" aria-label="Problem navigation">

        <?php if ($prevSol): ?>
        <a href="<?= SITE_URL ?>/leetcode/problem/<?= htmlspecialchars($prevSol['slug'], ENT_QUOTES, 'UTF-8') ?>"
           class="nav-link prev">
            <span class="nav-arrow">←</span>
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
            <span class="nav-arrow">→</span>
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
<!-- ═══════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════ -->
<script>
/* ── Language tab switching ── */
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

/* ── Copy active code to clipboard ── */
function copyCode() {
    var activeBlock = document.querySelector('.code-content[style*="display: block"], .code-content:not([style*="display: none"])');
    if (!activeBlock) return;

    var text = activeBlock.innerText || activeBlock.textContent;

    navigator.clipboard.writeText(text).then(function() {
        var btn = document.querySelector('.copy-btn');
        var orig = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
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

</body>
</html>






