// Landscape Cuisine Carousel + Toggle
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('cuisineCarousel');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    const dotsContainer = document.querySelector('.carousel-dots');
    const cards = document.querySelectorAll('.cuisine-landscape-card');
    
    let currentIndex = 0;
    const cardWidth = 400; // cuisine-landscape-card flex:0 0 380px + gap 1rem=400px
    
    if (carousel && cards.length > 0) {
        // Create dots
        cards.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (index === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(index));
            dotsContainer.appendChild(dot);
        });
        
        function updateCarousel() {
            carousel.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
            document.querySelectorAll('.dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
        }
        
        prevBtn.addEventListener('click', () => {
            currentIndex = currentIndex > 0 ? currentIndex - 1 : cards.length - 1;
            updateCarousel();
        });
        
        nextBtn.addEventListener('click', () => {
            currentIndex = currentIndex < cards.length - 1 ? currentIndex + 1 : 0;
            updateCarousel();
        });
        
        // Auto-advance every 5s
        setInterval(() => {
            currentIndex =currentIndex < cards.length - 1 ? currentIndex + 1 : 0;
            updateCarousel();
        }, 5000);
    }
    
/* toggleLandscapeItems removed - use universal handler from explore-full-page.js */

    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (document.querySelector('.sidebar-content[data-content="local-cuisine"].active')) {
            if (e.key === 'ArrowLeft') prevBtn.click();
            if (e.key === 'ArrowRight') nextBtn.click();
        }
    });
});
