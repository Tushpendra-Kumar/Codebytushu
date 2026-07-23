<?php
/**
 * run_course_import.php
 * ONE-TIME RUNNER — inserts 3 new courses into the database.
 * No login required. Secured by secret token in URL.
 *
 * Usage: https://codebytushu.com/admin/run_course_import.php?token=CBT_IMPORT_2024
 */
declare(strict_types=1);

// Simple security token to prevent accidental or unauthorized execution
$SECRET_TOKEN = "CBT_IMPORT_2024";
if (($_GET["token"] ?? "") !== $SECRET_TOKEN) {
    http_response_code(403);
    die("403 Forbidden — provide ?token=CBT_IMPORT_2024 in the URL.");
}

$rootDir = dirname(__DIR__);
require_once $rootDir . "/config/app.php";
require_once $rootDir . "/config/database.php";

$pdo = db();

// Get Java Masterclass category to reuse
$javaRow = $pdo->query("SELECT category_id FROM courses WHERE slug = \"java-masterclass-for-beginners\" LIMIT 1")->fetch();
$catId = $javaRow ? $javaRow["category_id"] : null;

$newCourses = [
    [
        "title"              => "Data Structures & Algorithms + Coding Interview Preparation",
        "slug"               => "dsa-coding-interview-preparation",
        "short_description"  => "Master Data Structures & Algorithms from scratch and crack coding interviews at top tech companies. Covers arrays, linked lists, trees, graphs, dynamic programming, sorting, and 200+ interview questions with solutions.",
        "description"        => "<h2>Course Overview</h2><p>This comprehensive course covers everything you need to ace coding interviews at top tech companies like Google, Amazon, Facebook, and Microsoft. From fundamental data structures to advanced algorithms, you will learn with practical examples and real interview questions.</p><h3>What You Will Learn</h3><ul><li>Arrays, Strings, and Hashing</li><li>Linked Lists — Singly, Doubly, Circular</li><li>Stacks, Queues, and Deques</li><li>Binary Trees and Binary Search Trees</li><li>Heaps and Priority Queues</li><li>Graphs — BFS, DFS, Dijkstra, Bellman-Ford</li><li>Dynamic Programming — Top-Down and Bottom-Up</li><li>Sorting and Searching Algorithms</li><li>Recursion and Backtracking</li><li>200+ Curated Interview Questions with Solutions</li><li>Time and Space Complexity Analysis</li></ul>",
        "thumbnail_path"     => "/assets/images/courses/dsa-interview-prep.jpg",
        "download_file_path" => "private/courses/Data Structures & Algorithms + Coding Interview Preparation.pdf",
        "price"              => 600.00,
        "level"              => "all",
        "duration_hours"     => 40.0,
        "total_lessons"      => 120,
    ],
    [
        "title"              => "Full Stack Web Development Masterclass",
        "slug"               => "full-stack-web-development-masterclass",
        "short_description"  => "Complete Full Stack Web Development course covering HTML, CSS, JavaScript, Node.js, Express, MongoDB, React, and deployment. Build real-world projects and become a job-ready full stack developer.",
        "description"        => "<h2>Course Overview</h2><p>Become a complete Full Stack Web Developer from scratch. This masterclass takes you from zero to hero, covering frontend and backend development with modern technologies used by top companies worldwide.</p><h3>What You Will Learn</h3><ul><li>HTML5, CSS3, and Responsive Design</li><li>JavaScript ES6+ — DOM, Events, Async/Await</li><li>React.js — Components, Hooks, Context API, Router</li><li>Node.js and Express.js for Backend APIs</li><li>MongoDB and Mongoose for Databases</li><li>REST API Design and Authentication with JWT</li><li>Git, GitHub, and Version Control</li><li>Deployment on Vercel, Netlify, and Railway</li><li>5+ Real-World Projects</li></ul>",
        "thumbnail_path"     => "/assets/images/courses/fullstack-web-dev.jpg",
        "download_file_path" => "private/courses/Full Stack Web Development Masterclass.pdf",
        "price"              => 700.00,
        "level"              => "beginner",
        "duration_hours"     => 60.0,
        "total_lessons"      => 180,
    ],
    [
        "title"              => "React Frontend to Backend Masterclass",
        "slug"               => "react-frontend-to-backend-masterclass",
        "short_description"  => "Master React.js from beginner to advanced — from basic components to full-stack apps with Node.js backend, REST APIs, authentication, state management, and real-world deployment.",
        "description"        => "<h2>Course Overview</h2><p>This masterclass covers React.js comprehensively — from the very basics to advanced patterns and full-stack integration with a Node.js backend. You will build production-ready applications that demonstrate real-world skills.</p><h3>What You Will Learn</h3><ul><li>React Fundamentals — JSX, Props, State, Lifecycle</li><li>React Hooks — useState, useEffect, useCallback, useMemo</li><li>Context API and Redux Toolkit for State Management</li><li>React Router v6 for SPA Navigation</li><li>Fetching Data with Axios and React Query</li><li>Building a Node.js + Express REST API Backend</li><li>MongoDB Integration with Mongoose</li><li>JWT Authentication and Protected Routes</li><li>Performance Optimization and Code Splitting</li><li>Deployment on Vercel with CI/CD</li></ul>",
        "thumbnail_path"     => "/assets/images/courses/react-masterclass.jpg",
        "download_file_path" => "private/courses/React Frontend to Backend Masterclass.pdf",
        "price"              => 800.00,
        "level"              => "intermediate",
        "duration_hours"     => 35.0,
        "total_lessons"      => 100,
    ],
];

