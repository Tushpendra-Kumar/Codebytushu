<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

$yearsToGenerate = [2026, 2027, 2028];

echo "Starting generation...\n";

foreach ($yearsToGenerate as $year) {
    echo "Processing Year: $year\n";

    // Insert Year if not exists
    $stmt = $pdo->prepare("SELECT id FROM leetcode_years WHERE year = ?");
    $stmt->execute([$year]);
    $yearId = $stmt->fetchColumn();

    if (!$yearId) {
        $pdo->prepare("INSERT INTO leetcode_years (year, badge_label, description, status, is_visible) VALUES (?, 'UPCOMING ARCHIVE', 'Daily LeetCode problem solutions.', 'active', 1)")->execute([$year]);
        $yearId = $pdo->lastInsertId();
    }

    for ($monthNum = 1; $monthNum <= 12; $monthNum++) {
        $monthName = date('F', mktime(0, 0, 0, $monthNum, 10));
        $monthShort = date('M', mktime(0, 0, 0, $monthNum, 10));
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $monthNum, $year);

        // Insert Month if not exists
        $stmt = $pdo->prepare("SELECT id FROM leetcode_months WHERE year_id = ? AND month_num = ?");
        $stmt->execute([$yearId, $monthNum]);
        $monthId = $stmt->fetchColumn();

        if (!$monthId) {
            $pdo->prepare("INSERT INTO leetcode_months (year_id, month_num, month_name, month_short, total_days, published_solutions, is_visible) VALUES (?, ?, ?, ?, ?, 0, 1)")->execute([$yearId, $monthNum, $monthName, $monthShort, $totalDays]);
            $monthId = $pdo->lastInsertId();
        }

        for ($day = 1; $day <= $totalDays; $day++) {
            $dateStr = sprintf("%04d-%02d-%02d", $year, $monthNum, $day);

            // Check if solution exists
            $stmt = $pdo->prepare("SELECT id FROM leetcode_solutions WHERE solution_date = ?");
            $stmt->execute([$dateStr]);
            $solId = $stmt->fetchColumn();

            if (!$solId) {
                // Generate a unique slug based on date
                $slug = "unpublished-solution-$dateStr";
                $title = "Daily LeetCode Problem";
                
                $pdo->prepare("INSERT INTO leetcode_solutions (month_id, solution_date, problem_title, slug, difficulty, platform, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, 'Medium', 'LeetCode', 1, NOW(), NOW())")->execute([$monthId, $dateStr, $title, $slug]);
            }
        }
        
        // Update month published_solutions count
        
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM leetcode_solutions WHERE month_id = ? AND is_published = 1");
    $stmtCount->execute([$monthId]);
    $count = $stmtCount->fetchColumn();
    $pdo->prepare("UPDATE leetcode_months SET published_solutions = ? WHERE id = ?")->execute([$count, $monthId]);

    }
}

echo "Generation complete!\n";
?>

