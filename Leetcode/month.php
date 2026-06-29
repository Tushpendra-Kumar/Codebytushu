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

    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/Leetcode/auth.css">
    
    <style>
        .cbt-content-locked { filter: none !important; -webkit-filter: none !important; }
        #cbt-auth-overlay { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; background: rgba(0,0,0,0.9) !important; }
        #cbt-auth-overlay * { filter: none !important; -webkit-filter: none !important; }
        .cbt-auth-modal { filter: none !important; -webkit-filter: none !important; }
    </style>
</head>

<body>
    <header class="navbar">
        <div class="logo-area">
            <div class="top-logo">
                <h1 class="main-logo">
                    <a href="<?= SITE_URL ?>/Leetcode/index.html" style="text-decoration:none;">
                        <span class="white-text">CODEBY</span><span class="gold-text">TUSHU</span>
                    </a>
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
            </ul>
        </nav>
    </header>

    <br><br><br><br>

    <div class="back-btn" style="margin-left: 5%; margin-top: 20px;">
        <a href="<?= SITE_URL ?>/Leetcode/problems.php" style="color: #ffc400; text-decoration: none; font-weight: 600;">← Back to All Months</a>
    </div>

    <style>
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
    <section class="problems-page">
        <div class="problems-content" style="text-align: center; max-width: 900px; margin: 0 auto;">
            <h1><?= e($month['month_name']) ?> <?= e($month['y_year']) ?> Problems</h1>
            <p>Select any day to explore the LeetCode daily coding challenge solution.</p>
        </div>

        <div class="days-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; padding: 40px 5%; max-width: 1200px; margin: 0 auto;">
            <?php 
            $totalDays = (int)$month['total_days'];
            for ($day = 1; $day <= $totalDays; $day++): 
                // Check if a solution exists for this day
                $sol = null;
                foreach ($days as $d) {
                    if ((int)date('d', strtotime($d['solution_date'])) === $day) {
                        $sol = $d;
                        break;
                    }
                }
                
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

</body>
</html>


