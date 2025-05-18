/**
 * Modern Testimonial Carousel
 * A responsive, accessible testimonial carousel with smooth transitions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get carousel elements
    const carousel = document.getElementById('testimonialModernCarousel');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.testimonial-modern-slide');
    const dots = document.querySelectorAll('.testimonial-modern-dot');
    const prevButton = carousel.querySelector('.testimonial-modern-arrow.prev');
    const nextButton = carousel.querySelector('.testimonial-modern-arrow.next');
    
    // Set initial state
    let currentIndex = 0;
    let interval;
    const autoplayDelay = 5000; // 5 seconds between slides
    let isTransitioning = false;
    
    // Initialize the carousel
    function initCarousel() {
        // Show the first slide
        showSlide(currentIndex);
        
        // Start autoplay
        startAutoplay();
        
        // Add event listeners
        prevButton.addEventListener('click', showPrevSlide);
        nextButton.addEventListener('click', showNextSlide);
        
        // Add event listeners to dots
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                showSlide(index);
                resetAutoplay();
            });
        });
        
        // Pause autoplay on hover
        carousel.addEventListener('mouseenter', pauseAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        
        // Add keyboard navigation
        document.addEventListener('keydown', handleKeyboardNavigation);
        
        // Add touch support
        let touchStartX = 0;
        let touchEndX = 0;
        
        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            pauseAutoplay();
        }, { passive: true });
        
        carousel.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoplay();
        }, { passive: true });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            if (touchEndX < touchStartX - swipeThreshold) {
                // Swipe left
                showNextSlide();
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe right
                showPrevSlide();
            }
        }
    }
    
    // Show a specific slide
    function showSlide(index) {
        if (isTransitioning) return;
        isTransitioning = true;
        
        // Hide all slides
        slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // Deactivate all dots
        dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show the selected slide
        slides[index].classList.add('active');
        
        // Activate the corresponding dot
        dots[index].classList.add('active');
        
        // Update current index
        currentIndex = index;
        
        // Reset transition state after animation completes
        setTimeout(() => {
            isTransitioning = false;
        }, 600); // Match this with the CSS transition duration
    }
    
    // Show the next slide
    function showNextSlide() {
        if (isTransitioning) return;
        
        let nextIndex = currentIndex + 1;
        if (nextIndex >= slides.length) {
            nextIndex = 0;
        }
        showSlide(nextIndex);
        resetAutoplay();
    }
    
    // Show the previous slide
    function showPrevSlide() {
        if (isTransitioning) return;
        
        let prevIndex = currentIndex - 1;
        if (prevIndex < 0) {
            prevIndex = slides.length - 1;
        }
        showSlide(prevIndex);
        resetAutoplay();
    }
    
    // Start autoplay
    function startAutoplay() {
        clearInterval(interval);
        interval = setInterval(showNextSlide, autoplayDelay);
    }
    
    // Pause autoplay
    function pauseAutoplay() {
        clearInterval(interval);
    }
    
    // Reset autoplay
    function resetAutoplay() {
        pauseAutoplay();
        startAutoplay();
    }
    
    // Handle keyboard navigation
    function handleKeyboardNavigation(e) {
        // Only handle keyboard navigation if the carousel is in the viewport
        const rect = carousel.getBoundingClientRect();
        const isInViewport = (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
        
        if (!isInViewport) return;
        
        if (e.key === 'ArrowLeft') {
            showPrevSlide();
        } else if (e.key === 'ArrowRight') {
            showNextSlide();
        }
    }
    
    // Initialize the carousel
    initCarousel();
    
    // Add resize handler to adjust for responsive design
    window.addEventListener('resize', function() {
        // Recalculate any dimensions if needed
    });
    
    // Add intersection observer to pause autoplay when not in viewport
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    startAutoplay();
                } else {
                    pauseAutoplay();
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(carousel);
    }
});
