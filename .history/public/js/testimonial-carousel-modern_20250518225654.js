/**
 * Modern Testimonial Carousel
 * A responsive, accessible testimonial carousel with smooth transitions
 */
document.addEventListener('DOMContentLoaded', function () {
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
        // Set initial slide classes
        slides[0].classList.add('active');
        slides[slides.length - 1].classList.add('prev');
        slides[1].classList.add('next');
        dots[0].classList.add('active');

        // Add a small delay before starting animations to ensure DOM is ready
        setTimeout(() => {
            // Add animate-in class to first slide for initial animation
            slides[0].classList.add('animate-in');

            // Remove the animation class after it completes
            setTimeout(() => {
                slides[0].classList.remove('animate-in');
            }, 800);
        }, 100);

        // Start autoplay
        startAutoplay();

        // Add event listeners
        prevButton.addEventListener('click', showPrevSlide);
        nextButton.addEventListener('click', showNextSlide);

        // Add event listeners to dots
        dots.forEach(dot => {
            dot.addEventListener('click', function () {
                const index = parseInt(this.getAttribute('data-index'));
                const direction = index > currentIndex ? 'right' : 'left';
                showSlide(index, direction);
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

        carousel.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
            pauseAutoplay();
        }, { passive: true });

        carousel.addEventListener('touchend', function (e) {
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
    function showSlide(index, direction = null) {
        if (isTransitioning) return;
        isTransitioning = true;

        // Get the current active slide
        const currentSlide = slides[currentIndex];
        const targetSlide = slides[index];

        // Determine animation direction if not specified
        if (direction === null) {
            direction = index > currentIndex ? 'right' : 'left';
            // Handle edge cases (first to last, last to first)
            if (currentIndex === slides.length - 1 && index === 0) {
                direction = 'right';
            } else if (currentIndex === 0 && index === slides.length - 1) {
                direction = 'left';
            }
        }

        // Remove all animation classes first
        slides.forEach(slide => {
            slide.classList.remove('active', 'animate-in', 'animate-in-left', 'animate-in-right', 'animate-out-left', 'animate-out-right', 'prev', 'next');
        });

        // Deactivate all dots
        dots.forEach(dot => {
            dot.classList.remove('active');
        });

        // Set up the animations based on direction
        if (direction === 'right') {
            currentSlide.classList.add('animate-out-left');
            targetSlide.classList.add('active', 'animate-in-right');
        } else {
            currentSlide.classList.add('animate-out-right');
            targetSlide.classList.add('active', 'animate-in-left');
        }

        // Activate the corresponding dot with a smooth transition
        dots[index].classList.add('active');

        // Update current index
        currentIndex = index;

        // Reset transition state after animation completes
        setTimeout(() => {
            // Clean up animation classes
            currentSlide.classList.remove('animate-out-left', 'animate-out-right');
            targetSlide.classList.remove('animate-in-left', 'animate-in-right');

            // Set previous and next slides for depth effect
            const prevIndex = (index - 1 + slides.length) % slides.length;
            const nextIndex = (index + 1) % slides.length;

            slides[prevIndex].classList.add('prev');
            slides[nextIndex].classList.add('next');

            isTransitioning = false;
        }, 800); // Match this with the CSS animation duration
    }

    // Show the next slide
    function showNextSlide() {
        if (isTransitioning) return;

        let nextIndex = currentIndex + 1;
        if (nextIndex >= slides.length) {
            nextIndex = 0;
        }
        showSlide(nextIndex, 'right');
        resetAutoplay();
    }

    // Show the previous slide
    function showPrevSlide() {
        if (isTransitioning) return;

        let prevIndex = currentIndex - 1;
        if (prevIndex < 0) {
            prevIndex = slides.length - 1;
        }
        showSlide(prevIndex, 'left');
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
    window.addEventListener('resize', function () {
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
