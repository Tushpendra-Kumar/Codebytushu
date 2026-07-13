<?php
/**
 * CodeByTushu — LeetCode Month Timeline
 * Displays all daily problems for a given month dynamically.
 */
declare(strict_types=1);

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
    <link rel="stylesheet" href="/styles.css?v=33">

    <!-- LeetCode Specific Overrides (root-relative) -->
    <link rel="stylesheet" href="/Leetcode/CSS/style.css">
    <link rel="stylesheet" href="/Leetcode/auth.css">
    
    <style>
        .cbt-content-locked { filter: none !important; -webkit-filter: none !important; }
        #cbt-auth-overlay { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; background: rgba(0,0,0,0.9) !important; }
        #cbt-auth-overlay * { filter: none !important; -webkit-filter: none !important; }
        .cbt-auth-modal { filter: none !important; -webkit-filter: none !important; }

        .day-card {
            background: #111118;
            border: 1px solid rgba(255,196,0,0.15);
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .day-card:hover {
            border-color: rgba(255,196,0,0.5);
            transform: translateY(-5px);
            background: rgba(255,196,0,0.05);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
    </style>
</head>

<body>

    <!-- PREMIUM NAVBAR -->
    <nav class="cbt-navbar navbar" id="mainNavbar" role="navigation" aria-label="Main navigation">
        <div class="cbt-nav-inner">
            <div class="cbt-logo" id="cbt-logo">
                <a href="/Leetcode/index.html" id="cbt-logo-link" aria-label="CodeByTushu Home" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
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
                <li role="none"><a href="/Leetcode/index.html"   class="cbt-nav-link" id="nav-home"    role="menuitem" tabindex="0">Home</a></li>
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
                    <a href="/Leetcode/index.html" tabindex="-1" style="display:flex;align-items:center;gap:7px;text-decoration:none;">
                        <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img" style="height:35px;">
                        <span class="cbt-logo-text" style="font-size:18px;">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                    </a>
                </div>
                <button class="cbt-drawer-close" id="cbt-drawer-close" aria-label="Close">&#x2715;</button>
            </div>
            <div class="cbt-drawer-body">
                <ul class="cbt-drawer-primary" role="menu">
                    <li><a href="/Leetcode/index.html"   class="cbt-drawer-link" role="menuitem">Home</a></li>
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

    <div class="back-btn" style="margin-left: 5%; margin-top: 20px;">
        <a href="<?= SITE_URL ?>/Leetcode/problems.php" style="color: #ffc400; text-decoration: none; font-weight: 600;">← Back to All Months</a>
    </div>
    <section class="problems-page">
        <div class="problems-content" style="text-align: center; max-width: 900px; margin: 0 auto;">
            <h1><?= e($month['month_name']) ?> <?= e($month['y_year']) ?> Problems</h1>
            <p>Select any day to explore the LeetCode daily coding challenge solution.</p>
        </div>

        <div class="days-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; padding: 40px 5%; max-width: 1200px; margin: 0 auto;">
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
                    <span style="font-size: 36px; font-weight: 800; color: rgba(255,196,0,0.2); line-height: 1;"><?= $paddedDay ?></span>
                    <h3 style="color: #fff; margin: 10px 0 5px; font-size: 16px;">Day <?= $paddedDay ?></h3>
                    
                    <?php if ($sol): ?>
                        <div style="color: #888; font-size: 12px; text-align: center; margin-bottom: 8px;"><?= e($sol['problem_title']) ?></div>
                        <?php if ($sol['difficulty']): ?>
                            <div style="font-size: 11px; padding: 2px 8px; border-radius: 10px; <?= strtolower($sol['difficulty']) === 'easy' ? 'background: rgba(44, 186, 126, 0.1); color: #2cba7e;' : (strtolower($sol['difficulty']) === 'medium' ? 'background: rgba(255, 196, 0, 0.1); color: #ffc400;' : 'background: rgba(255, 55, 95, 0.1); color: #ff375f;') ?>"><?= e($sol['difficulty']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color: #666; font-size: 12px; text-align: center; font-style: italic; margin-bottom: 8px;">Upcoming Solution</div>
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


