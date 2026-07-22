<?php
/**
 * /admin/import-pdf-runner.php
 * ─────────────────────────────────────────────────────────────
 * One-time CLI / browser runner for the Java Masterclass PDF import.
 * Access only via Admin panel (admin auth required).
 *
 * Run: https://yourdomain.com/admin/import-pdf-runner.php
 * Or via CLI: php import-pdf-runner.php
 */
declare(strict_types=1);

// Only allow admin access
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/includes/auth_check.php';
    header('Content-Type: text/html; charset=UTF-8');
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/app.php';
require_once $rootDir . '/config/database.php';

$pdo = db();

// ─── STEP 1: Run DB Migration ──────────────────────────────
echo "<h2>Step 1: Running DB Migration (012_pdf_course_fields.sql)</h2>";

$migrationFile = $rootDir . '/database/migrations/012_pdf_course_fields.sql';
$sql           = file_get_contents($migrationFile);
$statements    = array_filter(array_map('trim', explode(';', $sql)));

$ok  = 0;
$err = 0;
foreach ($statements as $stmt) {
    if (empty($stmt) || str_starts_with(ltrim($stmt), '--')) continue;
    try {
        $pdo->exec($stmt);
        $ok++;
    } catch (PDOException $e) {
        // Ignore "Duplicate column name" — means already applied
        if (str_contains($e->getMessage(), 'Duplicate column name') ||
            str_contains($e->getMessage(), 'already exists')) {
            echo "<span style='color:orange'>⚠ Already exists (skipped)</span><br>";
        } else {
            echo "<span style='color:red'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
            $err++;
        }
    }
}
echo "<span style='color:green'>✓ Migration complete ($ok statements executed, $err errors)</span><br><br>";

// ─── STEP 2: Import the Java Masterclass Course ───────────
echo "<h2>Step 2: Importing Java Masterclass for Beginners</h2>";

$filename = 'Java Masterclass for Beginners.pdf';
$pdfPath  = $rootDir . '/private/courses/' . $filename;

if (!file_exists($pdfPath)) {
    die("<span style='color:red'>✗ PDF not found at: private/courses/{$filename}</span>");
}

// Check if already imported
$exists = $pdo->prepare("SELECT id FROM courses WHERE import_source = ? LIMIT 1");
$exists->execute([$filename]);
if ($row = $exists->fetch()) {
    echo "<span style='color:orange'>⚠ Course already exists (ID: {$row['id']}). Skipping import.</span><br>";
    echo "<a href='/admin/course-edit.php?id={$row['id']}'>→ Edit Course</a>";
    exit;
}

// Find or create Java category
$catSlug = 'java';
$catStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND type = 'course' LIMIT 1");
$catStmt->execute([$catSlug]);
$catRow = $catStmt->fetch();
if (!$catRow) {
    $catIns = $pdo->prepare("INSERT INTO categories (name, slug, type, is_active) VALUES ('Java', 'java', 'course', 1)");
    $catIns->execute();
    $catId = (int) $pdo->lastInsertId();
} else {
    $catId = (int) $catRow['id'];
}

// ─── Course Data ───────────────────────────────────────────
$courseData = [
    'title'             => 'Java Masterclass for Beginners',
    'slug'              => 'java-masterclass-for-beginners',
    'short_description' => 'A complete beginner-to-intermediate Java course covering OOP, Collections, Exception Handling, File I/O, and interview preparation. Downloadable PDF guide.',
    'description'       => '<h2>About This Course</h2>
<p>Welcome to the <strong>Java Masterclass for Beginners</strong> — a comprehensive PDF course designed to take you from zero to job-ready in Java programming.</p>
<p>This course covers everything you need to confidently write Java code, build projects, and crack technical interviews. Every concept is explained in plain English with real code examples.</p>
<h3>What Makes This Course Special?</h3>
<ul>
<li>Structured chapter-by-chapter learning path from basics to advanced</li>
<li>Real code examples with explanations for every concept</li>
<li>Interview questions and answers included in every chapter</li>
<li>Downloadable PDF — study offline, anytime, anywhere</li>
<li>Covers Java 8+ features: Lambdas, Streams, Generics</li>
</ul>
<h3>Who Should Take This Course?</h3>
<p>Perfect for students, freshers, and working professionals who want to learn Java from scratch or strengthen their foundation before interviews.</p>
<p><strong>Download once. Learn at your own pace. No internet required.</strong></p>',
    'level'             => 'beginner',
    'language'          => 'English',
    'price'             => 120.00,
    'price_usd'         => 2.00,
    'duration_hours'    => 8.0,
    'requirements'      => "Basic computer literacy (can install software)\nA computer with Windows, macOS, or Linux\nJDK 11+ (instructions included in PDF)\nWillingness to practice daily",
    'what_you_learn'    => "Understand Java syntax, data types, and operators\nMaster Object-Oriented Programming (OOP) in Java\nWork with Arrays, Strings, and Collections Framework\nHandle exceptions and write robust Java programs\nRead and write files using Java I/O\nUse Java 8 features: Lambdas, Streams, Optional\nSolve coding problems for technical interviews\nBuild small real-world Java applications",
    'seo_title'         => 'Java Masterclass for Beginners PDF | CodeByTushu',
    'seo_description'   => 'Learn Java from scratch with our comprehensive PDF course. Covers OOP, Collections, Streams, Exception Handling & interview prep. Download now from CodeByTushu.',
    'seo_keywords'      => 'java course pdf, java masterclass, java for beginners, learn java, java programming, OOP java, java collections, java interview, CodeByTushu',
    'download_file_path'=> 'private/courses/Java Masterclass for Beginners.pdf',
    'content_type'      => 'pdf',
    'import_source'     => 'Java Masterclass for Beginners.pdf',
];

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

