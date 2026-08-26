// Smooth scrolling for navigation links and buttons
document.querySelectorAll('.smooth-scroll, a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Hide images that fail to load to remove broken picture icons
    document.querySelectorAll('img').forEach(img => {
        if (!img.getAttribute('src')) {
            img.style.display = 'none';
            return;
        }
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });

    // Add confirmation popup for contact email link
    const contactEmailLink = document.querySelector('.contact-row a[href^="mailto:"]');
    if (contactEmailLink) {
        contactEmailLink.addEventListener('click', function(e) {
            e.preventDefault();
            const proceed = window.confirm('Open an email app to send a message?');
            if (proceed) {
                window.location.href = this.href;
            }
        });
    }
});