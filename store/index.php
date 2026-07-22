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
    <title>Store | CodeByTushu</title>

    <link rel="icon" href="../favicon.ico?v=6" sizes="any">
    <link rel="icon" href="../favicon-32x32.png?v=6" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="../apple-touch-icon.png?v=6" sizes="180x180">
    <meta name="theme-color" content="#ffc400">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <link rel="stylesheet" href="../styles.css?v=40">
    <link rel="stylesheet" href="./store.css?v=2">
</head>
<body>
    <!-- Futuristic Animated Background (Global for Store) -->
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

            <!-- Dedicated Store Navbar -->
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="index.html" class="cbt-nav-link active">Home</a></li>
                <li><a href="#categories" class="cbt-nav-link">Categories</a></li>
                <li><a href="#all-products" class="cbt-nav-link">All Products</a></li>
                <li><a href="cart/index.html" class="cbt-nav-link">My Cart</a></li>
                <li><a href="#faq" class="cbt-nav-link">FAQ</a></li>
            </ul>

            <div class="cbt-nav-right">
                <a href="cart/index.html" class="cbt-nav-cart-btn" aria-label="Cart">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <span class="cbt-cart-counter" style="display: none;">0</span>
                </a>
                
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
                    <li role="none"><a href="index.html" class="cbt-drawer-link" role="menuitem">Home</a></li>
                    <li role="none"><a href="#categories" class="cbt-drawer-link" role="menuitem">Categories</a></li>
                    <li role="none"><a href="#all-products" class="cbt-drawer-link" role="menuitem">All Products</a></li>
                    <li role="none"><a href="cart/index.html" class="cbt-drawer-link" role="menuitem">My Cart</a></li>
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

            // Smooth scrolling for navigation links
            document.querySelectorAll('.cbt-nav-link, .cbt-drawer-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId.startsWith('#') && targetId.length > 1) {
                        e.preventDefault();
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            window.scrollTo({
                                top: targetElement.offsetTop - 80,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });

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
    <header class="cbt-store-hero">
        <div style="position: relative; z-index: 10;">
            <h1>Everything You Need to <br> <span class="highlight">Level Up Your Journey</span></h1>
            <p>From premium developer merchandise to high-quality digital assets and templates. Designed specifically for coders, creators, and tech enthusiasts.</p>
        
            <div class="cbt-store-search">
                <input type="text" id="storeSearch" placeholder="Search for T-Shirts, Presets, E-books...">
            </div>

            <div class="cbt-store-filters" id="storeFilters">
                <button class="cbt-filter-btn active" data-filter="all">All Products</button>
                <button class="cbt-filter-btn" data-filter="t-shirts">T-Shirts</button>
                <button class="cbt-filter-btn" data-filter="hoodies">Hoodies</button>
                <button class="cbt-filter-btn" data-filter="mugs">Mugs</button>
                <button class="cbt-filter-btn" data-filter="stickers">Stickers</button>
                <button class="cbt-filter-btn" data-filter="digital templates">Digital Templates</button>
                <button class="cbt-filter-btn" data-filter="presets">Presets</button>
                <button class="cbt-filter-btn" data-filter="e-books">E-books</button>
            </div>
        </div>
    </header>

    <!-- ===================== ALL PRODUCTS GRID ===================== -->
    <main class="cbt-store-container" id="all-products">
        <h2 class="cbt-section-title">All Products</h2>
        <div class="cbt-product-grid" id="productGrid">
            <!-- Products will be injected here by JS -->
        </div>
    </main>

    <!-- ===================== CUSTOMER REVIEWS ===================== -->
    <section class="cbt-store-container" style="margin-top:40px;">
        <h2 class="cbt-section-title">What Developers Say</h2>
        <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div style="flex:1; min-width:280px; background:rgba(22, 22, 22, 0.5); padding:25px; border-radius:12px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <div style="color:var(--primary); margin-bottom:15px;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"The Developer Mode T-Shirt is insanely comfortable. I literally wear it to every hackathon now!"</p>
                <h4 style="color:var(--text-heading);">- Rahul S.</h4>
            </div>
            <div style="flex:1; min-width:280px; background:rgba(22, 22, 22, 0.5); padding:25px; border-radius:12px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <div style="color:var(--primary); margin-bottom:15px;"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                <p style="color:var(--text-main); font-style:italic; margin-bottom:15px;">"The DSA Handbook is a game-changer. Crystal clear explanations that helped me crack my dream job."</p>
                <h4 style="color:var(--text-heading);">- Priya M.</h4>
            </div>
        </div>
    </section>

    <!-- ===================== FAQ ===================== -->
    <section class="cbt-store-container" id="faq">
        <h2 class="cbt-section-title">Frequently Asked Questions</h2>
        <div style="display:flex; flex-direction:column; gap:15px;">
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">How long does shipping take?</h3>
                <p style="color:var(--text-muted);">For physical products, standard shipping usually takes 5-7 business days across India.</p>
            </div>
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">How do I access digital products?</h3>
                <p style="color:var(--text-muted);">Digital products (templates, e-books, presets) are available for instant download immediately after purchase.</p>
            </div>
            <div style="background:rgba(22, 22, 22, 0.5); padding:20px; border-radius:8px; border:1px solid var(--border-color); backdrop-filter: blur(5px);">
                <h3 style="color:var(--text-heading); font-size:1.1rem; margin-bottom:10px;">What is your return policy?</h3>
                <p style="color:var(--text-muted);">We offer a 15-day return policy for unwashed and unworn apparel. Digital products are non-refundable.</p>
            </div>
        </div>
    </section>

    <!-- ===================== STAY UPDATED ===================== -->
    <section class="cbt-store-container">
        <div style="background:rgba(255, 196, 0, 0.15); padding:40px; border-radius:12px; text-align:center; border: 1px solid rgba(255,196,0,0.3); backdrop-filter: blur(10px);">
            <h2 style="color:var(--primary); font-size:2rem; margin-bottom:10px;">Stay Updated!</h2>
            <p style="color:#eee; margin-bottom:20px; font-weight:500;">Get the latest updates on new store drops, discount coupons, and free resources.</p>
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
                <p style="margin-top: 15px; color: var(--text-muted); line-height: 1.6;">Everything you need to level up your coding journey. From premium merchandise to high-quality digital assets.</p>
            </div>

            <!-- Quick Links -->
            <div class="cbt-ft-col">
                <h3><i class="fa-solid fa-link"></i> QUICK LINKS</h3>
                <ul class="cbt-ft-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="#all-products">Categories</a></li>
                    <li><a href="#all-products">All Products</a></li>
                    <li><a href="cart/index.html">My Cart</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="cbt-ft-col">
                <h3><i class="fa-solid fa-tags"></i> CATEGORIES</h3>
                <ul class="cbt-ft-links">
                    <li><a href="#all-products">Apparel (T-Shirts & Hoodies)</a></li>
                    <li><a href="#all-products">Desk Setup (Mugs & Stickers)</a></li>
                    <li><a href="#all-products">Digital Templates</a></li>
                    <li><a href="#all-products">Creator Assets (Presets)</a></li>
                    <li><a href="#all-products">E-books</a></li>
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

            <!-- Newsletter & Connect -->
            <div class="cbt-ft-col cbt-ft-newsletter">
                <h3><i class="fa-regular fa-paper-plane"></i> STAY UPDATED</h3>
                <p>Subscribe to get the latest updates on new drops and exclusive discounts.</p>
                <form class="cbt-ft-form cbt-course-subscribe-form">
                    <div class="cbt-ft-input-group">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" class="newsletter-email" name="email" placeholder="Enter your email" required aria-label="Email Address">
                    </div>
                    <button type="submit" class="cbt-ft-btn">
                        <span class="newsletter-btn-text">Subscribe</span> 
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

        <div class="cbt-copyright-strip" style="text-align: center; border-top: 1px solid var(--border-color); padding: 20px 0; margin-top: 20px; background: transparent !important;">
            <p>&copy; 2025 CodeByTushu. All rights reserved.</p>
        </div>
    </footer>

    <!-- JS files -->
    <script src="js/data.js"></script>
    <script src="js/store.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cart Counter
            function updateCartCount() {
                const countElem = document.getElementById('cart-count');
                if (countElem) {
                    const cart = JSON.parse(localStorage.getItem('cbt_cart')) || [];
                    let totalItems = 0;
                    cart.forEach(item => {
                        totalItems += (item.quantity || 1);
                    });
                    countElem.textContent = totalItems;
                    
                    if(totalItems > 0) {
                        countElem.style.display = 'flex';
                    } else {
                        countElem.style.display = 'none';
                    }
                }
            }
            updateCartCount();
            window.addEventListener('cartUpdated', updateCartCount);

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