// ─── Insert into DB ────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare("
        INSERT INTO courses (
            category_id, title, slug, short_description, description,
            price, price_usd, currency, level, language,
            requirements, what_you_learn, duration_hours,
            download_file_path, content_type,
            seo_title, seo_description, seo_keywords,
            is_free, is_featured, is_published, import_source,
            created_at, updated_at
        ) VALUES (
            :cat, :title, :slug, :short, :desc,
            :price, :pusd, 'INR', :level, :lang,
            :req, :learn, :dur,
            :dl_path, 'pdf',
            :seo_t, :seo_d, :seo_k,
            0, 0, 0, :import_src,
            NOW(), NOW()
        )
    ");
    $ins->execute([
        ':cat'        => $catId,
        ':title'      => $courseData['title'],
        ':slug'       => $courseData['slug'],
        ':short'      => $courseData['short_description'],
        ':desc'       => $courseData['description'],
        ':price'      => $courseData['price'],
        ':pusd'       => $courseData['price_usd'],
        ':level'      => $courseData['level'],
        ':lang'       => $courseData['language'],
        ':req'        => $courseData['requirements'],
        ':learn'      => $courseData['what_you_learn'],
        ':dur'        => $courseData['duration_hours'],
        ':dl_path'    => $courseData['download_file_path'],
        ':seo_t'      => $courseData['seo_title'],
        ':seo_d'      => $courseData['seo_description'],
        ':seo_k'      => $courseData['seo_keywords'],
        ':import_src' => $courseData['import_source'],
    ]);
    $courseId = (int) $pdo->lastInsertId();
    echo "<span style='color:green'>✓ Course created (ID: {$courseId})</span><br>";

    // Insert chapters and lessons
    $totalLessons = 0;
    foreach ($curriculum as $chIdx => $chapter) {
        $chIns = $pdo->prepare("INSERT INTO course_chapters (course_id, title, sort_order, is_active) VALUES (?, ?, ?, 1)");
        $chIns->execute([$courseId, $chapter['title'], $chIdx + 1]);
        $chapId = (int) $pdo->lastInsertId();
        echo "  ✓ Chapter " . ($chIdx+1) . ": {$chapter['title']}<br>";

        foreach ($chapter['lessons'] as $lIdx => $lesson) {
            $lIns = $pdo->prepare("INSERT INTO course_lessons (chapter_id, course_id, title, content_type, sort_order, is_active) VALUES (?, ?, ?, 'pdf', ?, 1)");
            $lIns->execute([$chapId, $courseId, $lesson['title'], $lIdx + 1]);
            $totalLessons++;
        }
    }

    // Update total_lessons
    $pdo->prepare("UPDATE courses SET total_lessons = ? WHERE id = ?")->execute([$totalLessons, $courseId]);

    $pdo->commit();

    echo "<br><strong style='color:green'>✅ All done! {$totalLessons} lessons created across " . count($curriculum) . " chapters.</strong><br><br>";
    echo "<a href='/admin/course-edit.php?id={$courseId}' style='background:#22c55e;color:#000;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;'>→ Edit Course in Admin Panel</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<span style='color:red'>✗ DB Error: " . htmlspecialchars($e->getMessage()) . "</span>";
}
