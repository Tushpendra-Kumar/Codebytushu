<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = db();
    $sql = file_get_contents(__DIR__ . '/database/migrations/011_course_store_v1.sql');
    $pdo->exec($sql);
    echo "Migration 011 executed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
