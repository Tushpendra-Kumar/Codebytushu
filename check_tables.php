<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>CodeByTushu - Database Diagnostics</h2>";

try {
    $pdo = db();
    echo "<p>✅ Database Connected Successfully!</p>";
    
    // Check tables
    $tablesToCheck = [
        'leetcode_years',
        'leetcode_months',
        'leetcode_solutions',
        'blogs',
        'courses',
        'store_products'
    ];
    
    echo "<h3>Checking Tables:</h3><ul>";
    $allGood = true;
    
    foreach ($tablesToCheck as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color:green;'>✅ Table <b>$table</b> exists.</li>";
            
            // Check columns if it's leetcode_years
            if ($table === 'leetcode_years') {
                $colStmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'is_visible'");
                if ($colStmt->rowCount() > 0) {
                    echo "<li style='color:green; margin-left: 20px;'>✅ Column <b>is_visible</b> exists in $table.</li>";
                } else {
                    echo "<li style='color:red; margin-left: 20px;'>❌ Column <b>is_visible</b> MISSING in $table!</li>";
                    $allGood = false;
                }
            }
        } else {
            echo "<li style='color:red;'>❌ Table <b>$table</b> is MISSING!</li>";
            $allGood = false;
        }
    }
    echo "</ul>";
    
    if ($allGood) {
        echo "<h3 style='color:green;'>All required tables are present! The 500 error must be something else.</h3>";
        
        // Let's try running the exact failing query just to see what happens
        echo "<h3>Testing Leetcode Query:</h3>";
        $testQuery = "SELECT id, year, badge_label, description FROM leetcode_years WHERE year = 2026 AND is_visible = 1 LIMIT 1";
        $stmt = $pdo->query($testQuery);
        $row = $stmt->fetch();
        if ($row) {
            echo "<p style='color:green;'>✅ Query succeeded. Data found: " . json_encode($row) . "</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Query succeeded but no data found for year 2026.</p>";
        }
        
    } else {
        echo "<h3 style='color:red;'>⚠️ Missing Tables Detected!</h3>";
        echo "<p>Please import the <b>database/schema.sql</b> file into your Hostinger phpMyAdmin.</p>";
    }
    
} catch (Throwable $e) {
    echo "<h3 style='color:red;'>❌ Exception Caught!</h3>";
    echo "<pre style='background:#f4f4f4; padding: 10px; border: 1px solid #ddd;'>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}
?>
