// =============================================================================
// app.js — Video Editor Website (video-editor-website/)
// PHP MIGRATION NOTE: This file handles all client-side interactions.
// When converting to PHP, this file stays 100% identical — only the
// HTML data source (index.html → index.php) changes.
// EmailJS will be replaceable with PHP mail() or PHPMailer in the future.
// =============================================================================

// ====================== NAV TOGGLE ======================
// PHP MIGRATION NOTE: The hamburger nav toggle below is pure client-side.
// No changes needed for PHP migration.
const hamburger = document.getElementById("hamburger");
const nav = document.getElementById("nav");

hamburger.addEventListener("click", () => {
  nav.classList.toggle("show");
});

// Close menu on link click (mobile)
document.querySelectorAll(".nav-link").forEach((link) => {
  link.addEventListener("click", () => {
    nav.classList.remove("show");
  });
});

// ====================== REVEAL ANIMATION ======================
// PHP MIGRATION NOTE: IntersectionObserver scroll reveal — pure client-side, no changes needed.
const revealElements = document.querySelectorAll(".reveal");

const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) entry.target.classList.add("show");
    });
  },
  { threshold: 0.12 }
);

revealElements.forEach((el) => revealObserver.observe(el));

// ====================== SCROLL TOP ======================
// NOTE: The scroll-to-top button is injected by /back-home.js on this page.
// The scrollTopBtn element does not exist in the HTML — back-home.js
// creates it dynamically. This block is kept with a null guard for safety.
// PHP MIGRATION NOTE: No changes needed here for PHP migration.
const scrollTopBtn = document.getElementById("scrollTopBtn");

if (scrollTopBtn) {
  window.addEventListener("scroll", () => {
    if (window.scrollY > 600) scrollTopBtn.classList.add("show");
    else scrollTopBtn.classList.remove("show");
  });

  scrollTopBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

// ====================== SAFE CONTACT FORM ======================
// PHP MIGRATION NOTE: EmailJS is the current no-backend solution.
// In the PHP version, this form will POST to a PHP handler (contact.php)
// that uses PHPMailer or PHP mail(). The emailjs.init(), emailjs.sendForm()
// calls below will be replaced by a fetch() call to contact.php.
// EmailJS service_id: "service_2n2zsep", template_id: "template_c15b3pa"

document.addEventListener("DOMContentLoaded", function () {

  emailjs.init("UtmoyVGBgxwM4qves");

  const form = document.getElementById("contactForm");
  const note = document.getElementById("formNote");

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      note.textContent = "⏳ Sending message...";

      emailjs.sendForm(
        "service_2n2zsep",
        "template_c15b3pa",
        this
      )
      .then(() => {
        note.textContent = "✅ Message sent successfully!!";
        form.reset();

        setTimeout(() => {
          note.textContent = "";
        }, 4000);
      })
      .catch((error) => {
        note.textContent = "❌ Failed to send message!";
        console.error("EmailJS Error:", error);
      });
    });
  }

});