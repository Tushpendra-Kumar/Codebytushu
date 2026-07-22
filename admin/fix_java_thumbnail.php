<?php
/**
 * One-time runner to set the generated thumbnail for the Java Masterclass course.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/app.php';
require_once $rootDir . '/config/database.php';

$pdo = db();

try {
    $stmt = $pdo->prepare("UPDATE courses SET thumbnail_path = '/assets/images/courses/java-masterclass.jpg' WHERE slug = 'java-masterclass-for-beginners'");
    $stmt->execute();
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Thumbnail Fixed</title>';
    echo '<style>body{font-family:sans-serif;padding:40px;background:#111;color:#fff;text-align:center;} h1{color:#22c55e;}</style></head><body>';
    echo '<h1>✓ Thumbnail successfully attached to the database!</h1>';
    echo '<p>The Java Masterclass course now has the custom AI-generated thumbnail.</p>';
    echo '<a href="/courses/" style="display:inline-block;margin-top:20px;padding:10px 20px;background:#ffc400;color:#000;text-decoration:none;border-radius:8px;font-weight:bold;">View Courses Page</a>';
    echo '&nbsp;&nbsp;<a href="/admin/courses.php" style="display:inline-block;margin-top:20px;padding:10px 20px;background:#333;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;">Go to Admin Panel</a>';
    echo '</body></html>';
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
