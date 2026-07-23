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

$is_logged_in = Auth::check();
$has_purchased = false;

if ($is_logged_in) {
    $user_id = Auth::id();
    
    // Check purchase
    $stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND user_id = ?");
    $stmt->execute([$course['id'], $user_id]);
    $has_purchased = (bool)$stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - CodeByTushu</title>
    
    <link rel="icon" href="../favicon.ico?v=6" sizes="any">
    <link rel="icon" href="../favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="../apple-touch-icon.png?v=6" sizes="180x180">
    <meta name="theme-color" content="#ffc400">

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Main Site Styles -->
    <link rel="stylesheet" href="../styles.css?v=40">
    <!-- Courses Styles -->
    <link rel="stylesheet" href="./courses.css?v=7">

    <style>
        .material-symbols-rounded {
            font-size: inherit;
            vertical-align: middle;
            line-height: 1;
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        
        .course-container { 
            max-width: 1000px; 
            margin: 140px auto 50px auto; 
            padding: 30px; 
            display: flex; 
            gap: 40px; 
            background: rgba(17, 17, 17, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px; 
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }
        @media(max-width: 768px) {
            .course-container {
                flex-direction: column;
                margin-top: 100px;
                padding: 20px;
                margin-left: 15px;
                margin-right: 15px;
            }
        }
        
        .course-image { flex: 1; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.05); }
        .course-image img { width: 100%; height: auto; display: block; }
        .course-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .course-info h1 { color: #ffc400; font-size: 2.2rem; margin-bottom: 15px; }
        .course-info p { color: #ddd; line-height: 1.6; margin-bottom: 25px; }
        .price { font-size: 2rem; font-weight: bold; color: #fff; margin-bottom: 25px; }
        .btn { padding: 14px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; display: inline-block; border: none; font-size: 1.1rem; transition: 0.3s; }
        .btn-primary { background: linear-gradient(90deg, #ffc400, #ff9100); color: #000; }
        .btn-primary:hover { box-shadow: 0 0 15px rgba(255, 196, 0, 0.4); transform: translateY(-2px); }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <!-- Futuristic Animated Background (Global for Courses) -->
    <div class="cbt-hero-bg" style="position: fixed; z-index: -1; top: 0; left: 0; width: 100vw; height: 100vh; pointer-events: none;">
        <div class="cbt-glow-center"></div>
        <div class="cbt-streak cbt-streak-1"></div>
        <div class="cbt-streak cbt-streak-2"></div>
        <div class="cbt-streak cbt-streak-3"></div>
        <div class="cbt-streak cbt-streak-4"></div>
        <div class="cbt-circle cbt-circle-left"></div>
        <div class="cbt-circle cbt-circle-right"></div>
        <div class="cbt-dots cbt-dots-top-left"></div>
        <div class="cbt-dots cbt-dots-bottom-right"></div>
        <div class="cbt-particles">
            <span class="cbt-particle p-1"></span>
            <span class="cbt-particle p-2"></span>
            <span class="cbt-particle p-3"></span>
            <span class="cbt-particle p-4"></span>
            <span class="cbt-particle p-5"></span>
            <span class="cbt-particle p-6"></span>
            <span class="cbt-particle p-7"></span>
            <span class="cbt-particle p-8"></span>
            <span class="cbt-particle p-9"></span>
            <span class="cbt-particle p-10"></span>
        </div>
    </div>

    <!-- ===================== NAVBAR ===================== -->
    
    <nav class="cbt-navbar navbar sticky" id="mainNavbar">
        <div class="cbt-nav-inner">
            <div class="cbt-logo" id="cbt-logo">
                <a href="../index.html" id="cbt-logo-link">
                    <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>
            
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="/courses/" class="cbt-nav-link">Home</a></li>
                <li><a href="/courses/#categories" class="cbt-nav-link">Categories</a></li>
                <li><a href="/courses/#all-courses" class="cbt-nav-link">All Courses</a></li>
                <li><a href="/courses/#faq" class="cbt-nav-link">FAQ</a></li>
            </ul>

            <div class="cbt-nav-right">
                <!-- Auth area for Login/Avatar -->
                <div id="cbt-auth-area" style="display:inline-flex;align-items:center;gap:8px;margin-left:15px;">
                </div>

                <!-- Hamburger for mobile (visible only on mobile via CSS) -->
                <button class="cbt-mobile-ham-btn" id="cbt-mobile-ham-btn"
                        aria-label="Open mobile menu" aria-expanded="false" aria-controls="cbt-mobile-drawer" style="margin-left:15px;">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>

        <!-- ══ Mobile Full Drawer ═══════════════════════════════════ -->
        <div class="cbt-mobile-overlay" id="cbt-mobile-overlay" aria-hidden="true"></div>
        <div class="cbt-mobile-drawer" id="cbt-mobile-drawer" role="dialog" aria-modal="true" aria-label="Mobile menu" aria-hidden="true">
            <div class="cbt-drawer-header">
                <div class="cbt-logo">
                    <a href="../index.html" aria-label="CodeByTushu Home" tabindex="-1">
                        <span class="cbt-logo-text" style="font-size:1.2rem;">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                    </a>
                </div>
                <button class="cbt-drawer-close" id="cbt-drawer-close" aria-label="Close menu">&#x2715;</button>
            </div>
            <div class="cbt-drawer-body">
                <ul class="cbt-drawer-primary" role="menu" aria-label="Main navigation">
                    <li role="none"><a href="/courses/" class="cbt-drawer-link" role="menuitem">Home</a></li>
                    <li role="none"><a href="/courses/#categories" class="cbt-drawer-link" role="menuitem">Categories</a></li>
                    <li role="none"><a href="/courses/#all-courses" class="cbt-drawer-link" role="menuitem">All Courses</a></li>
                    <li role="none"><a href="/user/purchases.php" class="cbt-drawer-link" role="menuitem">My Courses</a></li>
                    <li role="none"><a href="/courses/#faq" class="cbt-drawer-link" role="menuitem">FAQ</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var mobHamBtn     = document.getElementById('cbt-mobile-ham-btn');
            var mobDrawer     = document.getElementById('cbt-mobile-drawer');
            var mobOverlay    = document.getElementById('cbt-mobile-overlay');
            var drawerClose   = document.getElementById('cbt-drawer-close');
            var drawerIsOpen  = false;

            function openDrawer() {
                if (!mobDrawer) return;
                drawerIsOpen = true;
                mobOverlay.style.display = 'block';
                requestAnimationFrame(function () {
                    mobOverlay.classList.add('active');
                    mobDrawer.classList.add('is-open');
                    mobHamBtn.classList.add('is-open');
                    mobHamBtn.setAttribute('aria-expanded', 'true');
                    document.body.style.overflow = 'hidden';
                });
            }

            function closeDrawer() {
                if (!mobDrawer) return;
                drawerIsOpen = false;
                mobOverlay.classList.remove('active');
                mobDrawer.classList.remove('is-open');
                if (mobHamBtn) {
                    mobHamBtn.classList.remove('is-open');
                    mobHamBtn.setAttribute('aria-expanded', 'false');
                }
                document.body.style.overflow = '';
                setTimeout(function () {
                    if (!drawerIsOpen) {
                        mobOverlay.style.display = 'none';
                    }
                }, 300);
            }

            if (mobHamBtn) mobHamBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                drawerIsOpen ? closeDrawer() : openDrawer();
            });
            if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
            if (mobOverlay) mobOverlay.addEventListener('click', closeDrawer);
        });
    </script>

    <!-- ===================== COURSE DETAILS ===================== -->

    <div class="course-container">
        <div class="course-image">
            <img src="<?= htmlspecialchars($course['thumbnail_path'] ?: '/assets/images/default-course.jpg') ?>" alt="Course Image">
        </div>
        <div class="course-info">
            <h1><?= htmlspecialchars($course['title']) ?></h1>
            <div style="color:#ddd; line-height:1.6; margin-bottom:25px;">
                <?= $course['description'] ?: htmlspecialchars($course['short_description']) ?>
            </div>
            <div class="price">
                <?= $course['price'] > 0 ? '₹' . number_format($course['price'], 2) : 'FREE' ?>
            </div>
            
            <div id="action-area">
                <?php
                    $isFree = ($course['price'] == 0);
                    $isPurchased = false;
                    if ($is_logged_in) {
                        $pStmt = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.course_id = ? AND o.payment_status = 'verified'");
                        $pStmt->execute([$user_id, $course['id']]);
                        $isPurchased = (bool)$pStmt->fetch();
                    }
                ?>
                
                <?php if ($isPurchased || $isFree): ?>
                    <a href="/api/courses/download.php?id=<?= $course['id'] ?>" class="btn btn-primary"><i class="fas fa-download"></i> Download Course</a>
                <?php elseif (!$is_logged_in): ?>
                    <a href="/auth/login.php?redirect=/courses/<?= urlencode($slug) ?>" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login to Buy</a>
                <?php else: ?>
                    <button class="btn btn-primary" onclick="initPayment(<?= $course['id'] ?>, '<?= htmlspecialchars($course['title']) ?>', <?= $course['price'] ?>)"><i class="fas fa-lock-open"></i> Buy & Download</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/payment_modal.php'; ?>
    <script src="/auth-ui.js"></script>
</body>
</html>
