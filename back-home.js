/**
 * back-home.js
 * Injects a "Back to Home" pill + "Back to Top" circle button
 * on every sub-page that includes this script.
 *
 * Usage: <script src="/back-home.js"></script> before </body>
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

        /* Back to Top circle */
        .bth-top-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #111;
            border: 2px solid #f5a623;
            color: #f5a623;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 18px rgba(0,0,0,0.4);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }
        .bth-top-btn.visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .bth-top-btn:hover {
            background: #f5a623;
            color: #000;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(245,166,35,0.5);
        }

        /* Light mode overrides */
        [data-theme="light"] .bth-top-btn {
            background: #fff;
            border-color: #d4900a;
            color: #d4900a;
        }
        [data-theme="light"] .bth-top-btn:hover {
            background: #d4900a;
            color: #fff;
        }

        @media (max-width: 480px) {
            .bth-home-btn { bottom: 82px; right: 14px; font-size: 12px; padding: 8px 14px; }
            .bth-top-btn  { bottom: 14px; right: 14px; width: 40px; height: 40px; font-size: 17px; }
        }
    `;
    document.head.appendChild(style);

    /* ---------- BACK TO HOME BUTTON ---------- */
    const homeBtn = document.createElement('a');
    homeBtn.href = '/';
    homeBtn.className = 'bth-home-btn';
    homeBtn.setAttribute('aria-label', 'Back to Home');
    homeBtn.innerHTML = '<span class="bth-arrow">🏠</span> Back to Home';
    document.body.appendChild(homeBtn);

    /* ---------- BACK TO TOP BUTTON ---------- */
    const topBtn = document.createElement('button');
    topBtn.className = 'bth-top-btn';
    topBtn.setAttribute('aria-label', 'Back to top');
    topBtn.innerHTML = '↑';
    document.body.appendChild(topBtn);

    topBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ---------- SHOW / HIDE ON SCROLL ---------- */
    window.addEventListener('scroll', function () {
        const scrolled = window.scrollY > 300;
        homeBtn.classList.toggle('visible', scrolled);
        topBtn.classList.toggle('visible', scrolled);
    });

})();
