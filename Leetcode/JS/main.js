const navbar = document.querySelector(".navbar");


// ─── Dynamic Hero Margin ──────────────────────────────────────────────────────
// Precisely sets hero-section margin-top so the initial page load position
// is identical to the position after clicking "Home" in the nav.
// Formula: navbar CSS top-offset (20px) + actual rendered navbar height + gap (20px)

function syncHeroMargin() {
    const heroSection = document.querySelector(".hero-section");
    if (!heroSection || !navbar) return;

    const navbarCSSTop = parseInt(window.getComputedStyle(navbar).top) || 20;
    const gap          = 20; // breathing room below navbar in px
    const exactMargin  = navbarCSSTop + navbar.offsetHeight + gap;

    heroSection.style.marginTop = exactMargin + "px";
}

// Run on load and on resize (in case navbar height changes at breakpoints)
document.addEventListener("DOMContentLoaded", syncHeroMargin);
window.addEventListener("resize", syncHeroMargin);


// ─── Sticky Navbar ────────────────────────────────────────────────────────────

window.addEventListener("scroll", () => {

    if(window.scrollY > 50){

        navbar.classList.add("sticky");

    }else{

        navbar.classList.remove("sticky");

    }

});


// ─── Smooth Anchor Scroll with Offset ────────────────────────────────────────
// Uses the same formula as syncHeroMargin so clicking a nav link produces
// the exact same scroll position as the initial page load.

const navLinks = document.querySelectorAll('nav ul li a');

navLinks.forEach(link => {

    link.addEventListener("click", function(e){

        const targetId = this.getAttribute("href");

        if(targetId && targetId.startsWith("#")){

            e.preventDefault();

            const targetSection = document.querySelector(targetId);

            if(targetSection){

                // Recalculate live (handles resize between page load and click)
                const navbarCSSTop = parseInt(window.getComputedStyle(navbar).top) || 20;
                const gap          = 20;
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