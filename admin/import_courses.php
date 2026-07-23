<?php
/**
 * One-time importer: Insert 3 new PDF courses into the database.
 * Follows exactly the same structure as the Java Masterclass for Beginners course.
 * Safe to re-run: uses INSERT IGNORE on slug to prevent duplicates.
 */
declare(strict_types=1);

require_once __DIR__ . "/includes/auth_check.php";
$rootDir = dirname(__DIR__);
require_once $rootDir . "/config/app.php";
require_once $rootDir . "/config/database.php";

$pdo = db();

// ── Fetch the existing Java Masterclass to copy its category_id
$javaStmt = $pdo->query("SELECT id, category_id FROM courses WHERE slug = \"java-masterclass-for-beginners\" LIMIT 1");
$javaCourse = $javaStmt ? $javaStmt->fetch() : null;

// Use same category_id as Java Masterclass, or NULL if not found
$categoryId = $javaCourse ? $javaCourse["category_id"] : null;

// ── Define the 3 new courses
$newCourses = [
    [
        "title"              => "Data Structures & Algorithms + Coding Interview Preparation",
        "slug"               => "dsa-coding-interview-preparation",
        "short_description"  => "Master Data Structures & Algorithms from scratch and crack coding interviews at top tech companies. Covers arrays, linked lists, trees, graphs, dynamic programming, sorting, and 200+ interview questions with solutions.",
        "description"        => "<h2>Course Overview</h2><p>This comprehensive course covers everything you need to ace coding interviews at top tech companies like Google, Amazon, Facebook, and Microsoft. From fundamental data structures to advanced algorithms, you will learn with practical examples and real interview questions.</p><h3>What You Will Learn</h3><ul><li>Arrays, Strings, and Hashing</li><li>Linked Lists (Singly, Doubly, Circular)</li><li>Stacks, Queues, and Deques</li><li>Binary Trees and Binary Search Trees</li><li>Heaps and Priority Queues</li><li>Graphs (BFS, DFS, Dijkstra, Bellman-Ford)</li><li>Dynamic Programming (Top-Down, Bottom-Up)</li><li>Sorting and Searching Algorithms</li><li>Recursion and Backtracking</li><li>200+ Curated Interview Questions with Solutions</li><li>Time and Space Complexity Analysis</li><li>System Design Basics for Interviews</li></ul><h3>Who Is This Course For?</h3><ul><li>Students preparing for placement drives</li><li>Software engineers targeting product companies</li><li>Anyone who wants to master DSA from scratch</li></ul>",
        "thumbnail_path"     => "/assets/images/courses/dsa-interview-prep.jpg",
        "download_file_path" => "private/courses/Data Structures & Algorithms + Coding Interview Preparation.pdf",
        "price"              => 120.00,
        "price_usd"          => 2.00,
        "level"              => "all",
        "duration_hours"     => 40.0,
        "total_lessons"      => 120,
        "language"           => "Hindi",
        "is_free"            => 0,
        "is_featured"        => 1,
        "content_type"       => "pdf",
        "what_you_learn"     => "Arrays & Strings\nLinked Lists\nTrees & Graphs\nDynamic Programming\nSorting & Searching\n200+ Interview Questions\nTime & Space Complexity\nRecursion & Backtracking",
        "requirements"       => "Basic programming knowledge in any language\nWillingness to practice consistently",
        "seo_title"          => "DSA & Coding Interview Prep Course | CodeByTushu",
        "seo_description"    => "Master Data Structures, Algorithms & crack coding interviews at Google, Amazon, Facebook. 200+ interview questions included. Hindi course.",
        "seo_keywords"       => "data structures algorithms, coding interview preparation, DSA course Hindi, placement preparation, LeetCode practice",
        "import_source"      => "Data Structures & Algorithms + Coding Interview Preparation.pdf",
    ],
    [
        "title"              => "Full Stack Web Development Masterclass",
        "slug"               => "full-stack-web-development-masterclass",
        "short_description"  => "Complete Full Stack Web Development course covering HTML, CSS, JavaScript, Node.js, Express, MongoDB, React, and deployment. Build real-world projects and become a job-ready full stack developer.",
        "description"        => "<h2>Course Overview</h2><p>Become a complete Full Stack Web Developer from scratch. This masterclass takes you from zero to hero, covering frontend and backend development with modern technologies used by top companies worldwide.</p><h3>What You Will Learn</h3><ul><li>HTML5, CSS3, and Responsive Design</li><li>JavaScript (ES6+) — DOM, Events, Async/Await</li><li>React.js — Components, Hooks, Context API, Router</li><li>Node.js and Express.js for Backend APIs</li><li>MongoDB and Mongoose for Databases</li><li>REST API Design and Authentication (JWT)</li><li>Git, GitHub, and Version Control</li><li>Deployment on Vercel, Netlify, and Railway</li><li>5+ Real-World Projects</li><li>Full Stack Social Media App (Final Project)</li></ul><h3>Who Is This Course For?</h3><ul><li>Absolute beginners wanting to become web developers</li><li>Frontend developers who want to learn backend</li><li>Anyone wanting to build real-world projects</li></ul>",
        "thumbnail_path"     => "/assets/images/courses/fullstack-web-dev.jpg",
        "download_file_path" => "private/courses/Full Stack Web Development Masterclass.pdf",
        "price"              => 120.00,
        "price_usd"          => 2.00,
        "level"              => "beginner",
        "duration_hours"     => 60.0,
        "total_lessons"      => 180,
        "language"           => "Hindi",
        "is_free"            => 0,
        "is_featured"        => 1,
        "content_type"       => "pdf",
        "what_you_learn"     => "HTML5 & CSS3\nJavaScript ES6+\nReact.js\nNode.js & Express\nMongoDB\nREST APIs & JWT Auth\nGit & GitHub\nDeployment & DevOps",
        "requirements"       => "No prior web development experience needed\nA computer with internet access",
        "seo_title"          => "Full Stack Web Development Masterclass | CodeByTushu",
        "seo_description"    => "Learn Full Stack Web Development in Hindi. Master HTML, CSS, JS, React, Node.js, MongoDB, REST APIs & deploy real-world projects.",
        "seo_keywords"       => "full stack web development course, HTML CSS JavaScript, React Node.js MongoDB, web development Hindi, MERN stack",
        "import_source"      => "Full Stack Web Development Masterclass.pdf",
    ],
    [
        "title"              => "React Frontend to Backend Masterclass",
        "slug"               => "react-frontend-to-backend-masterclass",
        "short_description"  => "Master React.js from beginner to advanced — from basic components to full-stack apps with Node.js backend, REST APIs, authentication, state management, and real-world deployment.",
        "description"        => "<h2>Course Overview</h2><p>This masterclass covers React.js comprehensively — from the very basics to advanced patterns and full-stack integration with a Node.js backend. You will build production-ready applications that demonstrate real-world skills.</p><h3>What You Will Learn</h3><ul><li>React Fundamentals — JSX, Props, State, Lifecycle</li><li>React Hooks (useState, useEffect, useCallback, useMemo)</li><li>Context API and Redux Toolkit for State Management</li><li>React Router v6 for SPA Navigation</li><li>Fetching Data with Axios and React Query</li><li>Building a Node.js + Express REST API Backend</li><li>MongoDB Integration with Mongoose</li><li>JWT Authentication and Protected Routes</li><li>React + TypeScript Basics</li><li>Testing with Jest and React Testing Library</li><li>Performance Optimization and Code Splitting</li><li>Deployment on Vercel with CI/CD</li></ul><h3>Who Is This Course For?</h3><ul><li>JavaScript developers ready to learn React</li><li>React developers wanting to learn backend integration</li><li>Anyone building modern web applications</li></ul>",
        "thumbnail_path"     => "/assets/images/courses/react-masterclass.jpg",
        "download_file_path" => "private/courses/React Frontend to Backend Masterclass.pdf",
        "price"              => 120.00,
        "price_usd"          => 2.00,
        "level"              => "intermediate",
        "duration_hours"     => 35.0,
        "total_lessons"      => 100,
        "language"           => "Hindi",
        "is_free"            => 0,
        "is_featured"        => 1,
        "content_type"       => "pdf",
        "what_you_learn"     => "React Fundamentals\nReact Hooks\nContext API & Redux\nReact Router v6\nNode.js & Express Backend\nMongoDB & Mongoose\nJWT Authentication\nDeployment & CI/CD",
        "requirements"       => "Basic JavaScript knowledge (ES6+)\nHTML and CSS fundamentals",
        "seo_title"          => "React Frontend to Backend Masterclass | CodeByTushu",
        "seo_description"    => "Master React.js in Hindi — from JSX basics to full-stack apps with Node.js, MongoDB, JWT auth and Vercel deployment.",
        "seo_keywords"       => "React.js course Hindi, React frontend backend, React full stack, Node.js React course, MERN stack React",
        "import_source"      => "React Frontend to Backend Masterclass.pdf",
    ],
];

