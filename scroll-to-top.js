/**
 * scroll-to-top.js  — Global Scroll-to-Top Button
 * ══════════════════════════════════════════════════════════════════════════
 * PURPOSE :  Inject a premium "scroll to top" circular button on EVERY page
 *            that includes this script. Fully self-contained & dedup-safe.
 *
 * BEHAVIOUR:
 *   • Appears after user scrolls 350 px down (fade + slide-up animation).
 *   • Click → smooth scroll to TOP of the CURRENT page (never redirects).
 *   • Hidden automatically when user is already near the top.
 *   • Throttled scroll listener for zero-lag performance.
 *   • Works on Desktop · Tablet · Mobile.
 *   • Respects light / dark theme (CSS variable-aware).
 *
 * USAGE:
 *   Add once, before </body>, on any page:
 *   <script src="/scroll-to-top.js"></script>
 *
 *   For pages deep in subdirectories use the absolute path:
 *   <script src="/scroll-to-top.js"></script>
 *
 * DEDUP GUARD:
 *   If multiple scripts accidentally load this file twice, only one button
 *   is created (checked via data attribute on <body>).
 * ══════════════════════════════════════════════════════════════════════════
 */

(function () {
    'use strict';

    /* ── 0. Deduplication Guard ──────────────────────────────────────────── */
    if (document.body.dataset.sttInit) return;
    document.body.dataset.sttInit = '1';

    /* ── 1. Inject CSS (once) ────────────────────────────────────────────── */
    var styleId = 'cbt-stt-styles';
    if (!document.getElementById(styleId)) {
        var style = document.createElement('style');
        style.id = styleId;
        style.textContent = [
            /* === Base button === */
            '.cbt-stt-btn {',
            '    position: fixed;',
            '    bottom: 28px;',
            '    left: 28px;',
            '    z-index: 999999;',
            '    width: 50px;',
            '    height: 50px;',
            '    border-radius: 50%;',
            '    border: 2.5px solid #ffc400;',
            '    background: #111111;',
            '    color: #ffc400;',
            '    font-size: 22px;',
            '    cursor: pointer;',
            '    display: flex;',
            '    align-items: center;',
            '    justify-content: center;',
            '    box-shadow: 0 4px 20px rgba(255,196,0,0.25), 0 2px 8px rgba(0,0,0,0.4);',
            '    transition: opacity 0.35s ease, transform 0.35s cubic-bezier(0.34,1.56,0.64,1), background 0.25s ease, box-shadow 0.25s ease;',
            '    opacity: 0;',
            '    transform: translateY(18px) scale(0.88);',
            '    pointer-events: none;',
            '    -webkit-tap-highlight-color: transparent;',
            '    outline: none;',
            '}',

            /* === Visible state === */
            '.cbt-stt-btn.cbt-stt-visible {',
            '    opacity: 1;',
            '    transform: translateY(0) scale(1);',
            '    pointer-events: auto;',
            '}',

            /* === Hover === */
            '.cbt-stt-btn:hover {',
            '    background: #ffc400;',
            '    color: #111111;',
            '    transform: translateY(-5px) scale(1.05);',
            '    box-shadow: 0 10px 32px rgba(255,196,0,0.5), 0 4px 12px rgba(0,0,0,0.3);',
            '}',

            /* === Active / tap === */
            '.cbt-stt-btn:active {',
            '    transform: translateY(0) scale(0.94);',
            '    transition-duration: 0.1s;',
            '}',

            /* === Focus-visible (keyboard accessibility) === */
            '.cbt-stt-btn:focus-visible {',
            '    outline: 3px solid #ffc400;',
            '    outline-offset: 3px;',
            '}',

            /* === Light-mode override (matches site theme toggle) === */
            '[data-theme="light"] .cbt-stt-btn {',
            '    background: #ffffff;',
            '    border-color: #d4900a;',
            '    color: #d4900a;',
            '    box-shadow: 0 4px 20px rgba(212,144,10,0.2), 0 2px 8px rgba(0,0,0,0.15);',
            '}',
            '[data-theme="light"] .cbt-stt-btn:hover {',
            '    background: #d4900a;',
            '    color: #ffffff;',
            '}',

            /* === Icon sizing === */
            '.cbt-stt-btn .material-symbols-rounded {',
            '    font-size: 22px;',
            '    line-height: 1;',
            '    display: block;',
            '}',

            /* === Mobile adjustments === */
            '@media (max-width: 480px) {',
            '    .cbt-stt-btn { bottom: 18px; left: 18px; width: 44px; height: 44px; }',
            '    .cbt-stt-btn .material-symbols-rounded { font-size: 20px; }',
            '}',

            /* === Conflict: hide any OLD inline scroll buttons on pages we clean up === */
            '.main-top-btn-legacy-hidden { display: none !important; }'
        ].join('\n');
        document.head.appendChild(style);
    }

    /* ── 2. Load Material Symbols font if needed ─────────────────────────── */
    if (!document.querySelector('link[href*="Material+Symbols"]')) {
        var fontLink = document.createElement('link');
        fontLink.rel = 'stylesheet';
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0';
        document.head.appendChild(fontLink);
    }

    /* ── 3. Create the button ────────────────────────────────────────────── */
    var btn = document.createElement('button');
    btn.className = 'cbt-stt-btn';
    btn.setAttribute('aria-label', 'Scroll to top');
    btn.setAttribute('title', 'Scroll to top');
    btn.setAttribute('type', 'button');
    btn.innerHTML = '<span class="material-symbols-rounded" aria-hidden="true">arrow_upward</span>';
    document.body.appendChild(btn);

    /* ── 4. Click → smooth scroll to top of CURRENT page ────────────────── */
    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ── 5. Throttled scroll listener ───────────────────────────────────── */
    var SCROLL_THRESHOLD = 350;   // px — show button after this scroll depth
    var ticking = false;

    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                var shouldShow = window.pageYOffset > SCROLL_THRESHOLD;
                btn.classList.toggle('cbt-stt-visible', shouldShow);
                ticking = false;
            });
            ticking = true;
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });

    /* ── 6. Check immediately in case page loads mid-scroll ─────────────── */
    onScroll();

})();
