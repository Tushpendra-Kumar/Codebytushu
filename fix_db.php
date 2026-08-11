<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
$pdo = db();
try {
    $pdo->exec("ALTER TABLE `order_items` MODIFY COLUMN `course_id` INT UNSIGNED NULL");
    echo "SUCCESS";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
