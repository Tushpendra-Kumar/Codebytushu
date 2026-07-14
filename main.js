$(document).ready(function(){
    $(window).scroll(function(){
        // sticky navbar on scroll script
        if(this.scrollY > 20){
            $('.navbar').addClass("sticky");
        }else{
            $('.navbar').removeClass("sticky");
        }
        
        // scroll-up button show/hide script
        if(this.scrollY > 300){
            $('.scroll-up-btn, .cbt-back-to-top').addClass("show");
        }else{
            $('.scroll-up-btn, .cbt-back-to-top').removeClass("show");
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

    // typing text animation script
    // PHP MIGRATION NOTE: These strings can be fetched from a `site_config`
    // MySQL table (key: 'hero_typing_strings', value: JSON array).
    if (document.querySelector(".typing")) {
        var typed = new Typed(".typing", {
            strings: ["Learn Coding", "Watch Tutorials", "Explore Projects", "Code for Free"],
            typeSpeed: 100,
            backSpeed: 60,
            loop: true
        });
    }


});

// =============================================================================
// PHP MIGRATION NOTE (main.js):
// This file handles all jQuery-based interactions for the main portfolio page
// (index.html → future index.php). No server-side changes needed in this file.
// Only the HTML data source changes — this JS stays identical.
// =============================================================================