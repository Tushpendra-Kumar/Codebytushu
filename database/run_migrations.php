<?php
/**
 * CodeByTushu — Database Migration Runner
 * Runs pending SQL migration files in order.
 * USAGE: Visit this page from browser (admin only) or run via CLI:
 *   php e:\Codebytushu\database\run_migrations.php
 *
 * SECURITY: This file MUST be protected. It is blocked in .htaccess
 * and should only be run locally or removed after migrations are applied.
 */
declare(strict_types=1);

// Load environment
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();

// Migration files — in execution order
$migrations = [
    '004_leetcode_missing_columns.sql',
    '005_migrate_may2026_solutions.sql',
];

$migrationsDir = __DIR__ . '/migrations/';
$results = [];

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Migration Runner — CodeByTushu</title>";
echo "<style>body{font-family:monospace;background:#0f0f0f;color:#e0e0e0;padding:40px;} ";
echo ".ok{color:#28c840;} .err{color:#ff5f57;} .info{color:#ffd54f;} pre{background:#1a1a1a;padding:20px;border-radius:10px;overflow-x:auto;}</style>";
echo "</head><body>";
echo "<h1 style='color:#f5a623'>CodeByTushu — Migration Runner</h1>";

foreach ($migrations as $file) {
    $path = $migrationsDir . $file;
    echo "<h2 style='margin-top:30px'>Running: <span class='info'>{$file}</span></h2>";

    if (!file_exists($path)) {
        echo "<p class='err'>✗ File not found: {$path}</p>";
        continue;
    }

    $sql = file_get_contents($path);
    if (!$sql) {
        echo "<p class='err'>✗ Could not read file.</p>";
        continue;
    }

    // Split on semicolons (skip empty statements and comments)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => $s !== '' && !str_starts_with($s, '--') && !str_starts_with($s, '/*')
    );

    $errors = [];
    $count  = 0;

    foreach ($statements as $stmt) {
        if (trim($stmt) === '') continue;
        try {
            $pdo->exec($stmt);
            $count++;
        } catch (\PDOException $e) {
            // Ignore "duplicate column" / "already exists" — these are safe to skip
            $msg = $e->getMessage();
            $ignorable = (
                str_contains($msg, 'Duplicate column name') ||
                str_contains($msg, 'already exists') ||
                str_contains($msg, '1060') || // Duplicate column
                str_contains($msg, '1061')    // Duplicate key name
            );
            if (!$ignorable) {
                $errors[] = ['stmt' => substr($stmt, 0, 120) . '...', 'error' => $msg];
            }
        }
    }

    if (empty($errors)) {
        echo "<p class='ok'>✓ Executed {$count} statement(s) successfully.</p>";
    } else {
        echo "<p class='ok'>✓ Executed {$count} statement(s).</p>";
        echo "<p class='err'>⚠ " . count($errors) . " error(s):</p><ul>";
        foreach ($errors as $e) {
            echo "<li class='err'><strong>" . htmlspecialchars($e['error']) . "</strong><br>";
            echo "<code>" . htmlspecialchars($e['stmt']) . "</code></li>";
        }
        echo "</ul>";
    }
}

// Verification queries
echo "<h2 style='margin-top:40px;color:#f5a623'>Verification</h2>";

echo "<h3>leetcode_solutions columns:</h3><pre>";
$rows = $pdo->query("SHOW COLUMNS FROM leetcode_solutions WHERE Field IN ('platform','thumbnail_path','pdf_path','slug','problem_title')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['Field'] . ' → ' . $r['Type'] . ' (default: ' . ($r['Default'] ?? 'NULL') . ")\n";
}
echo "</pre>";

echo "<h3>Seeded solutions:</h3><pre>";
$sols = $pdo->query("SELECT id, slug, problem_title, difficulty, is_published, solution_date FROM leetcode_solutions ORDER BY solution_date LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
if (empty($sols)) {
    echo "(no solutions in DB yet)\n";
} else {
    foreach ($sols as $s) {
        echo "[{$s['id']}] {$s['solution_date']} | {$s['problem_title']} | {$s['difficulty']} | " . ($s['is_published'] ? 'PUBLISHED' : 'draft') . "\n";
    }
}
echo "</pre>";

echo "<h3>Code blocks:</h3><pre>";
$blocks = $pdo->query("SELECT cb.id, cb.language, cb.sort_order, LEFT(cb.code,60) as snippet, s.slug FROM solution_code_blocks cb JOIN leetcode_solutions s ON s.id=cb.solution_id ORDER BY cb.solution_id, cb.sort_order LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
if (empty($blocks)) {
    echo "(no code blocks)\n";
} else {
    foreach ($blocks as $b) {
        echo "[{$b['slug']}] {$b['language']} → " . str_replace("\n", ' ', $b['snippet']) . "...\n";
    }
}
echo "</pre>";

echo "<h3>Year / Month structure:</h3><pre>";
$years = $pdo->query("SELECT y.year, m.month_name, m.month_num, m.published_solutions FROM leetcode_years y LEFT JOIN leetcode_months m ON m.year_id=y.id ORDER BY y.year, m.month_num")->fetchAll(PDO::FETCH_ASSOC);
if (empty($years)) {
    echo "(no years/months)\n";
} else {
    foreach ($years as $r) {
        echo $r['year'] . '/' . ($r['month_num'] ?? '?') . ' ' . ($r['month_name'] ?? '-') . ' → ' . ($r['published_solutions'] ?? 0) . " published\n";
    }
}
echo "</pre>";

echo "</body></html>";
