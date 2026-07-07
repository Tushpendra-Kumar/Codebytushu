const navbar = document.querySelector(".navbar");

// ─── Dynamic Hero Margin ──────────────────────────────────────────────────────
// Precisely sets hero-section margin-top so the initial page load position
// is identical to the position after clicking "Home" in the nav.
// Formula: navbar CSS top-offset + actual rendered navbar height + gap

function syncHeroMargin() {
    const heroSection = document.querySelector(".hero-section");
    if (!heroSection || !navbar) return;

    const navbarCSSTop = parseInt(window.getComputedStyle(navbar).top) || 0;
    const gap          = 20; // breathing room below navbar in px
    const exactMargin  = navbarCSSTop + navbar.offsetHeight + gap;

    heroSection.style.marginTop = exactMargin + "px";
}

// Run on load and on resize (in case navbar height changes at breakpoints)
document.addEventListener("DOMContentLoaded", syncHeroMargin);
window.addEventListener("resize", syncHeroMargin);


// ─── Smooth Anchor Scroll with Offset ────────────────────────────────────────
// Uses the same formula as syncHeroMargin so clicking a nav link produces
// the exact same scroll position as the initial page load.

const navLinks = document.querySelectorAll('.cbt-nav-link, .cbt-drawer-link');

navLinks.forEach(link => {
    link.addEventListener("click", function(e){
        const targetId = this.getAttribute("href");
        if(targetId && targetId.startsWith("#")){
            e.preventDefault();
            const targetSection = document.querySelector(targetId);
            if(targetSection){
                const navbarCSSTop = parseInt(window.getComputedStyle(navbar).top) || 0;
                const gap          = 0;
                const navOffset    = navbarCSSTop + navbar.offsetHeight + gap;
                const sectionTop = Math.max(0, targetSection.offsetTop - navOffset);
                window.scrollTo({
                    top: sectionTop,
                    behavior: "smooth"
                });
            }
        }
    });
});

// ─── Scrollspy for Active Navbar State ───────────────────────────────────────
// Automatically updates the active navigation link based on scroll position

const sections = document.querySelectorAll("section[id]");

function updateActiveNav() {
    let current = "";
    
    sections.forEach((section) => {
        const sectionTop = section.offsetTop;
        const navOffset = navbar ? navbar.offsetHeight + 100 : 150; 
        
        if (window.scrollY >= sectionTop - navOffset) {
            current = section.getAttribute("id");
        }
    });

    if (current) {
        navLinks.forEach((link) => {
            link.classList.remove("active");
            if (link.getAttribute("href") === `#${current}`) {
                link.classList.add("active");
            }
        });
    }
}

window.addEventListener("scroll", updateActiveNav);
document.addEventListener("DOMContentLoaded", updateActiveNav);