// ── Run insert
$inserted = []; $skipped = []; $errors = [];

foreach ($newCourses as $c) {
    // Check if already exists
    $chk = $pdo->prepare("SELECT id FROM courses WHERE slug = ? LIMIT 1");
    $chk->execute([$c["slug"]]);
    if ($chk->fetch()) {
        $skipped[] = $c["title"] . " (slug already exists — skipped)";
        continue;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO courses (
                category_id, title, slug, short_description, description,
                thumbnail_path, download_file_path, content_type,
                price, price_usd, level, language,
                duration_hours, total_lessons,
                requirements, what_you_learn,
                is_free, is_featured, is_published,
                seo_title, seo_description, seo_keywords,
                import_source, published_at, created_at, updated_at
            ) VALUES (
                :category_id, :title, :slug, :short_description, :description,
                :thumbnail_path, :download_file_path, :content_type,
                :price, :price_usd, :level, :language,
                :duration_hours, :total_lessons,
                :requirements, :what_you_learn,
                :is_free, :is_featured, 1,
                :seo_title, :seo_description, :seo_keywords,
                :import_source, NOW(), NOW(), NOW()
            )
        ");
        $stmt->execute([
            ":category_id"        => $categoryId,
            ":title"              => $c["title"],
            ":slug"               => $c["slug"],
            ":short_description"  => $c["short_description"],
            ":description"        => $c["description"],
            ":thumbnail_path"     => $c["thumbnail_path"],
            ":download_file_path" => $c["download_file_path"],
            ":content_type"       => $c["content_type"],
            ":price"              => $c["price"],
            ":price_usd"          => $c["price_usd"],
            ":level"              => $c["level"],
            ":language"           => $c["language"],
            ":duration_hours"     => $c["duration_hours"],
            ":total_lessons"      => $c["total_lessons"],
            ":requirements"       => $c["requirements"],
            ":what_you_learn"     => $c["what_you_learn"],
            ":is_free"            => $c["is_free"],
            ":is_featured"        => $c["is_featured"],
            ":seo_title"          => $c["seo_title"],
            ":seo_description"    => $c["seo_description"],
            ":seo_keywords"       => $c["seo_keywords"],
            ":import_source"      => $c["import_source"],
        ]);
        $newId = $pdo->lastInsertId();
        $inserted[] = ["id" => $newId, "title" => $c["title"], "slug" => $c["slug"], "thumbnail" => $c["thumbnail_path"]];
    } catch (Exception $e) {
        $errors[] = $c["title"] . ": " . $e->getMessage();
    }
}

