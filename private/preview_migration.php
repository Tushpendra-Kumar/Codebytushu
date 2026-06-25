<?php
$baseDir = __DIR__ . '/../Leetcode';
$files = [];

// Recursively find all HTML files ending in -Next.html
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '-Next.html')) {
        $files[] = $file->getPathname();
    }
}

$results = [];
$rejected = [];

foreach ($files as $filePath) {
    $filename = basename($filePath);
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})-Next\.html$/', $filename, $matches)) {
        $rejected[] = $filename;
        continue;
    }
    $date = $matches[1];
    
    $html = file_get_contents($filePath);
    if (!$html) continue;

    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    // Title & Number
    // Exclude the navbar h1
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

    $results[] = [
        'file' => $filename,
        'date' => $date,
        'problem_number' => $problemNumber,
        'problem_title' => $problemTitle,
        'youtube_url' => $videoUrl,
        'time_complexity' => $timeComplexity,
        'space_complexity' => $spaceComplexity,
        'codes' => [
            'Java' => strlen(trim($javaCode)) > 20 ? 'Found' : 'Empty',
            'Python' => strlen(trim($pythonCode)) > 30 ? 'Found' : 'Empty',
            'C++' => strlen(trim($cppCode)) > 30 ? 'Found' : 'Empty',
            'JavaScript' => strlen(trim($jsCode)) > 30 ? 'Found' : 'Empty'
        ]
    ];
}

usort($results, fn($a, $b) => strcmp($a['date'], $b['date']));

$summary = [
    'total_files_detected' => count($files),
    'total_valid_records_to_insert' => count($results),
    'rejected_files_count' => count($rejected),
    'rejected_sample' => array_slice($rejected, 0, 5),
    'preview_data' => array_slice($results, 0, 5) // Show first 5
];

echo json_encode($summary, JSON_PRETTY_PRINT);
