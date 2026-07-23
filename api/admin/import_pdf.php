<?php
/**
 * /api/admin/import_pdf.php
 * ─────────────────────────────────────────────────────────────
 * One-click PDF Course Auto-Import API
 *
 * HOW IT WORKS:
 *   POST  action=scan    → Returns list of unimported PDFs in private/courses/
 *   POST  action=import  → Imports a single PDF, creates course in DB
 *
 * PDF PARSING:
 *   Priority 1: pdftotext CLI (Linux/cPanel)
 *   Priority 2: smalot/pdfparser (Composer library)
 *   Priority 3: Filename-based generation (always available fallback)
 *
 * ADMIN ONLY — requires admin/super_admin role.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

Auth::boot();

// Admin only
if (!Auth::check() || !in_array(Auth::user()['role'] ?? '', ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required.']);
    exit;
}

$action  = $_POST['action'] ?? '';
$rootDir = rtrim(dirname(__DIR__, 2), '/\\');
$pdfDir  = $rootDir . '/private/courses';

/* ═══════════════════════════════════════════════════════════
   ACTION: scan — list unimported PDFs
   ══════════════════════════════════════════════════════════ */
if ($action === 'scan') {
    $pdo   = db();
    $files = glob($pdfDir . '/*.pdf') ?: [];

    $already = $pdo->query("SELECT import_source FROM courses WHERE import_source IS NOT NULL")
                   ->fetchAll(PDO::FETCH_COLUMN);
    $already = array_map('strtolower', $already);

    $available = [];
    foreach ($files as $f) {
        $basename = basename($f);
        if (!in_array(strtolower($basename), $already, true)) {
            $available[] = [
                'filename' => $basename,
                'size_kb'  => round(filesize($f) / 1024, 1),
            ];
        }
    }

    echo json_encode(['success' => true, 'pdfs' => $available, 'count' => count($available)]);
    exit;
}

/* ═══════════════════════════════════════════════════════════
   ACTION: import — create course from a PDF
   ══════════════════════════════════════════════════════════ */
