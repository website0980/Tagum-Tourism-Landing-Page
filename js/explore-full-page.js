// Get all tab buttons
const tabBtns = document.querySelectorAll('.tab-btn');
const sectionContents = document.querySelectorAll('.section-content');

// Add click event to each tab button
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const section = btn.getAttribute('data-section');
        showSection(section);
    });
});

function showSection(sectionId) {
    // Hide all sections
    sectionContents.forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tabs
    tabBtns.forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.add('active');
    }

    // Activate corresponding tab
    const activeTab = document.querySelector(`[data-section="${sectionId}"]`);
    if (activeTab) {
        activeTab.classList.add('active');
    }

    // Scroll to top smoothly
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Get section from URL parameter on page load - Force show correct section
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section') || 'events';
    setTimeout(() => showSection(section), 100);
    initEventsByMonth();
});

function initEventsByMonth() {
    const monthGroups = document.querySelectorAll('.month-group');
    if (!monthGroups.length) return;

    monthGroups.forEach((group) => {
        const header = group.querySelector('.month-header');
        const panel = group.querySelector('.month-events');
        if (!header || !panel) return;

        header.addEventListener('click', () => {
            const isOpen = group.classList.contains('is-open');

            monthGroups.forEach((otherGroup) => {
                otherGroup.classList.remove('is-open');
                const otherHeader = otherGroup.querySelector('.month-header');
                const otherPanel = otherGroup.querySelector('.month-events');
                if (otherHeader) otherHeader.setAttribute('aria-expanded', 'false');
                if (otherPanel) otherPanel.hidden = true;
            });

            if (!isOpen) {
                group.classList.add('is-open');
                header.setAttribute('aria-expanded', 'true');
                panel.hidden = false;
            }
        });
    });
}

// Smooth scroll for breadcrumb and navigation links
document.querySelectorAll('a[href*="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (!href.includes('..')) {
            e.preventDefault();
        }
    });
});

// Cuisine toggle functionality
// Removed conflicting DOMContentLoaded toggle - using single universal handler only


/* Redundant toggle listeners removed - universal handler only */


// Universal Smooth Toggle Handler - CSS class + dynamic maxHeight
document.addEventListener('click', (e) => {
  if (e.target.matches('.toggle-items-btn')) {
    e.preventDefault();
    e.stopPropagation();
    const button = e.target;
    const list = button.nextElementSibling; // Direct ul/div after button
    if (!list || !list.matches('.toggle-list, .items-list, .landscape-items')) return;

    const isOpen = list.classList.contains('show');
    const isCuisine = button.closest('#local-cuisine, [data-content="local-cuisine"]') !== null;
    
    if (isOpen) {
      // Collapse smooth
      list.style.maxHeight = list.scrollHeight + 'px';
      requestAnimationFrame(() => {
        list.style.maxHeight = '0px';
        list.classList.remove('show');
        button.textContent = isCuisine ? 'View Dishes' : 'View Details';
      });
    } else {

      // Expand smooth
      list.classList.add('show');
      requestAnimationFrame(() => {
        list.style.maxHeight = list.scrollHeight + 'px';
      });
      button.textContent = isCuisine ? 'Hide Dishes' : 'Hide Details';
    }
  }
}, true);



// Cuisine Landscape Carousel
function initCuisineCarousel() {
    const carouselContainer = document.querySelector('.cuisine-landscape-carousel');
    if (!carouselContainer) return;

    const track = carouselContainer.querySelector('.carousel-track');
    const slides = track.querySelectorAll('.carousel-slide');
    const prevBtn = carouselContainer.querySelector('.carousel-prev');
    const nextBtn = carouselContainer.querySelector('.carousel-next');
    const dotsContainer = carouselContainer.querySelector('.carousel-dots');
    
    let currentIndex = 0;
    const totalSlides = slides.length;
    
    // Create dots
    dotsContainer.innerHTML = '';
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('button');
        dot.classList.add('carousel-dot');
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(i));
        dotsContainer.appendChild(dot);
    }
    
    const dots = dotsContainer.querySelectorAll('.carousel-dot');
    
    function updateCarousel() {
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }
    
    function goToSlide(index) {
        currentIndex = index;
        updateCarousel();
    }
    
    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateCarousel();
    }
    
    function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }
    
    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);
    
    // Auto play
    let autoPlay = setInterval(nextSlide, 5000);
    
    // Pause on hover
    carouselContainer.addEventListener('mouseenter', () => clearInterval(autoPlay));
    carouselContainer.addEventListener('mouseleave', () => {
        autoPlay = setInterval(nextSlide, 5000);
    });
    
    // Touch swipe for mobile
    let startX = 0;
    carouselContainer.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    });
    
    carouselContainer.addEventListener('touchend', (e) => {
        const endX = e.changedTouches[0].clientX;
        const diffX = startX - endX;
        if (Math.abs(diffX) > 50) {
            if (diffX > 0) nextSlide();
            else prevSlide();
        }
    });
}

// Initialize carousel when local-cuisine section is shown
const cuisineObserver = new MutationObserver(() => {
    if (document.querySelector('#local-cuisine.active')) {
        initCuisineCarousel();
        cuisineObserver.disconnect();
    }
});

cuisineObserver.observe(document.body, { childList: true, subtree: true });
