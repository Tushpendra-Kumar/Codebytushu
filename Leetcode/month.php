<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
Auth::requireLogin();

/**
 * CodeByTushu — LeetCode Month Timeline
 * Displays all daily problems for a given month dynamically.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();
$monthId = (int)get('id', '0');
if (!$monthId) {
    redirect('/Leetcode/problems.php');
}

$stmt = $pdo->prepare("SELECT m.*, y.year as y_year FROM leetcode_months m JOIN leetcode_years y ON m.year_id = y.id WHERE m.id = ? LIMIT 1");
$stmt->execute([$monthId]);
$month = $stmt->fetch();

if (!$month) {
    redirect('/Leetcode/problems.php');
}

$stmtDays = $pdo->prepare("SELECT id, solution_date, problem_title, slug, difficulty, problem_number FROM leetcode_solutions WHERE month_id = ? ORDER BY solution_date ASC");
$stmtDays->execute([$monthId]);
$days = $stmtDays->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($month['month_name']) ?> <?= e($month['y_year']) ?> LeetCode Solutions — CodeByTushu">
    <title><?= e($month['month_name']) ?> <?= e($month['y_year']) ?> Problems | CodeByTushu</title>

    <link rel="icon"             href="/favicon.ico?v=6"                 sizes="any">
    <link rel="icon"             href="/favicon-32x32.png?v=6"           type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=6"        sizes="180x180">
    <meta name="theme-color"     content="#ffc400">

    <script src="/theme.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Main Website Styling System (root-relative) -->
    <link rel="stylesheet" href="/styles.css?v=40">

    <!-- LeetCode Specific Overrides (root-relative) -->
    <link rel="stylesheet" href="/Leetcode/CSS/style.css">
    <link rel="stylesheet" href="/Leetcode/auth.css">
    
    <style>
        .cbt-content-locked { filter: none !important; -webkit-filter: none !important; }
        #cbt-auth-overlay { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; background: rgba(0,0,0,0.9) !important; }
        #cbt-auth-overlay * { filter: none !important; -webkit-filter: none !important; }
        .cbt-auth-modal { filter: none !important; -webkit-filter: none !important; }

        /* â”€â”€ Day Grid â”€â”€ */
        .days-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            padding: 28px 4%;
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
            /* Equal-height cards via stretch */
            align-items: stretch;
        }

        /* â”€â”€ Day Card â”€â”€ */
        .day-card {
            background: #111118;
            border: 1px solid rgba(255,196,0,0.15);
            border-radius: 14px;
            padding: 18px 10px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;  /* always top-align so heights stay equal */
            text-align: center;
            height: 100%;                 /* fill entire grid cell â†’ equal heights */
            box-sizing: border-box;
            transition: all 0.3s ease;
            overflow: hidden;
            word-break: break-word;
        }
        .day-card:hover {
            border-color: rgba(255,196,0,0.5);
            transform: translateY(-4px);
            background: rgba(255,196,0,0.04);
            box-shadow: 0 10px 24px rgba(0,0,0,0.35);
        }
        .day-card .day-num {
            font-size: 34px;
            font-weight: 800;
            color: rgba(255,196,0,0.2);
            line-height: 1;
            display: block;
        }
        .day-card h3 {
            color: #fff;
            margin: 8px 0 4px;
            font-size: 15px;
            font-weight: 600;
        }
        .day-card .day-title {
            color: #888;
            font-size: 12px;
            line-height: 1.4;
            margin-bottom: 8px;
            max-width: 100%;
        }
        .day-card .diff-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 10px;
            font-weight: 700;
            white-space: nowrap;    /* prevent 'Medi\num' word-break */
            margin-top: auto;       /* push badge to bottom of card */
        }
        .diff-easy   { background: rgba(44,186,126,0.12); color: #2cba7e; }
        .diff-medium { background: rgba(255,196,0,0.12);  color: #ffc400; }
        .diff-hard   { background: rgba(255,55,95,0.12);  color: #ff375f; }

        /* â”€â”€ Back btn â”€â”€ */
        .month-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 24px 5% 0;
            padding: 10px 20px;
            background: rgba(255,196,0,0.08);
            border: 1px solid rgba(255,196,0,0.3);
            border-radius: 30px;
            color: #ffc400;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .month-back-btn:hover {
            background: rgba(255,196,0,0.15);
            transform: translateX(-3px);
        }

        /* â”€â”€ Problems Page Wrapper â”€â”€ */
        .problems-page {
            flex-direction: column;
        }

        /* â”€â”€ Section heading â”€â”€ */
        .month-section-head {
            text-align: center;
            max-width: 900px;
            margin: 20px auto 0;
            padding: 0 5%;
        }
        .month-section-head h1 {
            font-size: clamp(22px, 5vw, 36px);
            color: #fff;
            margin-bottom: 8px;
        }
        .month-section-head p {
            color: #888;
            font-size: 15px;
        }

        /* â”€â”€ Mobile: Stack header & 1-col grid â”€â”€ */
        @media (max-width: 640px) {
            .problems-page {
                margin-top: 40px;
            }
            .month-section-head {
                max-width: 100%;
                margin-bottom: 25px;
                padding: 0 4%;
            }
            .days-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 10px 4%;
                width: 100%;
            }
            .day-card {
                padding: 18px 12px;
                border-radius: 14px;
                min-height: auto;
            }
            .day-card .day-num { font-size: 26px; margin-bottom: 4px; }
            .day-card h3 { font-size: 16px; margin: 4px 0 6px; }
            .day-card .day-title { font-size: 13px; margin-bottom: 8px; }
            .day-card .diff-badge { font-size: 11px; padding: 4px 12px; margin-top: 4px; }
        }
        @media (max-width: 380px) {
            .days-grid { gap: 8px; padding: 12px 3%; }
        }

        /* â”€â”€ Prevent horizontal overflow â”€â”€ */
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }
    </style>
