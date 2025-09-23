// Set year in footer
document.getElementById('year').textContent = new Date().getFullYear();

// Smooth scroll for nav links
document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    document.querySelector(link.getAttribute('href')).scrollIntoView({
      behavior: 'smooth'
    });
  });
});

// Contact form simple demo
document.getElementById('contactForm').addEventListener('submit', e=>{
  e.preventDefault();
  document.getElementById('form-status').textContent = "✅ Thanks! Your message has been sent (demo).";
  e.target.reset();
});
