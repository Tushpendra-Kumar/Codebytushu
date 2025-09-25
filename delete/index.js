// Set year in footer
document.getElementById('year').textContent = new Date().getFullYear();

// Smooth scroll for nav links
document.querySelectorAll('.main-nav-links a').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const targetId = link.getAttribute('href');
    document.querySelector(targetId).scrollIntoView({
      behavior: 'smooth'
    });
  });
});

// Horizontal scroll on mouse wheel for projects
const projectsContainer = document.querySelector('.projects-container');
projectsContainer.addEventListener('wheel', (e) => {
  e.preventDefault();
  projectsContainer.scrollLeft += e.deltaY;
});

// Contact form simple demo
document.getElementById('contactForm').addEventListener('submit', e => {
  e.preventDefault();
  const formStatus = document.getElementById('form-status');
  formStatus.textContent = "Thanks! Your message has been sent (demo).";
  e.target.reset();
});