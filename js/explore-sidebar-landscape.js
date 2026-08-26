// Unified Sidebar Landscape Carousel Logic
(function() {
    'use strict';
    
    class SidebarCarousel {
        constructor(containerSelector) {
            this.container = document.querySelector(containerSelector);
            if (!this.container) return;
            
            this.carousel = this.container.querySelector('.sidebar-carousel');
            this.prevBtn = this.container.querySelector('.sidebar-carousel-prev');
            this.nextBtn = this.container.querySelector('.sidebar-carousel-next');
            this.dotsContainer = this.container.querySelector('.sidebar-carousel-dots');
            this.cards = this.container.querySelectorAll('.sidebar-landscape-card');
            
            this.currentIndex = 0;
            this.cardWidth = 440; // sidebar-landscape-card 420px + gap 1.5rem
            this.init();
        }
        
        init() {
            if (this.cards.length === 0) return;
            
            this.createDots();
            this.updateCarousel();
            this.bindEvents();
            this.startAutoAdvance();
        }
        
        createDots() {
            this.cards.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('sidebar-dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => this.goToSlide(index));
                this.dotsContainer.appendChild(dot);
            });
        }
        
        updateCarousel() {
            this.carousel.style.transform = `translateX(-${this.currentIndex * this.cardWidth}px)`;
            this.updateDots();
        }
        
        updateDots() {
            this.dotsContainer.querySelectorAll('.sidebar-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === this.currentIndex);
            });
        }
        
        bindEvents() {
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.prevSlide());
            }
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.nextSlide());
            }
        }
        
        prevSlide() {
            this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.cards.length - 1;
            this.updateCarousel();
        }
        
        nextSlide() {
            this.currentIndex = this.currentIndex < this.cards.length - 1 ? this.currentIndex + 1 : 0;
            this.updateCarousel();
        }
        
        goToSlide(index) {
            this.currentIndex = index;
            this.updateCarousel();
        }
        
        startAutoAdvance() {
            setInterval(() => {
                this.nextSlide();
            }, 6000);
        }
    }
    
    // Initialize all sidebar carousels when sidebar opens
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new MutationObserver(() => {
            document.querySelectorAll('.sidebar-content.active .sidebar-landscape').forEach(container => {
                new SidebarCarousel(container);
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
    
    // Smooth details toggle for all sidebar landscape cards
    window.toggleSidebarDetails = function(btn) {
        const itemsList = btn.nextElementSibling;
        if (!itemsList || !itemsList.classList.contains('sidebar-details-list')) return;
        
        if (itemsList.classList.contains('show')) {
            btn.textContent = 'View Details';
            itemsList.classList.remove('show');
        } else {
            itemsList.classList.add('show');
            const height = itemsList.scrollHeight + 20; // padding buffer
            itemsList.style.maxHeight = height + 'px';
            btn.textContent = 'Hide Details';
        }
    };
    
    // Global keyboard nav for sidebar
    document.addEventListener('keydown', (e) => {
        const activeSidebar = document.querySelector('.sidebar.active');
        if (activeSidebar) {
            if (e.key === 'ArrowLeft') {
                activeSidebar.querySelector('.sidebar-carousel-prev')?.click();
            }
            if (e.key === 'ArrowRight') {
                activeSidebar.querySelector('.sidebar-carousel-next')?.click();
            }
        }
    });
})();
