<?php
require 'config/database.php';
$pdo = db();

$updates = [
    'java-masterclass-for-beginners' => '/uploads/courses/course_java_1785150009360.jpg',
    'react-frontend-to-backend-masterclass' => '/uploads/courses/course_react_1785150019239.jpg',
    'full-stack-web-development-masterclass' => '/uploads/courses/course_fullstack_1785150028724.jpg',
    'data-structures-algorithms-coding-interview-preparation' => '/uploads/courses/course_dsa_1785150038321.jpg',
    'devops-masterclass-for-beginners' => '/uploads/courses/course_devops_1785150048796.jpg'
];

foreach ($updates as $slug => $path) {
    $stmt = $pdo->prepare('UPDATE courses SET thumbnail_path = ? WHERE slug = ?');
    $stmt->execute([$path, $slug]);
    echo "Updated thumbnail for course: $slug<br>";
}
echo "<br><b>All thumbnails updated successfully! You can now delete this file.</b>";