// ── Output result
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Course Import</title>";
echo "<style>body{font-family:sans-serif;background:#111;color:#fff;padding:40px}h1{color:#ffc400}h2{color:#ddd;font-size:1.1rem;margin-top:25px}.ok{color:#22c55e}.warn{color:#fbbf24}.err{color:#ef4444}.card{background:#1a1a1a;padding:16px;border-radius:8px;margin-bottom:10px;border-left:4px solid #ffc400}img{max-width:320px;border:1px solid #333;margin-top:8px;display:block}a.btn{display:inline-block;padding:10px 20px;background:#ffc400;color:#000;text-decoration:none;border-radius:8px;font-weight:bold;margin:5px 5px 0 0}</style></head><body>";
echo "<h1>✅ Course Import Complete</h1>";

if (!empty($inserted)) {
    echo "<h2 class=\"ok\">Inserted (" . count($inserted) . " courses)</h2>";
    foreach ($inserted as $ins) {
        echo "<div class=\"card\"><strong class=\"ok\">#{$ins[\"id\"]} — {$ins[\"title\"]}</strong><br>";
        echo "Slug: <code>/courses/{$ins[\"slug\"]}</code><br>";
        echo "<img src=\"{$ins[\"thumbnail\"]}?t=".time()."\" alt=\"thumbnail\"></div>";
    }
}
if (!empty($skipped)) {
    echo "<h2 class=\"warn\">Skipped</h2>";
    foreach ($skipped as $s) echo "<div class=\"card warn\">$s</div>";
}
if (!empty($errors)) {
    echo "<h2 class=\"err\">Errors</h2>";
    foreach ($errors as $e) echo "<div class=\"card err\">$e</div>";
}

echo "<br><a class=\"btn\" href=\"/courses/\">🎓 View Courses Page</a>";
echo "<a class=\"btn\" href=\"/admin/courses.php\">⚙ Admin Panel</a>";
echo "</body></html>";

