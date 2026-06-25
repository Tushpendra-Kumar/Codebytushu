<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Auth.php';

// Force login as admin 1
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';

// Simulate POST request
$_POST = [
    'action' => 'create',
    'solution_date' => '2026-06-20',
    'problem_title' => 'Test Problem',
    'slug' => 'test-problem-123',
    'difficulty' => 'Easy',
    'platform' => 'LeetCode',
    'problem_number' => '123',
    'time_complexity' => 'O(1)',
    'space_complexity' => 'O(1)',
    'description' => 'This is a test description.',
    'is_published' => 1
];

// We bypass CSRF for CLI testing by setting it in SERVER
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'test-token';
$_SESSION['_cbt_csrf'] = 'test-token';

ob_start();
try {
    require __DIR__ . '/api/admin/leetcode.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "Response:\n";
echo $output;