if ($action === 'import') {
    $filename  = $_POST['filename'] ?? '';
    $priceInr  = (float)  ($_POST['price_inr']  ?? 120);
    $priceUsd  = (float)  ($_POST['price_usd']  ?? 2);
    $status    = $_POST['status'] ?? 'draft';   // 'draft' or 'published'

    if (!$filename || !preg_match('/\.pdf$/i', $filename)) {
        echo json_encode(['success' => false, 'error' => 'Invalid filename.']);
        exit;
    }

    $absPath = realpath($pdfDir . '/' . $filename);
    $allowed = realpath($pdfDir);

    if (!$absPath || !$allowed || strncmp($absPath, $allowed, strlen($allowed)) !== 0) {
        echo json_encode(['success' => false, 'error' => 'File not found or access denied.']);
        exit;
    }

    // ── Parse PDF text ─────────────────────────────────────
    $pdfText = extractPdfText($absPath);

    // ── Generate course data from PDF ──────────────────────
    $data = generateCourseData($filename, $pdfText, $absPath);

    // ── Save to database ────────────────────────────────────
    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Ensure slug is unique
        $slug     = $data['slug'];
        $existing = $pdo->prepare("SELECT id FROM courses WHERE slug = ?");
        $existing->execute([$slug]);
        if ($existing->fetch()) {
            $slug = $slug . '-' . time();
        }

        // Find or create category
        $catId = findOrCreateCategory($pdo, $data['category']);

        // Insert course
        $isPublished = ($status === 'published') ? 1 : 0;
        $isFree      = ($priceInr == 0) ? 1 : 0;

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
                :cat, :title, :slug, :short_desc, :desc,
                :price, :price_usd, 'INR', :level, :lang,
                :req, :learn, :dur,
                :pdf_path, 'pdf',
                :seo_title, :seo_desc, :seo_keys,
                :is_free, 0, :published, :import_src,
                NOW(), NOW()
            )
        ");
        $ins->execute([
            ':cat'        => $catId,
            ':title'      => $data['title'],
            ':slug'       => $slug,
            ':short_desc' => $data['short_description'],
            ':desc'       => $data['description'],
            ':price'      => $priceInr,
            ':price_usd'  => $priceUsd,
            ':level'      => $data['level'],
            ':lang'       => $data['language'],
            ':req'        => $data['requirements'],
            ':learn'      => $data['what_you_learn'],
            ':dur'        => $data['duration_hours'],
            ':pdf_path'   => 'private/courses/' . $filename,
            ':seo_title'  => $data['seo_title'],
            ':seo_desc'   => $data['seo_description'],
            ':seo_keys'   => $data['seo_keywords'],
            ':is_free'    => $isFree,
            ':published'  => $isPublished,
            ':import_src' => $filename,
        ]);
        $courseId = (int) $pdo->lastInsertId();

        // Insert chapters and lessons
        foreach ($data['curriculum'] as $chIdx => $chapter) {
            $chIns = $pdo->prepare("
                INSERT INTO course_chapters (course_id, title, description, sort_order, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $chIns->execute([$courseId, $chapter['title'], $chapter['description'] ?? '', $chIdx + 1]);
            $chapterId = (int) $pdo->lastInsertId();

            foreach ($chapter['lessons'] as $lIdx => $lesson) {
                $lIns = $pdo->prepare("
                    INSERT INTO course_lessons (chapter_id, course_id, title, content_type, sort_order, is_active)
                    VALUES (?, ?, ?, 'pdf', ?, 1)
                ");
                $lIns->execute([$chapterId, $courseId, $lesson['title'], $lIdx + 1]);
            }
        }

        // Update total_lessons count
        $cnt = $pdo->prepare("
            UPDATE courses SET total_lessons = (
                SELECT COUNT(*) FROM course_lessons WHERE course_id = ?
            ) WHERE id = ?
        ");
        $cnt->execute([$courseId, $courseId]);

        $pdo->commit();

        echo json_encode([
            'success'    => true,
            'course_id'  => $courseId,
            'title'      => $data['title'],
            'slug'       => $slug,
            'edit_url'   => '/admin/course-edit.php?id=' . $courseId,
            'message'    => "Course '{$data['title']}' imported successfully!",
        ]);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('PDF Import Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;


/* ═══════════════════════════════════════════════════════════
   HELPER: Extract text from PDF
   ══════════════════════════════════════════════════════════ */
function extractPdfText(string $path): string
{
    // Method 1: pdftotext (available on most Linux servers)
    if (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
        $escaped = escapeshellarg($path);
        $output  = [];
        exec("pdftotext {$escaped} - 2>/dev/null", $output, $code);
        if ($code === 0 && !empty($output)) {
            return implode("\n", $output);
        }
    }

    // Method 2: smalot/pdfparser (Composer)
    $parserClass = '\\Smalot\\PdfParser\\Parser';
    if (class_exists($parserClass)) {
        try {
            $parser = new $parserClass();
            $pdf    = $parser->parseFile($path);
            return $pdf->getText();
        } catch (Exception $e) {
            // fall through
        }
    }

    // Method 3: Raw binary scan for text strings (basic fallback)
    $raw  = file_get_contents($path);
    $text = '';
    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $raw, $m)) {
        foreach ($m[1] as $block) {
            if (preg_match_all('/\((.*?)\)/', $block, $tm)) {
                $text .= implode(' ', $tm[1]) . "\n";
            }
        }
    }
    return $text;
}


/* ═══════════════════════════════════════════════════════════
   HELPER: Generate course metadata from PDF filename + text
   ══════════════════════════════════════════════════════════ */
function generateCourseData(string $filename, string $pdfText, string $absPath = ''): array
{
    // Clean title from filename
    $title = preg_replace('/\.pdf$/i', '', $filename);
    $title = trim($title);

    // Slug
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $slug = trim($slug, '-');

    // Detect topic from title
    $titleLower = strtolower($title);
    $topic      = detectTopic($titleLower);
    $level      = detectLevel($titleLower);
    $language   = 'English'; // default

    // Duration estimate from PDF pages
    $pages       = estimatePages($pdfText, $absPath && file_exists($absPath) ? filesize($absPath) : 1024 * 1024);
    $durationHrs = max(2, min(40, round($pages / 15, 1)));

    // Short description
    $shortDesc = generateShortDesc($title, $topic, $level);

    // Full description
    $description = generateFullDesc($title, $topic, $level);

    // What you'll learn
    $whatYouLearn = generateWhatYouLearn($topic, $title);

    // Requirements
    $requirements = generateRequirements($topic, $level);

    // SEO
    $seoTitle = $title . ' | CodeByTushu';
    if (strlen($seoTitle) > 70) $seoTitle = substr($seoTitle, 0, 67) . '...';
    $seoDesc  = "Learn {$title} with hands-on examples and real-world projects. Download the complete PDF course from CodeByTushu.";
    $seoKeys  = implode(', ', [strtolower($title), $topic, 'course', 'pdf', 'learn ' . $topic, 'CodeByTushu', $level . ' ' . $topic]);

    // Curriculum from PDF text or generated
    $curriculum = extractCurriculum($pdfText, $topic, $level);

    return [
        'title'             => $title,
        'slug'              => $slug,
        'category'          => ucfirst($topic),
        'level'             => $level,
        'language'          => $language,
        'short_description' => $shortDesc,
        'description'       => $description,
        'what_you_learn'    => $whatYouLearn,
        'requirements'      => $requirements,
        'duration_hours'    => $durationHrs ?: 8,
        'seo_title'         => $seoTitle,
        'seo_description'   => $seoDesc,
        'seo_keywords'      => $seoKeys,
        'curriculum'        => $curriculum,
    ];
}

function detectTopic(string $lower): string
{
    $map = [
        'java'        => 'java',
        'react'       => 'react',
        'javascript'  => 'javascript',
        'python'      => 'python',
        'node'        => 'nodejs',
        'dsa'         => 'dsa',
        'data struct' => 'dsa',
        'algorithm'   => 'dsa',
        'sql'         => 'sql',
        'database'    => 'database',
        'html'        => 'web development',
        'css'         => 'web development',
        'web dev'     => 'web development',
        'full stack'  => 'full stack',
        'php'         => 'php',
        'devops'      => 'devops',
        'linux'       => 'linux',
        'git'         => 'git',
        'spring'      => 'java',
        'kotlin'      => 'kotlin',
        'flutter'     => 'flutter',
        'typescript'  => 'typescript',
    ];
    foreach ($map as $keyword => $topic) {
        if (str_contains($lower, $keyword)) return $topic;
    }
    return 'programming';
}

function detectLevel(string $lower): string
{
    if (str_contains($lower, 'beginner') || str_contains($lower, 'basic') || str_contains($lower, 'intro')) {
        return 'beginner';
    }
    if (str_contains($lower, 'advanced') || str_contains($lower, 'expert') || str_contains($lower, 'pro')) {
        return 'advanced';
    }
    if (str_contains($lower, 'intermediate') || str_contains($lower, 'mid')) {
        return 'intermediate';
    }
    return 'all';
}

function estimatePages(string $text, int $fileSize): int
{
    if (!empty($text)) {
        $words = str_word_count($text);
        return max(10, intval($words / 350)); // ~350 words per page
    }
    return max(10, intval($fileSize / 30000)); // ~30KB per page estimate
}

function generateShortDesc(string $title, string $topic, string $level): string
{
    $levelStr = $level === 'all' ? 'all levels' : "{$level}s";
    return "A comprehensive {$title} course designed for {$levelStr}. Learn {$topic} concepts with practical examples, exercises, and real-world projects through this complete PDF guide.";
}

function generateFullDesc(string $title, string $topic, string $level): string
{
    $levelStr = $level === 'all' ? 'all skill levels' : "the {$level} level";
    return "<h2>About This Course</h2>
<p>Welcome to <strong>{$title}</strong> — a complete, structured PDF course created by CodeByTushu for {$levelStr}.</p>

<p>This course covers everything you need to master <strong>{$topic}</strong> from the ground up, with a focus on practical implementation and real-world application. Every concept is explained clearly with examples, diagrams, and exercises.</p>

<h3>Why This Course?</h3>
<ul>
<li>Structured, chapter-by-chapter learning path</li>
<li>Practical code examples throughout</li>
<li>Designed by an experienced developer and educator</li>
<li>Downloadable PDF — learn at your own pace, offline anytime</li>
<li>Covers both theory and hands-on implementation</li>
</ul>

<h3>Who Is This For?</h3>
<p>This course is perfect for students, job seekers, and working professionals who want to learn or strengthen their {$topic} skills. Whether you're preparing for interviews or building real projects, this guide has you covered.</p>

<p><strong>Download once. Learn forever. No internet required.</strong></p>";
}

function generateWhatYouLearn(string $topic, string $title): string
{
    $items = [
        "Understand core {$topic} concepts from scratch",
        "Write clean, professional {$topic} code",
        "Build real-world projects using {$topic}",
        "Understand best practices and design patterns",
        "Prepare for technical interviews with confidence",
        "Debug and solve problems like a professional developer",
        "Apply learned concepts to practical scenarios",
        "Master the fundamentals covered in {$title}",
    ];
    return implode("\n", $items);
}

function generateRequirements(string $topic, string $level): string
{
    $base = [
        "Basic computer literacy (can install software, browse web)",
        "A computer with Windows, macOS, or Linux",
        "Willingness to practice and experiment",
    ];

    if ($level !== 'beginner') {
        $base[] = "Basic programming knowledge is helpful";
    }

    if (in_array($topic, ['react', 'nodejs', 'typescript', 'full stack'])) {
        $base[] = "Basic HTML, CSS and JavaScript knowledge recommended";
    }
    if ($topic === 'java' && $level !== 'beginner') {
        $base[] = "Basic understanding of Object-Oriented Programming";
    }

    return implode("\n", $base);
}

function extractCurriculum(string $pdfText, string $topic, string $level): array
{
    // Try to detect chapter headings from PDF text
    $chapters = [];
    if (!empty($pdfText)) {
        $lines   = explode("\n", $pdfText);
        $current = null;
        $lessons = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Detect chapter headings (Chapter X, Unit X, Module X, or ALL CAPS short lines)
            if (preg_match('/^(chapter|unit|module|section|part)\s*\d+[:\.\-]?\s*/i', $line) ||
                (strlen($line) < 60 && strtoupper($line) === $line && strlen($line) > 5)) {

                if ($current && !empty($lessons)) {
                    $chapters[] = ['title' => $current, 'lessons' => $lessons];
                }
                $current = ucwords(strtolower(preg_replace('/^(chapter|unit|module|section|part)\s*\d+[:\.\-]?\s*/i', '', $line)));
                $lessons = [];

            } elseif ($current && strlen($line) > 5 && strlen($line) < 100 &&
                      preg_match('/^[\d\.]+\s+\w/i', $line)) {
                // Numbered items as lessons
                $lessons[] = ['title' => preg_replace('/^[\d\.\s]+/', '', $line)];
            }
        }
        if ($current && !empty($lessons)) {
            $chapters[] = ['title' => $current, 'lessons' => $lessons];
        }
    }

    // If PDF parsing yielded good results, use them (need at least 2 chapters with lessons)
    if (count($chapters) >= 2) {
        return $chapters;
    }

    // Fallback: Generate topic-specific structured curriculum
    return generateTopicCurriculum($topic, $level);
}

function generateTopicCurriculum(string $topic, string $level): array
{
    $curricula = [
        'java' => [
            ['title' => 'Introduction to Java', 'lessons' => [
                ['title' => 'What is Java? History and Features'],
                ['title' => 'Setting Up JDK and IDE'],
                ['title' => 'Your First Java Program: Hello World'],
                ['title' => 'Java Compilation and Execution Process'],
            ]],
            ['title' => 'Java Fundamentals', 'lessons' => [
                ['title' => 'Variables, Data Types and Literals'],
                ['title' => 'Operators and Expressions'],
                ['title' => 'Input and Output in Java'],
                ['title' => 'Type Casting and Conversion'],
            ]],
            ['title' => 'Control Flow Statements', 'lessons' => [
                ['title' => 'if, if-else, else-if Statements'],
                ['title' => 'Switch Statement'],
                ['title' => 'for, while, do-while Loops'],
                ['title' => 'break, continue, and return'],
            ]],
            ['title' => 'Object-Oriented Programming (OOP)', 'lessons' => [
                ['title' => 'Classes and Objects'],
                ['title' => 'Constructors and this Keyword'],
                ['title' => 'Encapsulation and Access Modifiers'],
                ['title' => 'Inheritance and super Keyword'],
                ['title' => 'Polymorphism: Method Overloading and Overriding'],
                ['title' => 'Abstraction: Abstract Classes and Interfaces'],
            ]],
            ['title' => 'Arrays and Strings', 'lessons' => [
                ['title' => 'Arrays: 1D and 2D'],
                ['title' => 'Array Methods and Iteration'],
                ['title' => 'String Class and Methods'],
                ['title' => 'StringBuilder and StringBuffer'],
            ]],
            ['title' => 'Collections Framework', 'lessons' => [
                ['title' => 'ArrayList and LinkedList'],
                ['title' => 'HashMap and HashSet'],
                ['title' => 'Stack, Queue, and Deque'],
                ['title' => 'Iterator and for-each'],
            ]],
            ['title' => 'Exception Handling', 'lessons' => [
                ['title' => 'Types of Exceptions'],
                ['title' => 'try-catch-finally Block'],
                ['title' => 'throw and throws Keywords'],
                ['title' => 'Custom Exceptions'],
            ]],
            ['title' => 'File Handling and I/O', 'lessons' => [
                ['title' => 'Reading and Writing Files'],
                ['title' => 'BufferedReader and BufferedWriter'],
                ['title' => 'Serialization and Deserialization'],
            ]],
            ['title' => 'Advanced Java Concepts', 'lessons' => [
                ['title' => 'Generics'],
                ['title' => 'Lambda Expressions (Java 8+)'],
                ['title' => 'Streams API'],
                ['title' => 'Multithreading Basics'],
            ]],
            ['title' => 'Interview Preparation', 'lessons' => [
                ['title' => 'Top 50 Java Interview Questions'],
                ['title' => 'Java Coding Problems and Solutions'],
                ['title' => 'OOP Design Questions'],
                ['title' => 'Final Tips and Resources'],
            ]],
        ],
        'react' => [
            ['title' => 'React Fundamentals', 'lessons' => [
                ['title' => 'What is React and Virtual DOM'],
                ['title' => 'Create React App Setup'],
                ['title' => 'JSX Syntax and Expressions'],
                ['title' => 'Components: Functional vs Class'],
            ]],
            ['title' => 'Props and State', 'lessons' => [
                ['title' => 'Understanding Props'],
                ['title' => 'useState Hook'],
                ['title' => 'Lifting State Up'],
                ['title' => 'Conditional Rendering'],
            ]],
            ['title' => 'React Hooks', 'lessons' => [
                ['title' => 'useEffect Hook'],
                ['title' => 'useRef and useCallback'],
                ['title' => 'Custom Hooks'],
                ['title' => 'Context API with useContext'],
            ]],
        ],
        'dsa' => [
            ['title' => 'Arrays and Strings', 'lessons' => [
                ['title' => 'Array Basics and Operations'],
                ['title' => 'Two Pointer Technique'],
                ['title' => 'Sliding Window'],
                ['title' => 'String Manipulation Problems'],
            ]],
            ['title' => 'Linked Lists', 'lessons' => [
                ['title' => 'Singly Linked List'],
                ['title' => 'Doubly Linked List'],
                ['title' => 'Floyd\'s Cycle Detection'],
            ]],
            ['title' => 'Trees and Graphs', 'lessons' => [
                ['title' => 'Binary Trees'],
                ['title' => 'Binary Search Trees'],
                ['title' => 'BFS and DFS'],
                ['title' => 'Graph Representations'],
            ]],
            ['title' => 'Sorting and Searching', 'lessons' => [
                ['title' => 'Bubble, Selection, Insertion Sort'],
                ['title' => 'Merge Sort and Quick Sort'],
                ['title' => 'Binary Search'],
            ]],
            ['title' => 'Dynamic Programming', 'lessons' => [
                ['title' => 'Memoization vs Tabulation'],
                ['title' => 'Classic DP Problems'],
                ['title' => 'String DP Problems'],
            ]],
        ],
        'default' => [
            ['title' => 'Getting Started', 'lessons' => [
                ['title' => 'Introduction and Overview'],
                ['title' => 'Environment Setup'],
                ['title' => 'Core Concepts'],
            ]],
            ['title' => 'Fundamentals', 'lessons' => [
                ['title' => 'Basic Syntax and Structure'],
                ['title' => 'Core Features'],
                ['title' => 'Practical Examples'],
            ]],
            ['title' => 'Intermediate Concepts', 'lessons' => [
                ['title' => 'Advanced Features'],
                ['title' => 'Best Practices'],
                ['title' => 'Real-World Usage'],
            ]],
            ['title' => 'Projects and Practice', 'lessons' => [
                ['title' => 'Mini Project Walkthrough'],
                ['title' => 'Common Problems and Solutions'],
                ['title' => 'Interview Preparation Tips'],
            ]],
        ],
    ];

    return $curricula[$topic] ?? $curricula['default'];
}

function findOrCreateCategory(PDO $pdo, string $name): ?int
{
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $slug = trim($slug, '-');

    $s = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND type = 'course' LIMIT 1");
    $s->execute([$slug]);
    $row = $s->fetch();
    if ($row) return (int) $row['id'];

    $ins = $pdo->prepare("INSERT INTO categories (name, slug, type, is_active) VALUES (?, ?, 'course', 1)");
    $ins->execute([$name, $slug]);
    return (int) $pdo->lastInsertId();
}
