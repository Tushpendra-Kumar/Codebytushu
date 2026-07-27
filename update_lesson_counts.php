<?php
require 'config/database.php';
$pdo = db();

$updates = [
    'react-frontend-to-backend-masterclass' => 55,
    'full-stack-web-development-masterclass' => 60,
    'data-structures-algorithms-coding-interview-preparation' => 62,
    'devops-masterclass-for-beginners' => 56
];

foreach ($updates as $slug => $count) {
    $stmt = $pdo->prepare('UPDATE courses SET total_lessons = ? WHERE slug = ?');
    $stmt->execute([$count, $slug]);
    echo "Updated lesson count for course: $slug to $count<br>";
}
echo "<br><b>All lesson counts updated successfully! You can now delete this file.</b>";
