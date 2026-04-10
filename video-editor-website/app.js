// ====================== NAV TOGGLE ======================
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
const scrollTopBtn = document.getElementById("scrollTopBtn");

window.addEventListener("scroll", () => {
  if (window.scrollY > 600) scrollTopBtn.classList.add("show");
  else scrollTopBtn.classList.remove("show");
});

scrollTopBtn.addEventListener("click", () => {
  window.scrollTo({ top: 0, behavior: "smooth" });
});

// ====================== CONTACT FORM (DEMO) ======================
const form = document.getElementById("contactForm");
const note = document.getElementById("formNote");

form.addEventListener("submit", (e) => {
  e.preventDefault();
  note.textContent = "✅ Message sent successfully! (Demo)";
  form.reset();

  setTimeout(() => {
    note.textContent = "";
  }, 3000);
});
