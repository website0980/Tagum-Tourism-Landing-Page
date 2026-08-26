// Single experience details page enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top on load
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

    // Ensure main content is visible
    const mainContent = document.querySelector('.experience-single, .experience-detail, article');
    if (mainContent) {
        mainContent.style.display = 'block';
        mainContent.classList.add('active');
    }

    // Handle image errors with logging
    document.querySelectorAll('img').forEach(img => {
        if (!img.getAttribute('src')) {
            img.style.display = 'none';
            return;
        }
        img.addEventListener('error', function() {
            console.warn('Experience image failed to load:', this.src);
            this.style.display = 'none';
        });
        img.addEventListener('load', function() {
            console.log('Experience image loaded:', this.src);
        });
    });

    // Smooth scroll for internal links
    document.querySelectorAll('a[href^=\"#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
