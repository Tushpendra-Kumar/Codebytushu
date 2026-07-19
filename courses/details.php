<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
$pdo = db();

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
if (!$slug) {
    header("Location: /courses/");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE slug = ? AND is_published = 1");
$stmt->execute([$slug]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found.");
}

$is_logged_in = isset($_SESSION['user_id']);
$has_purchased = false;
$in_cart = false;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    // Check purchase
    $stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND user_id = ?");
    $stmt->execute([$course['id'], $user_id]);
    $has_purchased = (bool)$stmt->fetch();
    
    // Check cart
    $stmt = $pdo->prepare("SELECT id FROM cart_items WHERE course_id = ? AND user_id = ?");
    $stmt->execute([$course['id'], $user_id]);
    $in_cart = (bool)$stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - CodeByTushu</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        .course-container { max-width: 1000px; margin: 50px auto; padding: 20px; display: flex; gap: 40px; background: #111; border-radius: 12px; border: 1px solid #333; }
        .course-image { flex: 1; border-radius: 8px; overflow: hidden; }
        .course-image img { width: 100%; height: auto; display: block; }
        .course-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .course-info h1 { color: #ffc400; font-size: 2.2rem; margin-bottom: 15px; }
        .course-info p { color: #ddd; line-height: 1.6; margin-bottom: 25px; }
        .price { font-size: 1.8rem; font-weight: bold; color: #fff; margin-bottom: 20px; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; display: inline-block; border: none; font-size: 1.1rem; transition: 0.3s; }
        .btn-primary { background: #ffc400; color: #000; }
        .btn-primary:hover { background: #e6b000; }
        .btn-secondary { background: #333; color: #fff; }
        .btn-secondary:hover { background: #444; }
        .btn-success { background: #28a745; color: #fff; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="course-container">
        <div class="course-image">
            <img src="<?= htmlspecialchars($course['thumbnail_path'] ?: '/assets/images/default-course.jpg') ?>" alt="Course Image">
        </div>
        <div class="course-info">
            <h1><?= htmlspecialchars($course['title']) ?></h1>
            <p><?= nl2br(htmlspecialchars($course['description'] ?: $course['short_description'])) ?></p>
            <div class="price">
                <?= $course['price'] > 0 ? '₹' . number_format($course['price'], 2) : 'FREE' ?>
            </div>
            
            <div id="action-area">
                <?php if ($has_purchased): ?>
                    <a href="/user/courses.php" class="btn btn-success"><i class="fas fa-play"></i> Go to Dashboard</a>
                <?php elseif (!$is_logged_in): ?>
                    <a href="/auth/login.php?redirect=/courses/details.php?slug=<?= urlencode($slug) ?>" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login to Buy</a>
                <?php elseif ($in_cart): ?>
                    <a href="/cart/" class="btn btn-secondary"><i class="fas fa-shopping-cart"></i> Go to Cart</a>
                <?php else: ?>
                    <button id="add-to-cart-btn" class="btn btn-primary" onclick="addToCart(<?= $course['id'] ?>)"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    async function addToCart(courseId) {
        const btn = document.getElementById('add-to-cart-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        
        try {
            const formData = new FormData();
            formData.append('course_id', courseId);
            
            const res = await fetch('/api/cart/add.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('action-area').innerHTML = '<a href="/cart/" class="btn btn-secondary"><i class="fas fa-shopping-cart"></i> Go to Cart</a>';
                alert(data.message);
            } else {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
            }
        } catch(e) {
            alert('A network error occurred.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
        }
    }
    </script>
</body>
</html>
