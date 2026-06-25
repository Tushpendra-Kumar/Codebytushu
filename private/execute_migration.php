<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$baseDir = __DIR__ . '/../Leetcode';
$files = [];

// Recursively find all HTML files ending in -Next.html
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '-Next.html')) {
        $files[] = $file->getPathname();
    }
}

$insertedRecords = 0;
$skippedRecords = 0;

foreach ($files as $filePath) {
    $filename = basename($filePath);
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})-Next\.html$/', $filename, $matches)) {
        continue;
    }
    $date = $matches[1];
    
    $html = file_get_contents($filePath);
    if (!$html || trim($html) === '') {
        continue; // skip 0-byte or empty files
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    // Title & Number
    $titleNodes = $xpath->query('//div[@class="left-content"]//h1');
    if ($titleNodes->length > 0) {
        $titleNode = $titleNodes->item(0);
    } else {
        $titleNode = $xpath->query('//h1[not(contains(@class, "main-logo"))]')->item(0);
    }
    
    $titleText = $titleNode ? trim($titleNode->textContent) : '';
    
    $problemNumber = null;
    $problemTitle = $titleText;
    if (preg_match('/^(\d+)\s+(.+)$/', $titleText, $tMatches)) {
        $problemNumber = (int)$tMatches[1];
        $problemTitle = trim($tMatches[2]);
    }
    
    // Slug generation
    $slugBase = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $problemTitle)));
    $slug = $slugBase ?: 'problem-' . $date;

    // Code blocks
    $javaCode = $xpath->query('//pre[@id="java-code"]')->item(0)?->textContent ?? '';
    $pythonCode = $xpath->query('//pre[@id="python-code"]')->item(0)?->textContent ?? '';
    $cppCode = $xpath->query('//pre[@id="cpp-code"]')->item(0)?->textContent ?? '';
    $jsCode = $xpath->query('//pre[@id="javascript-code"]')->item(0)?->textContent ?? '';

    // Video URL
    $videoNode = $xpath->query('//iframe')->item(0);
    $videoUrl = $videoNode ? $videoNode->getAttribute('src') : null;

    // Complexities
    $infoCards = $xpath->query('//div[contains(@class, "info-card")]');
    $timeComplexity = null;
    $spaceComplexity = null;
    
    foreach ($infoCards as $card) {
        $p = $xpath->query('.//p', $card)->item(0)?->textContent ?? '';
        $h3 = $xpath->query('.//h3', $card)->item(0)?->textContent ?? '';
        
        if (stripos($p, 'TIME COMPLEXITY') !== false) {
            $timeComplexity = trim($h3);
        }
        if (stripos($p, 'SPACE COMPLEXITY') !== false) {
            $spaceComplexity = trim($h3);
        }
    }

    // Check if duplicate date or slug exists
    $dupDate = $pdo->prepare('SELECT id FROM leetcode_solutions WHERE solution_date = ? LIMIT 1');
    $dupDate->execute([$date]);
    if ($dupDate->fetch()) {
        $skippedRecords++;
        continue;
    }

    // Handle slug collisions by appending random string if needed
    $dupSlug = $pdo->prepare('SELECT id FROM leetcode_solutions WHERE slug = ? LIMIT 1');
    $dupSlug->execute([$slug]);
    if ($dupSlug->fetch()) {
        $slug .= '-' . rand(1000, 9999);
    }

    // Parse date for year/month
    $dt = \DateTime::createFromFormat('Y-m-d', $date);
    $year  = (int)$dt->format('Y');
    $month = (int)$dt->format('m');
    $day   = (int)$dt->format('d');

    // Month detection
    $ms = $pdo->prepare('SELECT id FROM leetcode_months WHERE year=? AND month_num=? LIMIT 1');
    $ms->execute([$year, $month]);
    $mon = $ms->fetch();
    $monthId = $mon ? (int)$mon['id'] : null;

    if (!$monthId) {
        $monthsArr = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $pdo->prepare('INSERT IGNORE INTO leetcode_months (year, month_num, month_name) VALUES (?,?,?)')
            ->execute([$year, $month, $monthsArr[$month]]);
        $monthId = (int)$pdo->lastInsertId();
        if (!$monthId) {
            $ms->execute([$year, $month]);
            $monthId = (int)($ms->fetch()['id'] ?? 0);
        }
    }

    // Current Date logic for is_published
    $currentDate = new \DateTime();
    $isPublished = ($dt <= $currentDate) ? 1 : 0;
    $publishedAt = $isPublished ? $dt->format('Y-m-d H:i:s') : null;

    // Insert into leetcode_solutions
    $stmt = $pdo->prepare('INSERT INTO leetcode_solutions 
        (month_id, solution_date, day_number, year, month, slug, problem_title, problem_number, platform, difficulty, time_complexity, space_complexity, youtube_url, is_published, published_at, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    
    $stmt->execute([
        $monthId, $date, $day, $year, $month, $slug, $problemTitle, $problemNumber, 'LeetCode', 'Medium', $timeComplexity, $spaceComplexity, $videoUrl, $isPublished, $publishedAt
    ]);

    $solutionId = $pdo->lastInsertId();

    // Insert code blocks
    $blocks = [
        ['language' => 'Java', 'slug' => 'java', 'code' => $javaCode, 'is_primary' => 1, 'sort' => 1],
        ['language' => 'Python', 'slug' => 'python', 'code' => $pythonCode, 'is_primary' => 0, 'sort' => 2],
        ['language' => 'C++', 'slug' => 'cpp', 'code' => $cppCode, 'is_primary' => 0, 'sort' => 3],
        ['language' => 'JavaScript', 'slug' => 'javascript', 'code' => $jsCode, 'is_primary' => 0, 'sort' => 4],
    ];

    $blockStmt = $pdo->prepare('INSERT INTO solution_code_blocks (solution_id, language, language_slug, code, is_primary, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($blocks as $b) {
        $blockStmt->execute([$solutionId, $b['language'], $b['slug'], trim($b['code']), $b['is_primary'], $b['sort']]);
    }

    $insertedRecords++;
}

echo "Migration Complete!\n";
echo "Inserted: $insertedRecords\n";
echo "Skipped (Duplicate Date): $skippedRecords\n";
