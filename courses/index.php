<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
$pdo = db();

// Fetch courses
$stmt = $pdo->prepare("SELECT * FROM courses WHERE is_published = 1 ORDER BY created_at DESC");
$stmt->execute();
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Courses - CodeByTushu</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .course-card { background: #111; border-radius: 12px; padding: 20px; border: 1px solid #333; transition: 0.3s; }
        .course-card:hover { border-color: #ffc400; transform: translateY(-5px); }
        .course-thumb { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; background: #222; }
        .course-title { color: #fff; font-size: 1.2rem; margin-bottom: 10px; }
        .course-desc { color: #aaa; font-size: 0.9rem; margin-bottom: 20px; line-height: 1.4; }
        .course-meta { display: flex; justify-content: space-between; align-items: center; }
        .course-price { color: #ffc400; font-weight: bold; font-size: 1.1rem; }
        .btn-view { background: #ffc400; color: #000; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn-view:hover { background: #e6b000; }
        .page-header { text-align: center; padding: 60px 20px 20px; }
        .page-header h1 { color: #ffc400; font-size: 2.5rem; margin-bottom: 10px; }
        .page-header p { color: #ccc; font-size: 1.1rem; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page-header">
        <h1>Premium Courses</h1>
        <p>Master programming with our high-quality courses and materials.</p>
    </div>

    <div class="courses-grid">
        <?php if(empty($courses)): ?>
            <p style="text-align: center; grid-column: 1 / -1; color: #aaa;">No courses available at the moment. Check back later!</p>
        <?php else: ?>
            <?php foreach($courses as $course): ?>
                <div class="course-card">
                    <img src="<?= htmlspecialchars($course['thumbnail_path'] ?: '/assets/images/default-course.jpg') ?>" class="course-thumb" alt="Course Thumbnail">
                    <h2 class="course-title"><?= htmlspecialchars($course['title']) ?></h2>
                    <p class="course-desc"><?= htmlspecialchars($course['short_description']) ?></p>
                    <div class="course-meta">
                        <span class="course-price"><?= $course['price'] > 0 ? '₹' . number_format($course['price'], 2) : 'FREE' ?></span>
                        <a href="/courses/details.php?slug=<?= urlencode($course['slug']) ?>" class="btn-view">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
