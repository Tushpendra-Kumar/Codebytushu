/**
 * back-home.js
 * Injects a "Back to Home" pill on every sub-page that includes this script.
 * The "Back to Top" circle button is now handled globally by /scroll-to-top.js
 * which is included on every page — no duplication needed here.
 *
 * SHARED UTILITY: Used by all sub-pages under /Leetcode/ and /video-editing/
 * Located at root (/back-home.js) so all sub-pages can load via absolute path.
 *
 * Usage: <script src="/back-home.js"></script> before </body>
 *
 * PHP MIGRATION NOTE:
 * This file stays at /back-home.js and is referenced in the shared PHP footer
 * include (e.g., includes/footer.php). No changes to the script content needed.
 * The "Back to Home" href='/' will work correctly in PHP routing as well.
 */
(function () {

    /* ---------- STYLES ---------- */
    const style = document.createElement('style');
    style.textContent = `
        /* Back to Home pill */
        .bth-home-btn {
            position: fixed;
            bottom: 90px;
            right: 24px;
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #f5a623;
            color: #000;
            font-family: 'Inter', 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 800;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 20px rgba(245,166,35,0.45);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }
        .bth-home-btn.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .bth-home-btn:hover {
            background: #ffc04a;
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(245,166,35,0.55);
        }
        .bth-home-btn .bth-arrow {
            font-size: 15px;
            line-height: 1;
        }

        @media (max-width: 480px) {
            .bth-home-btn { bottom: 82px; right: 14px; font-size: 12px; padding: 8px 14px; }
        }
    `;
    // Load Material Symbols font if not already loaded
    if (!document.querySelector('link[href*="Material+Symbols"]')) {
        var fontLink = document.createElement('link');
        fontLink.rel = 'stylesheet';
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0';
        document.head.appendChild(fontLink);
    }
    document.head.appendChild(style);

    /* ---------- BACK TO HOME BUTTON ---------- */
    const homeBtn = document.createElement('a');
    homeBtn.href = '/';
    homeBtn.className = 'bth-home-btn';
    homeBtn.setAttribute('aria-label', 'Back to Home');
    homeBtn.innerHTML = '<span class="bth-arrow"><span class="material-symbols-rounded" style="font-size:16px">home</span></span> Back to Home';
    document.body.appendChild(homeBtn);

    /* ---------- SHOW / HIDE ON SCROLL ---------- */
    window.addEventListener('scroll', function () {
        const scrolled = window.scrollY > 300;
        homeBtn.classList.toggle('visible', scrolled);
    });

    // NOTE: The "Back to Top" circle button was previously injected here.
    // It is now handled globally by /scroll-to-top.js (included on every page).
    // scroll-to-top.js has its own deduplication guard, so no double buttons appear.

})();
