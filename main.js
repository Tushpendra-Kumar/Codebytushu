$(document).ready(function(){
    $(window).scroll(function(){
        // sticky navbar on scroll script
        if(this.scrollY > 20){
            $('.navbar').addClass("sticky");
        }else{
            $('.navbar').removeClass("sticky");
        }
        
        // scroll-up button show/hide script
        if(this.scrollY > 500){
            $('.scroll-up-btn').addClass("show");
        }else{
            $('.scroll-up-btn').removeClass("show");
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
    var typed = new Typed(".typing", {
        strings: ["Learn Coding", "Watch Tutorials", "Explore Projects", "Code for Free"],
        typeSpeed: 100,
        backSpeed: 60,
        loop: true
    });

    // owl carousel script — initialized once inside document.ready (correct placement)
    // PHP MIGRATION NOTE: The project cards rendered in the carousel (index.html)
    // should be generated via a PHP loop from a `projects` MySQL table.
    // Each card will then be a PHP echo instead of hardcoded HTML.
    $('.carousel').owlCarousel({
        margin: 20,
        loop: true,
        autoplay: true,
        autoplayTimeOut: 2000,
        autoplayHoverPause: true,
        responsive: {
            0:{
                items: 1,
                nav: false
            },
            600:{
                items: 2,
                nav: false
            },
            1000:{
                items: 3,
                nav: false
            }
        }
    });
});

// =============================================================================
// PHP MIGRATION NOTE (main.js):
// This file handles all jQuery-based interactions for the main portfolio page
// (index.html → future index.php). No server-side changes needed in this file.
// Only the HTML data source changes — this JS stays identical.
// =============================================================================