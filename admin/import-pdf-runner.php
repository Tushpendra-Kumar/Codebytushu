<?php
/**
 * /admin/import-pdf-runner.php
 * ─────────────────────────────────────────────────────────────
 * Fixed version: Uses PHP-level column existence checks instead of
 * "ADD COLUMN IF NOT EXISTS" (which is not supported on all MySQL versions).
 *
 * Run once on server: https://codebytushu.com/admin/import-pdf-runner.php
 */
declare(strict_types=1);

// ─── Basic HTML wrapper ────────────────────────────────────
echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Course Import Runner</title>
<style>
  body{font-family:monospace;padding:30px;background:#0d0d0d;color:#ccc;line-height:1.8;}
  h2{color:#ffc400;border-bottom:1px solid #333;padding-bottom:6px;}
  .ok{color:#22c55e;} .warn{color:#f59e0b;} .err{color:#f44336;}
  .box{background:#111;border:1px solid #333;border-radius:8px;padding:20px;margin-top:20px;}
  a.btn{display:inline-block;margin-top:20px;background:#22c55e;color:#000;padding:10px 24px;
        border-radius:8px;text-decoration:none;font-weight:700;}
</style></head><body>';

// Only allow admin access (skip check if run from CLI)
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/includes/auth_check.php';
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/app.php';
require_once $rootDir . '/config/database.php';

$pdo = db();

// ═══════════════════════════════════════════════════════════
// STEP 1 — Add missing columns using PHP-level checks
// ═══════════════════════════════════════════════════════════
echo '<h2>Step 1: Adding Missing Columns to `courses` Table</h2>';

/**
 * Check if a column exists in the given table.
 */
function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
    $stmt->execute([':col' => $column]);
    return (bool) $stmt->fetch();
}

/**
 * Add a column safely (only if it does not exist).
 */
function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void {
    if (columnExists($pdo, $table, $column)) {
        echo "<span class='warn'>⚠ Column `{$column}` already exists — skipped.</span><br>";
        return;
    }
    try {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "<span class='ok'>✓ Added column `{$column}`</span><br>";
    } catch (PDOException $e) {
        echo "<span class='err'>✗ Failed to add `{$column}`: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    }
}

// Columns to add
$columns = [
    'download_file_path' => "VARCHAR(500) DEFAULT NULL COMMENT 'Relative path inside private/courses/'",
    'content_type'       => "VARCHAR(20) NOT NULL DEFAULT 'pdf' COMMENT 'pdf or video'",
    'price_usd'          => "DECIMAL(10,2) DEFAULT NULL COMMENT 'Price in USD'",
    'seo_title'          => "VARCHAR(70) DEFAULT NULL COMMENT 'SEO page title'",
    'seo_description'    => "VARCHAR(165) DEFAULT NULL COMMENT 'SEO meta description'",
    'seo_keywords'       => "VARCHAR(500) DEFAULT NULL COMMENT 'SEO keywords'",
    'import_source'      => "VARCHAR(200) DEFAULT NULL COMMENT 'Original PDF filename if auto-imported'",
];

foreach ($columns as $col => $def) {
    addColumnIfMissing($pdo, 'courses', $col, $def);
}

echo "<br><span class='ok'><strong>✓ Column migration complete.</strong></span>";

// ═══════════════════════════════════════════════════════════
// STEP 2 — Check if course already exists
// ═══════════════════════════════════════════════════════════
echo '<h2>Step 2: Checking Existing Course</h2>';

// Now that import_source column exists, check for duplicate
try {
    $existsStmt = $pdo->prepare("SELECT id, title FROM courses WHERE import_source = ? OR slug = ? LIMIT 1");
    $existsStmt->execute(['Java Masterclass for Beginners.pdf', 'java-masterclass-for-beginners']);
    $existingCourse = $existsStmt->fetch();
} catch (PDOException $e) {
    $existingCourse = false;
    echo "<span class='err'>Could not check: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

if ($existingCourse) {
    $cid = $existingCourse['id'];
    echo "<span class='warn'>⚠ Course already exists: \"{$existingCourse['title']}\" (ID: {$cid})</span><br>";
    echo "<span class='ok'>Nothing to import. Use the admin panel to edit it.</span>";
    echo "<a class='btn' href='/admin/course-edit.php?id={$cid}'>→ Edit Course in Admin</a>";
    echo '</body></html>';
    exit;
}

echo "<span class='ok'>✓ No duplicate found. Proceeding to import...</span><br>";

// ═══════════════════════════════════════════════════════════
// STEP 3 — Find or Create "Java" category
// ═══════════════════════════════════════════════════════════
echo '<h2>Step 3: Setting Up Java Category</h2>';

$catStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = 'java' AND type = 'course' LIMIT 1");
$catStmt->execute();
$catRow = $catStmt->fetch();

if ($catRow) {
    $catId = (int) $catRow['id'];
    echo "<span class='warn'>⚠ 'Java' category already exists (ID: {$catId})</span><br>";
} else {
    $catIns = $pdo->prepare("INSERT INTO categories (name, slug, type, is_active) VALUES ('Java', 'java', 'course', 1)");
    $catIns->execute();
    $catId = (int) $pdo->lastInsertId();
    echo "<span class='ok'>✓ Created 'Java' category (ID: {$catId})</span><br>";
}

// ═══════════════════════════════════════════════════════════
// STEP 4 — Insert the course
// ═══════════════════════════════════════════════════════════
echo '<h2>Step 4: Creating Java Masterclass Course</h2>';

$curriculum = [
    ['title' => 'Introduction to Java', 'lessons' => [
        ['title' => 'What is Java? History and Features'],
        ['title' => 'Setting Up JDK and IDE (IntelliJ / VS Code)'],
        ['title' => 'Your First Java Program: Hello World'],
        ['title' => 'How Java Compilation Works (JVM, JDK, JRE)'],
    ]],
    ['title' => 'Java Fundamentals', 'lessons' => [
        ['title' => 'Variables, Data Types and Literals'],
        ['title' => 'Operators and Expressions'],
        ['title' => 'Scanner – Taking User Input'],
        ['title' => 'Type Casting and Conversion'],
        ['title' => 'String Basics and Methods'],
    ]],
    ['title' => 'Control Flow Statements', 'lessons' => [
        ['title' => 'if, if-else, else-if Ladder'],
        ['title' => 'Switch Statement (Classic and Enhanced)'],
        ['title' => 'for, while, do-while Loops'],
        ['title' => 'break, continue, and return Keywords'],
        ['title' => 'Nested Loops and Patterns'],
    ]],
    ['title' => 'Object-Oriented Programming (OOP)', 'lessons' => [
        ['title' => 'Classes and Objects – Real World Analogy'],
        ['title' => 'Constructors and the this Keyword'],
        ['title' => 'Encapsulation – Getters and Setters'],
        ['title' => 'Inheritance and the super Keyword'],
        ['title' => 'Method Overloading (Compile-time Polymorphism)'],
        ['title' => 'Method Overriding (Runtime Polymorphism)'],
        ['title' => 'Abstract Classes vs Interfaces'],
        ['title' => 'final, static, and instanceof Keywords'],
    ]],
    ['title' => 'Arrays and Strings', 'lessons' => [
        ['title' => '1D and 2D Arrays – Declaration and Iteration'],
        ['title' => 'Arrays Class – sort, binarySearch, copyOf'],
        ['title' => 'String Class – 20 Important Methods'],
        ['title' => 'StringBuilder vs StringBuffer'],
        ['title' => 'String Interview Problems'],
    ]],
    ['title' => 'Collections Framework', 'lessons' => [
        ['title' => 'Introduction to Collections – List, Set, Map'],
        ['title' => 'ArrayList and LinkedList'],
        ['title' => 'HashMap, LinkedHashMap, TreeMap'],
        ['title' => 'HashSet, LinkedHashSet, TreeSet'],
        ['title' => 'Stack and Queue (ArrayDeque)'],
        ['title' => 'Collections Utility Class'],
        ['title' => 'Iterator and ListIterator'],
    ]],
    ['title' => 'Exception Handling', 'lessons' => [
        ['title' => 'Types of Errors: Compile-time vs Runtime'],
        ['title' => 'Exception Hierarchy in Java'],
        ['title' => 'try-catch-finally Block'],
        ['title' => 'throw and throws Keywords'],
        ['title' => 'Creating Custom Exceptions'],
        ['title' => 'try-with-resources (Java 7+)'],
    ]],
    ['title' => 'File Handling & I/O', 'lessons' => [
        ['title' => 'File Class – Creating, Deleting, Checking'],
        ['title' => 'Reading Files with FileReader and BufferedReader'],
        ['title' => 'Writing Files with FileWriter and BufferedWriter'],
        ['title' => 'Serialization and Deserialization'],
    ]],
    ['title' => 'Java 8+ Modern Features', 'lessons' => [
        ['title' => 'Lambda Expressions and Functional Interfaces'],
        ['title' => 'Streams API – filter, map, reduce'],
        ['title' => 'Optional Class'],
        ['title' => 'Default and Static Methods in Interfaces'],
        ['title' => 'Method References'],
    ]],
    ['title' => 'Multithreading Basics', 'lessons' => [
        ['title' => 'Processes vs Threads'],
        ['title' => 'Creating Threads: Thread Class and Runnable'],
        ['title' => 'Thread Lifecycle and States'],
        ['title' => 'Synchronization and Deadlock Prevention'],
    ]],
    ['title' => 'Interview Preparation', 'lessons' => [
        ['title' => 'Top 50 Java Interview Questions with Answers'],
        ['title' => 'OOP Design Pattern Questions'],
        ['title' => 'Java Collections Interview Questions'],
        ['title' => 'Coding Problems: Arrays and Strings'],
        ['title' => 'Final Tips: How to Crack Java Interviews'],
    ]],
];

try {
    $pdo->beginTransaction();

    // Detect which SEO columns exist at runtime
    $hasSeoTitle = columnExists($pdo, 'courses', 'seo_title');
    $hasSeoDesc  = columnExists($pdo, 'courses', 'seo_description');
    $hasSeoKeys  = columnExists($pdo, 'courses', 'seo_keywords');
    $hasDlPath   = columnExists($pdo, 'courses', 'download_file_path');
    $hasContType = columnExists($pdo, 'courses', 'content_type');
    $hasPriceUsd = columnExists($pdo, 'courses', 'price_usd');
    $hasImportSrc= columnExists($pdo, 'courses', 'import_source');

    // Build dynamic INSERT based on available columns
    $cols   = ['category_id','title','slug','short_description','description',
               'price','currency','level','language',
               'requirements','what_you_learn','duration_hours',
               'is_free','is_featured','is_published','created_at','updated_at'];
    $vals   = [':cat',':title',':slug',':short',':desc',
               ':price',':curr',':level',':lang',
               ':req',':learn',':dur',
               ':is_free',':is_feat',':is_pub','NOW()','NOW()'];
    $params = [
        ':cat'    => $catId,
        ':title'  => 'Java Masterclass for Beginners',
        ':slug'   => 'java-masterclass-for-beginners',
        ':short'  => 'A complete beginner-to-intermediate Java course covering OOP, Collections, Exception Handling, File I/O, and interview preparation. Downloadable PDF guide.',
        ':desc'   => '<h2>About This Course</h2><p>Welcome to the <strong>Java Masterclass for Beginners</strong> — a comprehensive PDF course designed to take you from zero to job-ready in Java programming.</p><p>Covers everything you need to confidently write Java code, build projects, and crack technical interviews.</p><ul><li>Structured chapter-by-chapter learning path</li><li>Real code examples with explanations</li><li>Interview questions included in every chapter</li><li>Downloadable PDF — study offline, anytime</li><li>Covers Java 8+ features: Lambdas, Streams, Generics</li></ul>',
        ':price'  => 120.00,
        ':curr'   => 'INR',
        ':level'  => 'beginner',
        ':lang'   => 'English',
        ':req'    => "Basic computer literacy\nA computer with Windows, macOS, or Linux\nJDK 11+ (instructions included in PDF)",
        ':learn'  => "Understand Java syntax, data types, and operators\nMaster OOP in Java\nWork with Collections Framework\nHandle exceptions and write robust programs\nUse Java 8 features: Lambdas, Streams\nSolve coding problems for interviews",
        ':dur'    => 8.0,
        ':is_free'=> 0,
        ':is_feat'=> 0,
        ':is_pub' => 1,   // Published = 1 so it shows on frontend
    ];

    if ($hasDlPath)   { $cols[] = 'download_file_path'; $vals[] = ':dl_path';  $params[':dl_path']   = 'private/courses/Java Masterclass for Beginners.pdf'; }
    if ($hasContType) { $cols[] = 'content_type';       $vals[] = ':ctype';    $params[':ctype']     = 'pdf'; }
    if ($hasPriceUsd) { $cols[] = 'price_usd';          $vals[] = ':pusd';     $params[':pusd']      = 2.00; }
    if ($hasSeoTitle) { $cols[] = 'seo_title';          $vals[] = ':seo_t';    $params[':seo_t']     = 'Java Masterclass for Beginners PDF | CodeByTushu'; }
    if ($hasSeoDesc)  { $cols[] = 'seo_description';    $vals[] = ':seo_d';    $params[':seo_d']     = 'Learn Java from scratch with our comprehensive PDF course. Covers OOP, Collections, Streams & interview prep. Download now from CodeByTushu.'; }
    if ($hasSeoKeys)  { $cols[] = 'seo_keywords';       $vals[] = ':seo_k';    $params[':seo_k']     = 'java course pdf, java masterclass, java for beginners, learn java, CodeByTushu'; }
    if ($hasImportSrc){ $cols[] = 'import_source';       $vals[] = ':imp_src';  $params[':imp_src']   = 'Java Masterclass for Beginners.pdf'; }

    $sql = sprintf(
        'INSERT INTO courses (%s) VALUES (%s)',
        implode(', ', $cols),
        implode(', ', $vals)
    );

    $ins = $pdo->prepare($sql);
    $ins->execute($params);
    $courseId = (int) $pdo->lastInsertId();
    echo "<span class='ok'>✓ Course inserted (ID: {$courseId})</span><br>";

    // ── Insert chapters and lessons ───────────────────────
    $totalLessons = 0;
    foreach ($curriculum as $chIdx => $chapter) {
        $chIns = $pdo->prepare(
            "INSERT INTO course_chapters (course_id, title, sort_order, is_active) VALUES (?, ?, ?, 1)"
        );
        $chIns->execute([$courseId, $chapter['title'], $chIdx + 1]);
        $chapId = (int) $pdo->lastInsertId();
        echo "  <span class='ok'>✓ Chapter " . ($chIdx + 1) . ": {$chapter['title']}</span><br>";

        foreach ($chapter['lessons'] as $lIdx => $lesson) {
            $lIns = $pdo->prepare(
                "INSERT INTO course_lessons (chapter_id, course_id, title, content_type, sort_order, is_active)
                 VALUES (?, ?, ?, 'pdf', ?, 1)"
            );
            $lIns->execute([$chapId, $courseId, $lesson['title'], $lIdx + 1]);
            $totalLessons++;
        }
    }

    // Update total_lessons counter
    $pdo->prepare("UPDATE courses SET total_lessons = ? WHERE id = ?")->execute([$totalLessons, $courseId]);

    $pdo->commit();

    echo '<div class="box">';
    echo "<h2 class='ok'>🎉 Import Complete!</h2>";
    echo "<strong style='color:#fff;'>Java Masterclass for Beginners</strong><br>";
    echo "<span class='ok'>✓ {$totalLessons} lessons created across " . count($curriculum) . " chapters</span><br>";
    echo "<span class='ok'>✓ Status: Published (visible on /courses/)</span><br><br>";
    echo "<a class='btn' href='/admin/course-edit.php?id={$courseId}'>✎ Edit Course in Admin Panel</a>";
    echo "&nbsp;&nbsp;<a class='btn' style='background:#ffc400;' href='/courses/'>→ View Courses Page</a>";
    echo '</div>';

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<span class='err'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>";
    echo "<br><small style='color:#888;'>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</small>";
}

echo '</body></html>';