$inserted = []; $skipped = []; $errors = [];

foreach ($newCourses as $c) {
    $chk = $pdo->prepare("SELECT id FROM courses WHERE slug = ? LIMIT 1");
    $chk->execute([$c["slug"]]);
    if ($chk->fetch()) {
        $skipped[] = $c["title"];
        continue;
    }
    try {
        $stmt = $pdo->prepare("
            INSERT INTO courses (
                category_id, title, slug, short_description, description,
                thumbnail_path, download_file_path, content_type,
                price, level, language,
                duration_hours, total_lessons,
                is_free, is_featured, is_published,
                published_at, created_at, updated_at
            ) VALUES (
                :category_id, :title, :slug, :short_description, :description,
                :thumbnail_path, :download_file_path, \"pdf\",
                :price, :level, \"Hindi\",
                :duration_hours, :total_lessons,
                0, 1, 1,
                NOW(), NOW(), NOW()
            )
        ");
        $stmt->execute([
            ":category_id"        => $catId,
            ":title"              => $c["title"],
            ":slug"               => $c["slug"],
            ":short_description"  => $c["short_description"],
            ":description"        => $c["description"],
            ":thumbnail_path"     => $c["thumbnail_path"],
            ":download_file_path" => $c["download_file_path"],
            ":price"              => $c["price"],
            ":level"              => $c["level"],
            ":duration_hours"     => $c["duration_hours"],
            ":total_lessons"      => $c["total_lessons"],
        ]);
        $newId = $pdo->lastInsertId();
        $inserted[] = ["id" => $newId, "title" => $c["title"], "slug" => $c["slug"]];
    } catch (Exception $e) {
        $errors[] = $c["title"] . ": " . $e->getMessage();
    }
}

// ── HTML Output ────────────────────────────────────────────────────
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Course Import</title>";
echo "<style>body{font-family:sans-serif;background:#0a0a0a;color:#fff;padding:40px;max-width:800px;margin:0 auto}";
echo "h1{color:#ffc400;font-size:2rem;margin-bottom:5px}";
echo "h2{color:#ddd;font-size:1.1rem;margin-top:25px;border-bottom:1px solid #333;padding-bottom:8px}";
echo ".ok{color:#22c55e}.warn{color:#fbbf24}.err{color:#ef4444}";
echo ".card{background:#1a1a1a;padding:16px 20px;border-radius:10px;margin-bottom:10px;border-left:4px solid #ffc400}";
echo ".skip-card{border-left-color:#fbbf24}";
echo ".err-card{border-left-color:#ef4444}";
echo "code{background:#111;padding:3px 8px;border-radius:4px;color:#ffc400;font-size:0.9em}";
echo "a.btn{display:inline-block;padding:12px 24px;background:#ffc400;color:#000;text-decoration:none;border-radius:8px;font-weight:bold;margin:6px 6px 0 0;font-size:1rem}";
echo "</style></head><body>";

echo "<h1>🚀 Course Import Results</h1>";
echo "<p style=\"color:#888;\">Import completed at: " . date("d M Y, H:i:s") . "</p>";

if (!empty($inserted)) {
    echo "<h2 class=\"ok\">✅ Inserted " . count($inserted) . " Course(s)</h2>";
    foreach ($inserted as $ins) {
        echo "<div class=\"card\">";
        echo "<strong class=\"ok\">#{$ins["id"]} — {$ins["title"]}</strong><br>";
        echo "<span style=\"color:#aaa;font-size:0.9em;\">URL: </span><code>/courses/{$ins["slug"]}</code>";
        echo "</div>";
    }
}

if (!empty($skipped)) {
    echo "<h2 class=\"warn\">⚠️ Skipped (already exist)</h2>";
    foreach ($skipped as $s) {
        echo "<div class=\"card skip-card\"><span class=\"warn\">$s</span></div>";
    }
}

if (!empty($errors)) {
    echo "<h2 class=\"err\">❌ Errors</h2>";
    foreach ($errors as $e) {
        echo "<div class=\"card err-card\"><span class=\"err\">$e</span></div>";
    }
}

echo "<br>";
echo "<a class=\"btn\" href=\"/courses/\" target=\"_blank\">🎓 View Courses Page</a>";
echo "<a class=\"btn\" href=\"/admin/courses.php\" style=\"background:#333;color:#fff;\">⚙ Admin Panel</a>";
echo "</body></html>";

