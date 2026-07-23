<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$pdo = db();

function addColumnIfNotExists(PDO $pdo, string $table, string $column, string $definition): void {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "<span style='color:green'>&#10004; Added column <b>$column</b> to $table.</span><br>";
    } else {
        echo "<span style='color:gray'>&#8594; Column <b>$column</b> already exists in $table.</span><br>";
    }
}

echo "<h2>CodeByTushu Database Patcher</h2>";
echo "<div style='font-family:monospace; background:#111; color:#fff; padding:20px; border-radius:8px;'>";

try {
    addColumnIfNotExists($pdo, 'courses', 'download_file_path', 'VARCHAR(500) DEFAULT NULL AFTER thumbnail_path');
    addColumnIfNotExists($pdo, 'courses', 'meta_description', 'VARCHAR(160) DEFAULT NULL AFTER is_published');
    addColumnIfNotExists($pdo, 'courses', 'content_type', "ENUM('pdf','video','mixed') NOT NULL DEFAULT 'pdf' AFTER download_file_path");
    addColumnIfNotExists($pdo, 'courses', 'price_usd', 'DECIMAL(10,2) DEFAULT NULL AFTER discount_price');
    addColumnIfNotExists($pdo, 'courses', 'seo_title', 'VARCHAR(70) DEFAULT NULL AFTER meta_description');
    addColumnIfNotExists($pdo, 'courses', 'seo_description', 'VARCHAR(165) DEFAULT NULL AFTER seo_title');
    addColumnIfNotExists($pdo, 'courses', 'seo_keywords', 'VARCHAR(500) DEFAULT NULL AFTER seo_description');
    addColumnIfNotExists($pdo, 'courses', 'import_source', 'VARCHAR(200) DEFAULT NULL AFTER seo_keywords');
    addColumnIfNotExists($pdo, 'courses', 'what_you_learn_json', 'JSON DEFAULT NULL AFTER what_you_learn');
    addColumnIfNotExists($pdo, 'courses', 'requirements_json', 'JSON DEFAULT NULL AFTER requirements');
    
    echo "<br><b style='color:#28a745'>All patches applied successfully! You can now update courses without errors.</b>";
} catch (Exception $e) {
    echo "<br><b style='color:#dc3545'>Error:</b> " . htmlspecialchars($e->getMessage());
}
echo "</div>";
