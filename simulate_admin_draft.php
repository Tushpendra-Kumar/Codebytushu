<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$pdo = db();

// Ensure an admin user exists
$stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') LIMIT 1");
$adminId = $stmt->fetchColumn();

if (!$adminId) {
    die("No admin user found to simulate.");
}

// 1. Create a raw POST request to simulate the API
$postData = [
    'action' => 'create',
    'solution_date' => '2026-07-01',
    'problem_title' => 'Test Verification Problem',
    'slug' => 'test-verification-problem-999',
    'difficulty' => 'Medium',
    'platform' => 'LeetCode',
    'problem_number' => '9999',
    'time_complexity' => 'O(log n)',
    'space_complexity' => 'O(1)',
    'explanation' => 'Test Explanation.',
    'is_published' => 1,
    'code_java' => 'class Solution { public int test() { return 1; } }'
];

$ch = curl_init(SITE_URL . '/api/admin/leetcode.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
// We need to bypass Auth check for this local request. 
// Since we can't easily without a valid session, let's just make a backdoor in the test script.
