<?php
require_once __DIR__ . '/../classes/Auth.php';
Auth::boot();
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogs | CodeByTushu</title>

    <link rel="icon" href="../favicon.ico?v=6" sizes="any">
    <link rel="icon" href="../favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="../apple-touch-icon.png?v=6" sizes="180x180">
    <meta name="theme-color" content="#ffc400">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <link rel="stylesheet" href="../styles.css?v=40">
    <link rel="stylesheet" href="./blogs.css?v=1">
</head>
<body>
    <!-- Futuristic Animated Background (Global for Blogs) -->
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
                    <img src="../image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>
            
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="#home" class="cbt-nav-link active">Home</a></li>
                <li><a href="#categories" class="cbt-nav-link">Categories</a></li>
                <li><a href="#tags" class="cbt-nav-link">Tags</a></li>
                <li><a href="#all-blogs" class="cbt-nav-link">All Blogs</a></li>
                <li><a href="#faq" class="cbt-nav-link">FAQ</a></li>
            </ul>

            <div class="cbt-nav-right">
                <button class="cbt-nav-cart-btn" aria-label="Search" style="background:none; border:none; color:#fff; cursor:pointer;" onclick="document.getElementById('blogSearch').scrollIntoView({behavior: 'smooth', block: 'center'}); setTimeout(() => document.getElementById('blogSearch').focus(), 500);">
                    <span class="material-symbols-rounded">search</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- ===================== HERO SECTION ===================== -->
    <header class="cbt-blog-hero" id="home">
        <h1>Learn Programming Through <br><span style="color:var(--primary);">Practical Articles</span></h1>
        <p>Explore in-depth tutorials, interview guides, and best practices tailored to help you understand complex concepts easily.</p>
        
        <div class="cbt-blog-search">
            <i class="fa-solid fa-search search-icon"></i>
            <input type="text" id="blogSearch" placeholder="Search for React, Java, Interview tips..." autocomplete="off">
            <div id="blogSearchDropdown" class="cbt-search-dropdown" style="display:none;"></div>
        </div>
    </header>

    <!-- ===================== CATEGORIES SECTION ===================== -->
    <section class="cbt-blog-container" id="categories" style="margin-bottom: 40px; padding-top: 20px;">
        <h2 class="cbt-section-title" style="font-size: 1.8rem; text-align: center;">Filter by Category</h2>
        <div class="cbt-blog-filters" id="categoryFilters">
            <!-- Categories injected via JS -->
        </div>
    </section>

    <!-- ===================== TAGS SECTION ===================== -->
    <section class="cbt-blog-container" id="tags" style="margin-bottom: 50px; padding-top: 20px;">
        <h2 class="cbt-section-title" style="font-size: 1.8rem; text-align: center;">Popular Tags</h2>
        <div class="cbt-blog-filters" id="tagFilters">
            <button class="cbt-filter-chip active" id="btn-all-tags" onclick="resetTags()">All Tags</button>
            <!-- Tags injected via JS -->
        </div>
    </section>

    <!-- ===================== ALL BLOGS SECTION ===================== -->
    <section class="cbt-blog-container" id="all-blogs" style="margin-bottom: 60px; padding-top: 20px;">
        <h2 class="cbt-section-title" style="font-size: 2rem; margin-bottom: 30px;">All Blogs</h2>
        <div class="cbt-blog-grid" id="blogGrid">
            <!-- Blogs injected via JS -->
        </div>
    </section>

    <!-- ===================== FAQ ===================== -->
    <section class="cbt-blog-container" id="faq">
        <h2 class="cbt-section-title">Frequently Asked Questions</h2>
        <div style="display:flex; flex-direction:column; gap:15px;">
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">Are the blogs free to read?</h3>
                <p style="color:var(--text-muted);">Yes, all our blog articles, tutorials, and guides are completely free and open to everyone.</p>
            </div>
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">How often is new content published?</h3>
                <p style="color:var(--text-muted);">We publish new articles every week. Subscribe to our newsletter to never miss an update.</p>
            </div>
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">Can I request a specific topic?</h3>
                <p style="color:var(--text-muted);">Absolutely! Feel free to reach out to us via our contact form or social media handles to request tutorials.</p>
            </div>
        </div>
    </section>

    <!-- ===================== STAY UPDATED ===================== -->
    <section class="cbt-blog-container" style="margin-bottom: 50px;">
        <div style="background:rgba(255, 196, 0, 0.15); padding:40px; border-radius:12px; text-align:center; border: 1px solid rgba(255,196,0,0.3); backdrop-filter: blur(10px);">
            <h2 style="color:var(--primary); font-size:2rem; margin-bottom:10px;">Never Miss an Update</h2>
            <p style="color:#eee; margin-bottom:20px; font-weight:500;">Get the latest articles, tutorials, and resources delivered straight to your inbox.</p>
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
                <p style="margin-top: 15px; color: var(--text-muted); line-height: 1.6;">Your ultimate destination for in-depth programming tutorials, career advice, and interview preparation.</p>
            </div>

            <!-- Quick Links -->
            <div class="cbt-ft-col">
                <h3><i class="fa-solid fa-link"></i> QUICK LINKS</h3>
                <ul class="cbt-ft-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="#categories">Categories</a></li>
                    <li><a href="#all-blogs">All Blogs</a></li>
                    <li><a href="#tags">Tags</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="cbt-ft-col">
                <h3><i class="fa-solid fa-book"></i> BLOG CATEGORIES</h3>
                <ul class="cbt-ft-links">
                    <li><a href="#all-blogs">Java & JavaScript</a></li>
                    <li><a href="#all-blogs">React & Node.js</a></li>
                    <li><a href="#all-blogs">Data Structures (DSA)</a></li>
                    <li><a href="#all-blogs">Interview Prep</a></li>
                    <li><a href="#all-blogs">Artificial Intelligence</a></li>
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

            <!-- Connect -->
            <div class="cbt-ft-col cbt-ft-newsletter">
                <h3 style="margin-top: 0px;"><i class="fa-regular fa-user"></i> CONNECT WITH US</h3>
                <p style="margin-bottom: 20px;">Join our community on social media for daily updates and tips.</p>
                <ul class="cbt-ft-links" style="display: flex; gap: 15px; font-size: 1.3rem;">
                    <li><a href="https://github.com/Tushpendra-Kumar" target="_blank"><i class="fa-brands fa-github"></i></a></li>
                    <li><a href="https://linkedin.com/company/codebytushu" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a></li>
                    <li><a href="https://youtube.com/@codebytushu" target="_blank"><i class="fa-brands fa-youtube"></i></a></li>
                    <li><a href="https://instagram.com/codebytushu" target="_blank"><i class="fa-brands fa-instagram"></i></a></li>
                </ul>
            </div>
        </div>

        <div class="cbt-copyright-strip" style="text-align: center; border-top: 1px solid var(--border-color); padding: 20px 0; margin-top: 20px; background: transparent !important;">
            <p>&copy; 2025 CodeByTushu. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- JS files -->
    <script src="js/data.js"></script>
    <script src="js/blogs.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Newsletter Subscription Logic (Reused)
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
</body>
</html>
