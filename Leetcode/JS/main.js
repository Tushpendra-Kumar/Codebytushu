const navbar = document.querySelector(".navbar");


// Sticky Navbar

window.addEventListener("scroll", () => {

    if(window.scrollY > 50){

        navbar.classList.add("sticky");

    }else{

        navbar.classList.remove("sticky");

    }

});


// Smooth Anchor Scroll with Offset

const navLinks = document.querySelectorAll('nav ul li a');

navLinks.forEach(link => {

    link.addEventListener("click", function(e){

        const targetId = this.getAttribute("href");

        if(targetId.startsWith("#")){

            e.preventDefault();

            const targetSection = document.querySelector(targetId);

            if(targetSection){

                const navbarHeight = navbar.offsetHeight + 40;

                const sectionTop =
                    targetSection.offsetTop - navbarHeight;

                window.scrollTo({

                    top: sectionTop,

                    behavior: "smooth"

                });

            }

        }

    });

});