</head>

<body>

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
                <li role="none"><a href="/Leetcode/"   class="cbt-nav-link" id="nav-home"    role="menuitem" tabindex="0">Home</a></li>
                <li role="none"><a href="/Leetcode/problems.php" class="cbt-nav-link active" id="nav-problems" role="menuitem" tabindex="0">Problems</a></li>
                <li role="none"><a href="/Leetcode/donate.php"  class="cbt-nav-link" id="nav-donate"  role="menuitem" tabindex="0">Donate</a></li>
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
        <div class="cbt-panel-overlay" id="cbt-panel-overlay" aria-hidden="true"></div>
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
    </nav>

    <!-- BACKGROUND -->
    <div class="cbt-hero-bg" style="position:fixed;width:100vw;height:100vh;z-index:-1;">
        <div class="cbt-glow-center"></div>
        <div class="cbt-streak cbt-streak-1"></div><div class="cbt-streak cbt-streak-2"></div>
        <div class="cbt-circle cbt-circle-left"></div><div class="cbt-circle cbt-circle-right"></div>
    </div>

    <br><br><br><br>

    <a href="/Leetcode/problems.php?year=<?= (int)$month['y_year'] ?>" class="month-back-btn"><i class="fa-solid fa-arrow-left"></i> Back to All Months</a>

    <section class="problems-page">
        <div class="month-section-head">
            <h1><?= e($month['month_name']) ?> <?= e($month['y_year']) ?> Problems</h1>
            <p>Select any day to explore the LeetCode daily coding challenge solution.</p>
        </div>

        <div class="days-grid">
            <?php 
            $totalDays = (int)$month['total_days'];
            
            // Pre-process days for O(1) lookup
            $mappedDays = [];
            foreach ($days as $d) {
                $mappedDays[(int)date('d', strtotime($d['solution_date']))] = $d;
            }

            for ($day = 1; $day <= $totalDays; $day++): 
                // Check if a solution exists for this day
                $sol = $mappedDays[$day] ?? null;
                
                $paddedDay = str_pad((string)$day, 2, '0', STR_PAD_LEFT);
            ?>
                <a href="<?= SITE_URL ?>/Leetcode/solution.php?month=<?= (int)$month['id'] ?>&day=<?= $day ?>" class="day-card">
                    <span class="day-num"><?= $paddedDay ?></span>
                    <h3>Day <?= $paddedDay ?></h3>

                    <?php if ($sol): ?>
                        <div class="day-title"><?= e($sol['problem_title']) ?></div>
                        <?php if ($sol['difficulty']): ?>
                            <span class="diff-badge diff-<?= strtolower($sol['difficulty']) ?>"><?= e($sol['difficulty']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="day-title" style="font-style:italic;color:#555;">Upcoming Solution</div>
                    <?php endif; ?>
                </a>
            <?php endfor; ?>
        </div>
    </section>

    <script src="/back-home.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var cbtNav = document.getElementById('mainNavbar');
            if (cbtNav) window.addEventListener('scroll', function () {
                cbtNav.classList.toggle('sticky', window.scrollY > 20);
            }, { passive: true });
            var deskHam = document.getElementById('cbt-hamburger-btn');
            var panel = document.getElementById('cbt-ham-panel');
            var pOver = document.getElementById('cbt-panel-overlay');
            var pClose = document.getElementById('cbt-panel-close');
            var pOpen = false;
            if (deskHam && panel) {
                function openP() { pOpen=true; pOver.style.display='block'; panel.style.display='flex'; requestAnimationFrame(function(){pOver.classList.add('active');panel.classList.add('is-open');}); deskHam.setAttribute('aria-expanded','true'); }
                function closeP() { pOpen=false; pOver.classList.remove('active'); panel.classList.remove('is-open'); deskHam.setAttribute('aria-expanded','false'); setTimeout(function(){if(!pOpen){pOver.style.display='none';panel.style.display='none';}},350); }
                deskHam.addEventListener('click', function(e){e.stopPropagation(); pOpen?closeP():openP();});
                if(pClose) pClose.addEventListener('click', closeP);
                if(pOver) pOver.addEventListener('click', closeP);
            }
            var mobHam = document.getElementById('cbt-mobile-ham-btn');
            var drawer = document.getElementById('cbt-mobile-drawer');
            var dOver = document.getElementById('cbt-mobile-overlay');
            var dClose = document.getElementById('cbt-drawer-close');
            var dOpen = false;
            if (mobHam && drawer) {
                function openD() { dOpen=true; dOver.style.display='block'; requestAnimationFrame(function(){dOver.classList.add('active');drawer.classList.add('is-open');}); mobHam.setAttribute('aria-expanded','true'); }
                function closeD() { dOpen=false; dOver.classList.remove('active'); drawer.classList.remove('is-open'); mobHam.setAttribute('aria-expanded','false'); setTimeout(function(){if(!dOpen)dOver.style.display='none';},350); }
                mobHam.addEventListener('click', function(e){e.stopPropagation(); dOpen?closeD():openD();});
                if(dClose) dClose.addEventListener('click', closeD);
                if(dOver) dOver.addEventListener('click', closeD);
                drawer.querySelectorAll('.cbt-drawer-link').forEach(function(l){l.addEventListener('click',closeD);});
            }
        });
    </script>

</body>
</html>


