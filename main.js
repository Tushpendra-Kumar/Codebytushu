$(document).ready(function(){

    // ─── Cache DOM selectors once (not on every scroll event) ──────────────
    var $navbar   = $('.navbar');
    var $scrollBtn = $('.scroll-up-btn, .cbt-back-to-top');
    var ticking   = false;

    // ─── Throttled scroll via requestAnimationFrame ─────────────────────────
    $(window).on('scroll.cbt', function(){
        if (!ticking) {
            window.requestAnimationFrame(function(){
                var scrollY = window.pageYOffset;

                // sticky navbar
                if (scrollY > 20) {
                    $navbar.addClass('sticky');
                } else {
                    $navbar.removeClass('sticky');
                }

                // scroll-up button
                if (scrollY > 300) {
                    $scrollBtn.addClass('show');
                } else {
                    $scrollBtn.removeClass('show');
                }

                ticking = false;
            });
            ticking = true;
        }
    });

    // slide-up script
    $('.scroll-up-btn').click(function(){
        $('html').animate({scrollTop: 0});
        // removing smooth scroll on slide-up button click
        $('html').css("scrollBehavior", "auto");
    });

    $('.navbar .menu li a').click(function(){
        // applying again smooth scroll on menu items click
        $('html').css("scrollBehavior", "smooth");
    });

    // toggle menu/navbar script
    $('.menu-btn').click(function(){
        $('.navbar .menu').toggleClass("active");
        $('.menu-btn i').toggleClass("active");
    });


});

// =============================================================================
// PHP MIGRATION NOTE (main.js):
// This file handles all jQuery-based interactions for the main portfolio page
// (index.html → future index.php). No server-side changes needed in this file.
// Only the HTML data source changes — this JS stays identical.
// =============================================================================