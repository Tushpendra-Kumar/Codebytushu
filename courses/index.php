<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
$pdo = db();

// Fetch courses
$stmt = $pdo->prepare("
    SELECT c.*, cat.slug as cat_slug 
    FROM courses c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    WHERE c.is_published = 1 
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();

// Fetch purchased courses for this user (to show already-purchased state)
$purchasedCourses = [];
if (Auth::check()) {
    $purchStmt = $pdo->prepare("
        SELECT c.id FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN courses c ON oi.course_id = c.id
        WHERE o.user_id = ? AND o.payment_status = 'verified'
    ");
    $purchStmt->execute([Auth::user()['id']]);
    $purchasedCourses = $purchStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
﻿
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses | CodeByTushu</title>

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
    </style>
</head>
<body>
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

    <script src="../theme.js"></script>

    <!-- ===================== NAVBAR ===================== -->
    
    <nav class="cbt-navbar navbar" id="mainNavbar">
        <div class="cbt-nav-inner">
            <div class="cbt-logo" id="cbt-logo">
                <a href="../index.html" id="cbt-logo-link">
                    <img src="/image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>
            
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="#home" class="cbt-nav-link active">Home</a></li>
                <li><a href="#categories" class="cbt-nav-link">Categories</a></li>
                <li><a href="#all-courses" class="cbt-nav-link">All Courses</a></li>
                <li><a href="#faq" class="cbt-nav-link">FAQ</a></li>
            </ul>

            <div class="cbt-nav-right">
                
                <!-- Auth area for Login/Avatar -->
                <div id="cbt-auth-area" style="display:inline-flex;align-items:center;gap:8px;margin-left:15px;">
                    <a href="/auth/login.php" class="cbt-login-btn" id="cbt-login-btn">
                        <span>Login</span>
                    </a>
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
        <!-- Overlay -->
        <div class="cbt-mobile-overlay" id="cbt-mobile-overlay" aria-hidden="true"></div>
        <!-- Drawer -->
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
                    <li role="none"><a href="#home" class="cbt-drawer-link" role="menuitem">Home</a></li>
                    <li role="none"><a href="#categories" class="cbt-drawer-link" role="menuitem">Categories</a></li>
                    <li role="none"><a href="#all-courses" class="cbt-drawer-link" role="menuitem">All Courses</a></li>
                    <li role="none"><a href="/user/purchases.php" class="cbt-drawer-link" role="menuitem">My Courses</a></li>
                    <li role="none"><a href="#faq" class="cbt-drawer-link" role="menuitem">FAQ</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var cbtNav = document.getElementById('mainNavbar');
            if (cbtNav) {
                window.addEventListener('scroll', function () {
                    if (window.scrollY > 20) {
                        cbtNav.classList.add('sticky');
                    } else {
                        cbtNav.classList.remove('sticky');
                    }
                }, { passive: true });
            }

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

            if (mobDrawer) {
                var links = mobDrawer.querySelectorAll('.cbt-drawer-link');
                links.forEach(function(link) {
                    link.addEventListener('click', closeDrawer);
                });
            }
        });
    </script>


    <!-- ===================== HERO SECTION ===================== -->
    <header class="cbt-courses-hero" id="home">
        <div style="position: relative; z-index: 10;">
            <h1>Master In-Demand Skills <br> with <span class="highlight">Practical Courses</span></h1>
        <p>Learn industry-ready skills through step-by-step courses designed for beginners as well as advanced learners. Every course focuses on practical learning and real-world projects.</p>
        


        <div class="cbt-courses-filters" id="categories">
            <button class="cbt-filter-btn active" data-filter="all">All Courses</button>
            <button class="cbt-filter-btn" data-filter="java">Java</button>
            <button class="cbt-filter-btn" data-filter="react">React</button>
            <button class="cbt-filter-btn" data-filter="dsa">DSA</button>
            <button class="cbt-filter-btn" data-filter="web development">Web Dev</button>
            <button class="cbt-filter-btn" data-filter="free">Free</button>
            <button class="cbt-filter-btn" data-filter="paid">Paid</button>
        </div>
        </div>
    </header>

    <!-- ===================== COURSE GRID ===================== -->
    <main class="cbt-courses-container" id="all-courses">
        <h2 class="cbt-section-title">All Courses</h2>
        <div class="cbt-course-grid" id="courseGrid">
        <?php if(empty($courses)): ?>
            <p style="color:var(--text-muted); text-align:center; grid-column:1/-1;">No courses found.</p>
        <?php else: ?>
            <?php foreach($courses as $course): ?>
                <?php 
                    $priceDisplay = $course['price'] == 0 ? '<span class="cbt-course-price free">Free</span>' : '<span class="cbt-course-price">₹' . number_format($course['price'], 2) . '</span>';
                    $thumb = $course['thumbnail_path'] ?: '/assets/images/default-course.jpg';
                ?>
                <?php 
                    $cat = strtolower($course['cat_slug'] ?? 'all');
                ?>
                <div class="cbt-course-card" 
                     data-title="<?= htmlspecialchars(strtolower($course['title'])) ?>"
                     data-category="<?= htmlspecialchars($cat) ?>"
                     data-price="<?= (float)$course['price'] ?>">
                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($course['title']) ?>" class="cbt-course-thumb">
                    <div class="cbt-course-info">
                        <div class="cbt-course-meta">
                            <span class="cbt-course-category"><?= htmlspecialchars($course['level'] ?? 'All Levels') ?></span>
                        </div>
                        <h3 class="cbt-course-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="cbt-course-desc"><?= htmlspecialchars($course['short_description']) ?></p>
                        
                        <div class="cbt-course-details-row">
                            <span><span class="material-symbols-rounded">schedule</span> <?= $course['duration_hours'] ? $course['duration_hours'].' hrs' : 'Self-paced' ?></span>
                            <span><span class="material-symbols-rounded">menu_book</span> <?= $course['total_lessons'] ?? 0 ?> Lessons</span>
                        </div>
                        
                        <div class="cbt-course-price-row">
                            <?= $priceDisplay ?>
                        </div>

                        <div class="cbt-course-actions">
                            <a href="/courses/<?= urlencode($course['slug']) ?>" class="cbt-btn cbt-btn-outline">View Details</a>
                            
                            <?php
                                $isFree     = ($course['price'] == 0);
                                $isPurchased = in_array($course['id'], $purchasedCourses);
                                // content_type: 'pdf' | 'video' | null (future-proof)
                                $contentType = $course['content_type'] ?? 'pdf';
                            ?>

                            <?php if ($isPurchased): ?>
                                <!-- Already purchased: direct download -->
                                <a href="/api/courses/download.php?id=<?= $course['id'] ?>" class="cbt-btn cbt-btn-primary">
                                    <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">download</span> Download Course
                                </a>
                            <?php elseif ($isFree): ?>
                                <!-- Free course: direct download -->
                                <a href="/api/courses/download.php?id=<?= $course['id'] ?>" class="cbt-btn cbt-btn-primary">
                                    <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">download</span> Download Course
                                </a>
                            <?php else: ?>
                                <!-- Paid course: go to payment -->
                                <button type="button" class="cbt-btn cbt-btn-primary" onclick="initPayment(<?= $course['id'] ?>, '<?= htmlspecialchars($course['title']) ?>', <?= $course['price'] ?>)">
                                    <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">lock_open</span> Download Course
                                </button>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </main>

    <!-- ===================== STUDENT REVIEWS ===================== -->
    <section class="cbt-courses-container" style="padding-top: 40px;">
        <h2 class="cbt-section-title">Student Reviews</h2>
        <div class="cbt-course-grid">
            <div class="cbt-course-card" style="padding: 20px;">
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"The Java Masterclass completely changed my understanding of OOP. Highly recommended!"</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#111; font-weight:bold;">A</div>
                    <div>
                        <h4 style="color:var(--text-heading); font-size:1rem;">Amit Kumar</h4>
                        <span style="color:#ffc400; font-size:0.95rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    </div>
                </div>
            </div>
            <div class="cbt-course-card" style="padding: 20px;">
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"React Front to Back was practical and easy to follow. The projects are actually useful."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#111; font-weight:bold;">S</div>
                    <div>
                        <h4 style="color:var(--text-heading); font-size:1rem;">Sneha Sharma</h4>
                        <span style="color:#ffc400; font-size:0.95rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    </div>
                </div>
            </div>
            <div class="cbt-course-card" style="padding: 20px;">
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"Best DSA prep material out there. Helped me clear my Google phone screen!"</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#111; font-weight:bold;">R</div>
                    <div>
                        <h4 style="color:var(--text-heading); font-size:1rem;">Rahul Verma</h4>
                        <span style="color:#ffc400; font-size:0.95rem;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== LEARNING ROADMAP ===================== -->
    <section class="cbt-courses-container">
        <h2 class="cbt-section-title">Learning Roadmap</h2>
        <div style="background:rgba(22, 22, 22, 0.5); padding:30px; border-radius:12px; border:1px solid var(--border-color); text-align:center;">
            <p style="color:var(--text-main); margin-bottom:20px;">Beginner <i class="fa-solid fa-arrow-right" style="margin:0 5px; font-size:0.9em; opacity:0.7;"></i> Intermediate <i class="fa-solid fa-arrow-right" style="margin:0 5px; font-size:0.9em; opacity:0.7;"></i> Advanced <i class="fa-solid fa-arrow-right" style="margin:0 5px; font-size:0.9em; opacity:0.7;"></i> Projects <i class="fa-solid fa-arrow-right" style="margin:0 5px; font-size:0.9em; opacity:0.7;"></i> Interview Ready</p>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
                <div style="flex:1; min-width:150px; background:rgba(10, 10, 10, 0.5); padding:20px; border-radius:8px; border:1px solid var(--primary);">
                    <h3 style="color:var(--primary);">1. Basics</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Syntax & Fundamentals</p>
                </div>
                <span class="material-symbols-rounded" style="color:var(--text-muted);">arrow_forward</span>
                <div style="flex:1; min-width:150px; background:rgba(10, 10, 10, 0.5); padding:20px; border-radius:8px; border:1px solid var(--primary);">
                    <h3 style="color:var(--primary);">2. Core</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem;">OOP & Logic Building</p>
                </div>
                <span class="material-symbols-rounded" style="color:var(--text-muted);">arrow_forward</span>
                <div style="flex:1; min-width:150px; background:rgba(10, 10, 10, 0.5); padding:20px; border-radius:8px; border:1px solid var(--primary);">
                    <h3 style="color:var(--primary);">3. Build</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem;">Real World Projects</p>
                </div>
                <span class="material-symbols-rounded" style="color:var(--text-muted);">arrow_forward</span>
                <div style="flex:1; min-width:150px; background:rgba(10, 10, 10, 0.5); padding:20px; border-radius:8px; border:1px solid var(--primary);">
                    <h3 style="color:var(--primary);">4. Interview</h3>
                    <p style="color:var(--text-muted); font-size:0.9rem;">DSA & System Design</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== FAQ ===================== -->
    <section class="cbt-courses-container" id="faq">
        <h2 class="cbt-section-title">Frequently Asked Questions</h2>
        <div style="display:flex; flex-direction:column; gap:15px;">
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">How long do I have access to a course?</h3>
                <p style="color:var(--text-muted);">You get lifetime access to all purchased courses, including future updates.</p>
            </div>
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">Will I get a certificate?</h3>
                <p style="color:var(--text-muted);">Yes, upon completing 100% of the course modules, you will receive a digital certificate of completion.</p>
            </div>
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">Is there a refund policy?</h3>
                <p style="color:var(--text-muted);">Yes, we offer a 7-day money-back guarantee if you are not satisfied with the course content.</p>
            </div>
        </div>
    </section>

    <!-- ===================== STAY UPDATED ===================== -->
    <section class="cbt-courses-container">
        <div style="background:rgba(255, 196, 0, 0.15); padding:40px; border-radius:12px; text-align:center; border: 1px solid rgba(255,196,0,0.3); backdrop-filter: blur(10px);">
            <h2 style="color:var(--primary); font-size:2rem; margin-bottom:10px;">Stay Updated!</h2>
            <p style="color:#eee; margin-bottom:20px; font-weight:500;">Get the latest updates on new courses, discount coupons, and free resources.</p>
            <form class="cbt-course-subscribe-form" style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
                <input type="email" class="newsletter-email" name="email" placeholder="Enter your email address" required style="padding:12px 20px; border-radius:8px; border:none; width:100%; max-width:350px; outline:none; font-family:inherit; background: rgba(0,0,0,0.6); color: #fff;">
                <button type="submit" class="newsletter-btn-text" style="padding:12px 24px; border-radius:8px; border:none; background:var(--primary); color:#111; font-weight:bold; cursor:pointer;">Subscribe</button>
            </form>
            <div class="newsletter-message" style="margin-top: 15px; font-size: 14px; display: none; padding: 10px; border-radius: 6px; max-width: 400px; margin-left: auto; margin-right: auto; text-align: center;"></div>
        </div>
    </section>

    <!-- ===================== FOOTER ===================== -->
    <footer class="cbt-footer">
        <div class="cbt-footer-container">
            <!-- Brand Column -->
            <div class="cbt-ft-col cbt-ft-brand">
                <a href="../index.html" class="cbt-logo">
                    <span class="cbt-logo-bracket">&lt;/&gt;</span>
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
                <p style="margin-top: 15px; color: var(--text-muted); line-height: 1.6;">Master in-demand skills through practical, real-world courses designed for beginners to advanced learners.</p>
            </div>

            <!-- Quick Links -->
            <div class="cbt-ft-col">
                <h3><i class="fa-solid fa-link"></i> QUICK LINKS</h3>
                <ul class="cbt-ft-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="index.html#categories">Categories</a></li>
                    <li><a href="index.html#all-courses">All Courses</a></li>
                    <li><a href="index.html#faq">FAQ</a></li>
                </ul>
            </div>

            <!-- Course Categories -->
            <div class="cbt-ft-col">
                <h3><i class="fa-solid fa-book-open"></i> COURSE CATEGORIES</h3>
                <ul class="cbt-ft-links">
                    <li><a href="index.html#all-courses">Java</a></li>
                    <li><a href="index.html#all-courses">React</a></li>
                    <li><a href="index.html#all-courses">DSA</a></li>
                    <li><a href="index.html#all-courses">Web Development</a></li>
                    <li><a href="index.html#all-courses">Free Courses</a></li>
                    <li><a href="index.html#all-courses">Paid Courses</a></li>
                </ul>
            </div>

            <!-- Resources -->
            <div class="cbt-ft-col">
                <h3><i class="fa-regular fa-folder-open"></i> RESOURCES</h3>
                <ul class="cbt-ft-links">
                    <li><a href="../privacy-policy/index.html">Privacy Policy</a></li>
                    <li><a href="../terms/index.html">Terms &amp; Conditions</a></li>
                    <li><a href="../#contact">Contact</a></li>
                    <li><a href="../#support">Support</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="cbt-ft-col cbt-ft-newsletter">
                <h3><i class="fa-regular fa-paper-plane"></i> STAY UPDATED</h3>
                <p>Subscribe to get the latest updates on new courses, discount coupons, and free resources.</p>
                <form class="cbt-ft-form cbt-course-subscribe-form">
                    <div class="cbt-ft-input-group">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" class="newsletter-email" name="email" placeholder="Enter your email" required aria-label="Email Address">
                    </div>
                    <button type="submit" class="cbt-ft-btn">
                        <span class="newsletter-btn-text">Subscribe Now</span> 
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
                <div class="newsletter-message" style="margin-top: 15px; font-size: 14px; display: none; padding: 10px; border-radius: 6px;"></div>
                
                <h3 style="margin-top: 25px;"><i class="fa-regular fa-user"></i> CONNECT</h3>
                <ul class="cbt-ft-links" style="display: flex; gap: 15px; font-size: 1.3rem;">
                    <li><a href="https://github.com/Tushpendra-Kumar" target="_blank"><i class="fa-brands fa-github"></i></a></li>
                    <li><a href="https://linkedin.com/company/codebytushu" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a></li>
                    <li><a href="https://youtube.com/@codebytushu" target="_blank"><i class="fa-brands fa-youtube"></i></a></li>
                    <li><a href="https://instagram.com/codebytushu" target="_blank"><i class="fa-brands fa-instagram"></i></a></li>
                </ul>
            </div>
        </div>

        <div class="cbt-copyright-strip" style="text-align: center; border-top: 1px solid var(--border-color); padding: 20px 0; margin-top: 20px;">
            <p>&copy; 2025 CodeByTushu. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const grid = document.getElementById('courseGrid');
            const search = document.getElementById('courseSearch');
            const filters = document.querySelectorAll('.cbt-filter-btn');
            const cards = document.querySelectorAll('.cbt-course-card');

            let currentFilter = 'all';
            let currentSearch = '';

            function applyFilters() {
                let visibleCount = 0;
                cards.forEach(card => {
                    const title = card.getAttribute('data-title').toLowerCase();
                    const cat = card.getAttribute('data-category').toLowerCase();
                    const price = parseFloat(card.getAttribute('data-price') || 0);
                    
                    const matchSearch = title.includes(currentSearch);
                    let matchFilter = false;

                    if (currentFilter === 'all') {
                        matchFilter = true;
                    } else if (currentFilter === 'free') {
                        matchFilter = price === 0;
                    } else if (currentFilter === 'paid') {
                        matchFilter = price > 0;
                    } else {
                        matchFilter = cat.includes(currentFilter) || currentFilter.includes(cat);
                    }

                    if (matchSearch && matchFilter) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                let noResultsMsg = document.getElementById('cbt-no-results');
                if (visibleCount === 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('p');
                        noResultsMsg.id = 'cbt-no-results';
                        noResultsMsg.style = 'color:var(--text-muted); text-align:center; grid-column:1/-1; padding:20px;';
                        noResultsMsg.textContent = 'No courses found.';
                        grid.appendChild(noResultsMsg);
                    } else {
                        noResultsMsg.style.display = 'block';
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }

            if (search) {
                search.addEventListener('input', (e) => {
                    currentSearch = e.target.value.toLowerCase().trim();
                    applyFilters();
                });
            }

            filters.forEach(btn => {
                btn.addEventListener('click', () => {
                    filters.forEach(f => f.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    applyFilters();
                });
            });

            // Hamburger toggle
            const ham = document.getElementById('cbt-hamburger-btn');
            const nav = document.getElementById('cbt-center-nav');
            if(ham && nav) {
                ham.addEventListener('click', () => {
                    nav.classList.toggle('show');
                });
            }

            // Newsletter Subscription
            const subscribeForms = document.querySelectorAll('.cbt-course-subscribe-form');
            subscribeForms.forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    const emailInput = form.querySelector('.newsletter-email');
                    const btn = form.querySelector('button[type="submit"]');
                    const btnText = form.querySelector('.newsletter-btn-text') || btn;
                    const msgDiv = form.parentNode.querySelector('.newsletter-message');
                    
                    const originalText = btnText.innerHTML;
                    btn.disabled = true;
                    btnText.innerHTML = 'Subscribing...';
                    msgDiv.style.display = 'none';
                    
                    try {
                        const response = await fetch('../api/subscribe.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: emailInput.value })
                        });
                        
                        const data = await response.json();
                        
                        msgDiv.style.display = 'block';
                        msgDiv.textContent = data.message;
                        
                        if (data.success) {
                            msgDiv.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
                            msgDiv.style.color = '#4CAF50';
                            msgDiv.style.border = '1px solid #4CAF50';
                            form.reset();
                        } else {
                            msgDiv.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
                            msgDiv.style.color = '#f44336';
                            msgDiv.style.border = '1px solid #f44336';
                        }
                    } catch (error) {
                        msgDiv.style.display = 'block';
                        msgDiv.textContent = 'Network error. Please try again later.';
                        msgDiv.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
                        msgDiv.style.color = '#f44336';
                        msgDiv.style.border = '1px solid #f44336';
                    } finally {
                        btn.disabled = false;
                        btnText.innerHTML = originalText;
                    }
                });
            });
        });
    </script>

    <script src="/auth-ui.js"></script>
    <script src="/auth-ui.js"></script>
    <?php include __DIR__ . '/payment_modal.php'; ?>
    <script>
        // Navigation active state and smooth scrolling
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.cbt-nav-link');
            const sections = document.querySelectorAll('header[id], div[id], section[id], main[id]');
            
            // Smooth scroll
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId.startsWith('#')) {
                        e.preventDefault();
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            window.scrollTo({
                                top: targetElement.offsetTop - 80, // adjust for navbar height
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });

            // Active state on scroll
            window.addEventListener('scroll', () => {
                let current = '';
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (pageYOffset >= (sectionTop - 150)) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + current) {
                        link.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
