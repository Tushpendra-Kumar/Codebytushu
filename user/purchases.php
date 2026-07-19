<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireLogin();

$pdo = db();
$user_id = $_SESSION['user_id'];

// Fetch purchased courses (only from verified orders)
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.thumbnail_path, c.download_file_path 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN courses c ON oi.course_id = c.id
    WHERE o.user_id = ? AND o.payment_status = 'verified'
    GROUP BY c.id
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses & Downloads - CodeByTushu</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        h1 { color: #fff; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .empty-state { padding: 50px; text-align: center; color: #aaa; background: #111; border-radius: 12px; border: 1px dashed #444; }
        
        .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .course-card { background: #111; border: 1px solid #333; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: 0.3s; }
        .course-card:hover { border-color: #ffc400; box-shadow: 0 5px 15px rgba(255,196,0,0.1); transform: translateY(-5px); }
        .course-img { width: 100%; height: 160px; object-fit: cover; border-bottom: 1px solid #333; }
        .course-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
        .course-title { color: #fff; font-size: 1.2rem; font-weight: bold; margin-bottom: 20px; line-height: 1.4; }
        
        .btn-action { margin-top: auto; padding: 12px; border-radius: 8px; font-weight: bold; text-decoration: none; text-align: center; display: block; transition: 0.3s; }
        .btn-download { background: #ffc400; color: #000; }
        .btn-download:hover { background: #e6b000; }
        .btn-disabled { background: #333; color: #888; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="dashboard-container">
        <h1><i class="fas fa-graduation-cap"></i> My Courses</h1>
        
        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #555; margin-bottom: 15px;"></i>
                <h2>No courses found</h2>
                <p>You haven't purchased any courses yet, or your order is still pending.</p>
                <a href="/courses/index.php" class="btn-download" style="display:inline-block; margin-top:15px; padding: 10px 20px;">Explore Courses</a>
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <img src="<?= htmlspecialchars($course['thumbnail_path'] ?: '/image1/default-course.jpg') ?>" class="course-img" alt="Course Thumbnail">
                        <div class="course-body">
                            <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                            
                            <?php if ($course['download_file_path']): ?>
                                <a href="<?= htmlspecialchars($course['download_file_path']) ?>" target="_blank" class="btn-action btn-download">
                                    <i class="fas fa-download"></i> Download Material
                                </a>
                            <?php else: ?>
                                <a href="#" class="btn-action btn-disabled">
                                    <i class="fas fa-clock"></i> Coming Soon
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
