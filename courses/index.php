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
    <title>Courses | CodeByTushu</title>

    <link rel="icon" href="../favicon.ico?v=6" sizes="any">
    <link rel="icon" href="../favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="../apple-touch-icon.png?v=6" sizes="180x180">
    <meta name="theme-color" content="#ffc400">

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Main Site Styles -->
    <link rel="stylesheet" href="../styles.css?v=32">
    <!-- Courses Styles -->
    <link rel="stylesheet" href="./courses.css?v=1">

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
                    <img src="../image1/Black%20Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>
            
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="index.html" class="cbt-nav-link active">Home</a></li>
                <li><a href="#categories" class="cbt-nav-link">Categories</a></li>
                <li><a href="#all-courses" class="cbt-nav-link">All Courses</a></li>
                <li><a href="../cart/index.html" class="cbt-nav-link">My Cart</a></li>
                <li><a href="#faq" class="cbt-nav-link">FAQ</a></li>
            </ul>

            <div class="cbt-nav-right">
                <a href="../cart/index.html" class="cbt-nav-cart-btn" aria-label="Cart">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <span class="cbt-cart-counter" style="display: none;">0</span>
                </a>
                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- ===================== HERO SECTION ===================== -->
    <header class="cbt-courses-hero">
        <div style="position: relative; z-index: 10;">
            <h1>Master In-Demand Skills <br> with <span class="highlight">Practical Courses</span></h1>
        <p>Learn industry-ready skills through step-by-step courses designed for beginners as well as advanced learners. Every course focuses on practical learning and real-world projects.</p>
        
        <div class="cbt-courses-search">
            <input type="text" id="courseSearch" placeholder="Search for Java, React, DSA...">
        </div>

        <div class="cbt-courses-filters" id="courseFilters">
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
            <!-- Courses will be injected here by JS -->
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
                        <span style="color:var(--text-muted); font-size:0.85rem;">â­â­â­â­â­</span>
                    </div>
                </div>
            </div>
            <div class="cbt-course-card" style="padding: 20px;">
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"React Front to Back was practical and easy to follow. The projects are actually useful."</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#111; font-weight:bold;">S</div>
                    <div>
                        <h4 style="color:var(--text-heading); font-size:1rem;">Sneha Sharma</h4>
                        <span style="color:var(--text-muted); font-size:0.85rem;">â­â­â­â­â­</span>
                    </div>
                </div>
            </div>
            <div class="cbt-course-card" style="padding: 20px;">
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"Best DSA prep material out there. Helped me clear my Google phone screen!"</p>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#111; font-weight:bold;">R</div>
                    <div>
                        <h4 style="color:var(--text-heading); font-size:1rem;">Rahul Verma</h4>
                        <span style="color:var(--text-muted); font-size:0.85rem;">â­â­â­â­â­</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== LEARNING ROADMAP ===================== -->
    <section class="cbt-courses-container">
        <h2 class="cbt-section-title">Learning Roadmap</h2>
        <div style="background:rgba(22, 22, 22, 0.5); padding:30px; border-radius:12px; border:1px solid var(--border-color); text-align:center;">
            <p style="color:var(--text-main); margin-bottom:20px;">Beginner â†’ Intermediate â†’ Advanced â†’ Projects â†’ Interview Ready</p>
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
                    <li><a href="../cart/index.html">My Cart</a></li>
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
    <script src="js/data.js"></script>
    <script src="js/cart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const grid = document.getElementById('courseGrid');
            const search = document.getElementById('courseSearch');
            const filters = document.querySelectorAll('.cbt-filter-btn');

            function renderCourses(courses) {
                grid.innerHTML = '';
                if(courses.length === 0) {
                    grid.innerHTML = '<p style="color:var(--text-muted); text-align:center; grid-column:1/-1;">No courses found.</p>';
                    return;
                }
                courses.forEach(course => {
                    const inCart = isInCart(course.id);
                    const btnText = inCart ? 'Remove from Cart <span class="material-symbols-rounded">remove_shopping_cart</span>' : 'Add to Cart <span class="material-symbols-rounded">shopping_cart</span>';
                    const btnClass = inCart ? 'added' : '';
                    
                    const priceDisplay = course.price === 0 ? '<span class="cbt-course-price free">Free</span>' : `<span class="cbt-course-price">$${course.price}</span>`;

                    grid.innerHTML += `
                        <div class="cbt-course-card">
                            <img src="${course.image}" alt="${course.title}" class="cbt-course-thumb">
                            <div class="cbt-course-info">
                                <div class="cbt-course-meta">
                                    <span class="cbt-course-category">${course.category}</span>
                                    <span>${course.difficulty}</span>
                                </div>
                                <h3 class="cbt-course-title">${course.title}</h3>
                                <p class="cbt-course-desc">${course.description}</p>
                                
                                <div class="cbt-course-details-row">
                                    <span><span class="material-symbols-rounded">schedule</span> ${course.duration}</span>
                                    <span><span class="material-symbols-rounded">menu_book</span> ${course.lessons} Lessons</span>
                                </div>
                                
                                <div class="cbt-course-price-row">
                                    ${priceDisplay}
                                    <span style="color:var(--text-muted); font-size:0.9rem;">â­ ${course.rating} (${course.students})</span>
                                </div>

                                <div class="cbt-course-actions">
                                    <a href="course-details/index.html?id=${course.id}" class="cbt-btn cbt-btn-outline">View Details</a>
                                    <button class="cbt-btn cbt-btn-primary ${btnClass}" onclick="toggleCart('${course.id}', this)">${btnText}</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            // Initial render
            renderCourses(coursesData);

            // Filtering
            let currentFilter = 'all';
            let currentSearch = '';

            function applyFilters() {
                let filtered = coursesData.filter(c => c.title.toLowerCase().includes(currentSearch));
                
                if (currentFilter !== 'all') {
                    if (currentFilter === 'free') {
                        filtered = filtered.filter(c => c.price === 0);
                    } else if (currentFilter === 'paid') {
                        filtered = filtered.filter(c => c.price > 0);
                    } else {
                        filtered = filtered.filter(c => c.category.toLowerCase() === currentFilter);
                    }
                }
                
                renderCourses(filtered);
            }

            search.addEventListener('input', (e) => {
                currentSearch = e.target.value.toLowerCase();
                applyFilters();
            });

            filters.forEach(btn => {
                btn.addEventListener('click', () => {
                    filters.forEach(f => f.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.dataset.filter;
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
</body>
</html>
