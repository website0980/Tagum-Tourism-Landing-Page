// Get sidebar elements
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const learnMoreBtns = document.querySelectorAll('.learn-more-btn');
const closeSidebarBtn = document.getElementById('closeSidebar');

// Check for URL parameter to open specific section
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section) {
        openSidebar(section);
    }
});

// Open sidebar with content
learnMoreBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const cardType = btn.getAttribute('data-card');
        openSidebar(cardType);
    });
});

// Close sidebar button
closeSidebarBtn.addEventListener('click', closeSidebar);

// Close sidebar when clicking overlay
sidebarOverlay.addEventListener('click', closeSidebar);

function openSidebar(cardType) {
    // Hide all content
    const allContent = document.querySelectorAll('.sidebar-content');
    allContent.forEach(content => {
        content.classList.remove('active');
    });

    // Show selected content
    const selectedContent = document.querySelector(`.sidebar-content[data-content="${cardType}"]`);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }

    // Show sidebar and overlay
    sidebar.classList.add('active');
    sidebarOverlay.classList.add('active');

    // Scroll to top
    sidebar.scrollTop = 0;

    // Prevent body scroll when sidebar is open
    document.body.style.overflow = 'hidden';
}

// Hide images that fail to load to remove broken picture icons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('img').forEach(img => {
        if (!img.getAttribute('src')) {
            img.style.display = 'none';
            return;
        }
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });
});

function closeSidebar() {
    // Hide sidebar and overlay
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');

    // Re-enable body scroll
    document.body.style.overflow = 'auto';
}

// Close sidebar with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('active')) {
        closeSidebar();
    }
});
