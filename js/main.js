/* ================= Mobile Menu Toggle ================= */
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('navLinks');

hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});

// Close menu when a link is clicked
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
    });
});

/* ================= Auto Year in Footer ================= */
document.getElementById('year').textContent = new Date().getFullYear();

/* ================= Smooth Scroll for Anchor Links ================= */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId.length > 1) {
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });
});

/* ================= Navbar shadow on scroll ================= */
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    if (window.scrollY > 20) {
        navbar.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
    } else {
        navbar.style.boxShadow = '0 2px 12px rgba(0,0,0,0.04)';
    }
});