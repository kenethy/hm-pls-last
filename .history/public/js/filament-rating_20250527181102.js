/**
 * Filament-Integrated Mechanic Rating System
 * Designed to work seamlessly with Filament admin panel
 */

class FilamentRatingSystem {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.currentServiceId = null;
        this.mechanics = [];
        this.ratings = {};
        this.pendingBulkRatings = [];
        this.modalJustOpened = false; // Flag to prevent immediate closing

        this.init();
    }

    init() {
        console.log('🚀 Filament Rating System initializing...');
        this.setupCSRF();
        this.bindEvents();
        console.log('✅ Filament Rating System initialized');
    }

    setupCSRF() {
        if (this.csrfToken) {
            // Try to set axios defaults if axios is available
            if (window.axios && window.axios.defaults) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = this.csrfToken;
                console.log('✅ CSRF token set for axios');
            } else {
                console.log('ℹ️ Axios not available, using fetch with manual CSRF headers');
            }
        } else {
            console.warn('⚠️ CSRF token not found in meta tag');
        }
    }

    bindEvents() {
        // Close modal when clicking outside or pressing escape
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('ratingModal');

            // Don't close if modal just opened
            if (this.modalJustOpened) {
                console.log('🚫 Ignoring click - modal just opened');
                return;
            }

            // Only close if modal is visible and click is on the modal overlay (not the modal content)
            if (modal &&
                (modal.style.display === 'block' || !modal.classList.contains('hidden')) &&
                e.target.id === 'ratingModal') {
                console.log('🔒 Modal close triggered by outside click');
                this.closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('ratingModal');

            // Don't close if modal just opened
            if (this.modalJustOpened) {
                console.log('🚫 Ignoring Escape - modal just opened');
                return;
            }

            // Only close if modal is visible and Escape is pressed
            if (e.key === 'Escape' &&
                modal &&
                (modal.style.display === 'block' || !modal.classList.contains('hidden'))) {
                console.log('🔒 Modal close triggered by Escape key');
                this.closeModal();
            }
        });
    }

    /**
     * Open rating modal immediately (integrated with Filament workflow)
     */
    openModalImmediately(serviceData) {
        console.log('🎯 Opening rating modal immediately for service:', serviceData.service_id);

        this.currentServiceId = serviceData.service_id;
        const modal = document.getElementById('ratingModal');

        if (!modal) {
            console.error('❌ Rating modal element not found in DOM');
            console.log('🔍 Available elements with "Modal" in ID:',
                Array.from(document.querySelectorAll('[id*="Modal"]')).map(el => el.id));
            return;
        }

        console.log('✅ Modal element found:', modal);
        console.log('🔍 Modal Alpine.js data:', modal.__x);

        // Debug modal styles BEFORE opening
        console.log('📊 Modal styles BEFORE opening:', {
            display: modal.style.display,
            visibility: modal.style.visibility,
            opacity: modal.style.opacity,
            zIndex: modal.style.zIndex,
            position: modal.style.position,
            classes: modal.className,
            computedDisplay: window.getComputedStyle(modal).display,
            computedVisibility: window.getComputedStyle(modal).visibility,
            computedOpacity: window.getComputedStyle(modal).opacity,
            computedZIndex: window.getComputedStyle(modal).zIndex
        });

        // Try multiple methods to show the modal
        let modalOpened = false;

        // Method 1: Alpine.js (preferred)
        if (modal.__x && modal.__x.$data) {
            try {
                console.log('🎯 Opening modal using Alpine.js...');
                modal.__x.$data.isOpen = true;
                modalOpened = true;
                console.log('✅ Modal opened using Alpine.js');
            } catch (error) {
                console.warn('⚠️ Alpine.js method failed:', error);
            }
        }

        // Method 2: Direct class manipulation (fallback)
        if (!modalOpened) {
            try {
                console.log('🎯 Opening modal using class manipulation...');
                this.forceShowModal(modal);
                modalOpened = true;
                console.log('✅ Modal opened using class manipulation');

                // Debug modal styles AFTER opening
                console.log('📊 Modal styles AFTER opening:', {
                    display: modal.style.display,
                    visibility: modal.style.visibility,
                    opacity: modal.style.opacity,
                    zIndex: modal.style.zIndex,
                    position: modal.style.position,
                    classes: modal.className,
                    computedDisplay: window.getComputedStyle(modal).display,
                    computedVisibility: window.getComputedStyle(modal).visibility,
                    computedOpacity: window.getComputedStyle(modal).opacity,
                    computedZIndex: window.getComputedStyle(modal).zIndex
                });
            } catch (error) {
                console.warn('⚠️ Class manipulation method failed:', error);
            }
        }

        // Method 3: Force Alpine.js initialization and try again
        if (!modalOpened && window.Alpine) {
            console.log('🔧 Attempting to force Alpine.js initialization...');
            try {
                // Try to initialize Alpine on the modal
                window.Alpine.initTree(modal);

                // Wait a moment for initialization to complete
                setTimeout(() => {
                    if (modal.__x && modal.__x.$data) {
                        try {
                            modal.__x.$data.isOpen = true;
                            modalOpened = true;
                            console.log('✅ Modal opened after forced Alpine.js initialization');
                        } catch (error) {
                            console.warn('⚠️ Alpine.js still not working after forced init:', error);
                        }
                    }

                    // Final fallback if still not opened
                    if (!modalOpened) {
                        console.log('🔧 Using final fallback method...');
                        modal.classList.remove('hidden');
                        modal.style.display = 'block';
                        modalOpened = true;
                        console.log('✅ Modal opened using final fallback');
                    }
                }, 100);
            } catch (error) {
                console.warn('⚠️ Failed to force Alpine.js initialization:', error);
                // Final fallback - force show modal
                modal.classList.remove('hidden');
                modal.style.display = 'block';
                modalOpened = true;
                console.log('✅ Modal opened using emergency fallback');
            }
        }

        // Method 4: Emergency fallback if Alpine.js is not available
        if (!modalOpened) {
            console.log('🚨 Emergency fallback - showing modal without Alpine.js');
            this.forceShowModal(modal);
            modalOpened = true;
            console.log('✅ Modal opened using emergency fallback');
        }

        if (modalOpened) {
            // Set flag to prevent immediate closing
            this.modalJustOpened = true;
            console.log('🔒 Modal just opened flag set');

            // Clear the flag after a short delay
            setTimeout(() => {
                this.modalJustOpened = false;
                console.log('🔓 Modal just opened flag cleared');
            }, 1000); // 1 second delay

            // Load service data immediately
            this.displayServiceInfo(serviceData);
            this.loadMechanicsData(serviceData.service_id);
        } else {
            console.error('❌ Failed to open modal using any method');
        }
    }

    /**
     * Force show modal with aggressive styling
     */
    forceShowModal(modal) {
        console.log('💪 Force showing modal with aggressive styling...');

        // Remove all hiding classes
        modal.classList.remove('hidden');
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');

        // Set aggressive inline styles
        modal.style.cssText = `
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 99999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
        `;

        // Also force show child elements
        const modalWindow = modal.querySelector('.fi-modal-window');
        if (modalWindow) {
            modalWindow.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                transform: scale(1) !important;
            `;
        }

        const overlay = modal.querySelector('.fi-modal-close-overlay');
        if (overlay) {
            overlay.style.cssText = `
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            `;
        }

        console.log('💪 Modal forced to show with aggressive styling');
    }

    /**
     * Load mechanics data for the service
     */
    async loadMechanicsData(serviceId) {
        this.showLoadingState();

        try {
            const response = await fetch(`/api/ratings/service/${serviceId}/modal`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📊 Mechanics data received:', data);

            if (data.success) {
                this.displayMechanics(data.mechanics);
                this.hideLoadingState();
                console.log('✅ Mechanics data loaded successfully');
            } else {
                console.error('❌ API returned error:', data.message);
                this.showErrorState(data.message || 'Gagal memuat data montir');
            }
        } catch (error) {
            console.error('❌ Error loading mechanics data:', error);
            this.showErrorState('Terjadi kesalahan saat memuat data: ' + error.message);
        }
    }

    /**
     * Display service information in the modal
     */
    displayServiceInfo(serviceData) {
        document.getElementById('serviceType').textContent = serviceData.service_type || '-';
        document.getElementById('serviceDate').textContent = new Date().toLocaleDateString('id-ID') || '-';
        document.getElementById('vehicleInfo').textContent = serviceData.vehicle_info || '-';
    }

    /**
     * Display mechanics for rating
     */
    displayMechanics(mechanics) {
        this.mechanics = mechanics;
        const container = document.getElementById('mechanicsContainer');
        const template = document.getElementById('mechanicRatingTemplate');

        container.innerHTML = '';

        mechanics.forEach(mechanic => {
            const mechanicCard = template.content.cloneNode(true);

            // Set mechanic info
            mechanicCard.querySelector('.mechanic-name').textContent = mechanic.name;
            mechanicCard.querySelector('.mechanic-specialization').textContent = mechanic.specialization || 'Montir Umum';

            // Set mechanic ID for star rating and submit button
            const starRating = mechanicCard.querySelector('.star-rating');
            const submitBtn = mechanicCard.querySelector('.submit-individual-rating');
            starRating.setAttribute('data-mechanic-id', mechanic.id);
            submitBtn.setAttribute('data-mechanic-id', mechanic.id);

            // Handle already rated mechanics
            if (mechanic.has_rating && mechanic.existing_rating) {
                this.displayExistingRating(mechanicCard, mechanic.existing_rating);
            } else {
                this.setupStarRating(mechanicCard, mechanic.id);
                this.setupSubmitButton(mechanicCard, mechanic.id);
                this.updateStatusBadge(mechanicCard, 'pending');
            }

            container.appendChild(mechanicCard);
        });

        this.updateSubmitAllButton();
    }

    /**
     * Update status badge
     */
    updateStatusBadge(mechanicCard, status) {
        const badge = mechanicCard.querySelector('.rating-status-badge');

        // Reset classes
        badge.className = 'rating-status-badge fi-badge inline-flex items-center justify-center whitespace-nowrap rounded-md border px-2 py-1 text-xs font-medium ring-1 ring-inset transition duration-75';

        switch (status) {
            case 'pending':
                badge.classList.add('bg-amber-50', 'text-amber-700', 'ring-amber-600/10', 'dark:bg-amber-400/10', 'dark:text-amber-400', 'dark:ring-amber-400/30');
                badge.textContent = 'Belum Rating';
                break;
            case 'completed':
                badge.classList.add('bg-green-50', 'text-green-700', 'ring-green-600/10', 'dark:bg-green-400/10', 'dark:text-green-400', 'dark:ring-green-400/30');
                badge.textContent = 'Sudah Rating';
                break;
        }
    }

    /**
     * Setup star rating interaction with enhanced feedback
     */
    setupStarRating(mechanicCard, mechanicId) {
        const stars = mechanicCard.querySelectorAll('.star');
        const statusBadge = mechanicCard.querySelector('.rating-status-badge');

        console.log(`🔧 Setting up star rating for mechanic ${mechanicId}`);

        stars.forEach((star, index) => {
            // Enhanced hover effects
            star.addEventListener('mouseenter', () => {
                this.highlightStars(stars, index + 1);
                // Update status badge during hover
                statusBadge.textContent = `Rating ${index + 1} bintang`;
                statusBadge.className = 'rating-status-badge text-xs px-2 py-1 rounded-full bg-amber-100 dark:bg-amber-700 text-amber-600 dark:text-amber-300';
            });

            star.addEventListener('mouseleave', () => {
                const currentRating = this.ratings[mechanicId]?.rating || 0;
                this.highlightStars(stars, currentRating);
                // Reset status badge
                if (currentRating > 0) {
                    statusBadge.textContent = `${currentRating} bintang`;
                    statusBadge.className = 'rating-status-badge text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-700 text-green-600 dark:text-green-300';
                } else {
                    statusBadge.textContent = 'Belum Rating';
                    statusBadge.className = 'rating-status-badge text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
                }
            });

            // Enhanced click to select rating
            star.addEventListener('click', () => {
                const rating = index + 1;
                console.log(`⭐ Star clicked: Rating ${rating} for mechanic ${mechanicId}`);

                this.setRating(mechanicId, rating);
                this.highlightStars(stars, rating);
                this.updateSubmitButton(mechanicCard, mechanicId);

                // Update status badge immediately
                statusBadge.textContent = `${rating} bintang`;
                statusBadge.className = 'rating-status-badge text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-700 text-green-600 dark:text-green-300';

                console.log(`✅ Rating ${rating} set for mechanic ${mechanicId}`);
            });
        });
    }

    /**
     * Highlight stars up to a certain rating with enhanced visual feedback
     */
    highlightStars(stars, rating) {
        console.log(`⭐ Highlighting stars: rating ${rating}`);

        stars.forEach((star, index) => {
            const svg = star.querySelector('svg');
            if (index < rating) {
                // Active star - amber color
                svg.classList.remove('text-gray-300');
                svg.classList.add('text-amber-400');
                star.classList.add('scale-110'); // Slight scale for selected stars
            } else {
                // Inactive star - gray color
                svg.classList.remove('text-amber-400');
                svg.classList.add('text-gray-300');
                star.classList.remove('scale-110');
            }
        });

        console.log(`✅ Stars highlighted for rating ${rating}`);
    }

    /**
     * Set rating for a mechanic
     */
    setRating(mechanicId, rating) {
        if (!this.ratings[mechanicId]) {
            this.ratings[mechanicId] = {};
        }
        this.ratings[mechanicId].rating = rating;
    }

    /**
     * Setup submit button for individual rating
     */
    setupSubmitButton(mechanicCard, mechanicId) {
        const submitBtn = mechanicCard.querySelector('.submit-individual-rating');
        const commentTextarea = mechanicCard.querySelector('.rating-comment');

        // Enable/disable submit button based on rating
        this.updateSubmitButton(mechanicCard, mechanicId);

        // Handle comment input (only if comment textarea exists in template)
        if (commentTextarea) {
            commentTextarea.addEventListener('input', (e) => {
                if (!this.ratings[mechanicId]) {
                    this.ratings[mechanicId] = {};
                }
                this.ratings[mechanicId].comment = e.target.value;
            });
        }

        // Handle submit button click
        submitBtn.addEventListener('click', () => {
            this.submitIndividualRating(mechanicId, mechanicCard);
        });
    }

    /**
     * Update submit button state
     */
    updateSubmitButton(mechanicCard, mechanicId) {
        const submitBtn = mechanicCard.querySelector('.submit-individual-rating');
        const hasRating = this.ratings[mechanicId]?.rating > 0;

        submitBtn.disabled = !hasRating;

        if (hasRating) {
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    /**
     * Submit individual rating for a mechanic
     */
    async submitIndividualRating(mechanicId, mechanicCard) {
        const rating = this.ratings[mechanicId];
        if (!rating || !rating.rating) {
            this.showErrorToast('Silakan pilih rating terlebih dahulu');
            return;
        }

        const submitBtn = mechanicCard.querySelector('.submit-individual-rating');
        const originalText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Mengirim...';

        try {
            const response = await fetch('/api/ratings/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    service_id: this.currentServiceId,
                    mechanic_id: mechanicId,
                    rating: rating.rating,
                    comment: rating.comment || null
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccessMessage(mechanicCard);
                this.showSuccessToast(data.message);

                // Update mechanic data to reflect the new rating
                const mechanic = this.mechanics.find(m => m.id == mechanicId);
                if (mechanic) {
                    mechanic.has_rating = true;
                    mechanic.existing_rating = data.rating;
                }

                // Convert to already rated display
                setTimeout(() => {
                    this.convertToAlreadyRated(mechanicCard, data.rating);
                    this.updateStatusBadge(mechanicCard, 'completed');
                }, 2000);

            } else {
                this.showErrorMessage(mechanicCard, data.message);
                this.showErrorToast(data.message);
            }
        } catch (error) {
            console.error('Error submitting rating:', error);
            this.showErrorMessage(mechanicCard, 'Terjadi kesalahan saat mengirim rating');
            this.showErrorToast('Terjadi kesalahan saat mengirim rating');
        } finally {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    /**
     * Convert rating section to already rated display
     */
    convertToAlreadyRated(mechanicCard, ratingData) {
        const ratingSection = mechanicCard.querySelector('.rating-section');
        const alreadyRatedSection = mechanicCard.querySelector('.already-rated-section');

        // Hide rating section, show already rated section
        ratingSection.classList.add('hidden');
        alreadyRatedSection.classList.remove('hidden');

        // Display stars
        const starsContainer = mechanicCard.querySelector('.existing-stars');
        starsContainer.innerHTML = this.generateStarDisplay(ratingData.rating);

        // Display comment and date
        const commentElement = mechanicCard.querySelector('.existing-comment');
        const dateElement = mechanicCard.querySelector('.existing-date');

        if (ratingData.comment) {
            commentElement.textContent = ratingData.comment;
        } else {
            commentElement.textContent = 'Tidak ada komentar';
            commentElement.classList.add('italic', 'text-gray-500');
        }

        dateElement.textContent = `Diberi rating baru saja`;
        this.updateSubmitAllButton();
    }

    /**
     * Generate star display HTML
     */
    generateStarDisplay(rating) {
        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                starsHtml += '<svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
            } else {
                starsHtml += '<svg class="h-4 w-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
            }
        }
        return starsHtml;
    }

    /**
     * Display existing rating for a mechanic
     */
    displayExistingRating(mechanicCard, rating) {
        const ratingSection = mechanicCard.querySelector('.rating-section');
        const alreadyRatedSection = mechanicCard.querySelector('.already-rated-section');

        // Hide rating section, show already rated section
        ratingSection.classList.add('hidden');
        alreadyRatedSection.classList.remove('hidden');

        // Display existing stars
        const starsContainer = mechanicCard.querySelector('.existing-stars');
        starsContainer.innerHTML = this.generateStarDisplay(rating.rating);

        // Display existing comment and date
        const commentElement = mechanicCard.querySelector('.existing-comment');
        const dateElement = mechanicCard.querySelector('.existing-date');

        if (rating.comment) {
            commentElement.textContent = rating.comment;
        } else {
            commentElement.textContent = 'Tidak ada komentar';
            commentElement.classList.add('italic', 'text-gray-500');
        }

        dateElement.textContent = `Diberi rating pada ${rating.created_at}`;
        this.updateStatusBadge(mechanicCard, 'completed');
    }

    /**
     * Show success message in mechanic card
     */
    showSuccessMessage(mechanicCard) {
        const successMsg = mechanicCard.querySelector('.success-message');
        successMsg.classList.remove('hidden');

        setTimeout(() => {
            successMsg.classList.add('hidden');
        }, 3000);
    }

    /**
     * Show error message in mechanic card
     */
    showErrorMessage(mechanicCard, message) {
        const errorMsg = mechanicCard.querySelector('.error-message');
        const errorText = mechanicCard.querySelector('.error-text');

        errorText.textContent = message;
        errorMsg.classList.remove('hidden');

        setTimeout(() => {
            errorMsg.classList.add('hidden');
        }, 5000);
    }

    /**
     * Update submit all button visibility
     */
    updateSubmitAllButton() {
        const submitAllBtn = document.getElementById('submitAllRatings');
        const hasUnratedMechanics = this.mechanics.some(mechanic => !mechanic.has_rating);

        if (hasUnratedMechanics) {
            submitAllBtn.classList.remove('hidden');
        } else {
            submitAllBtn.classList.add('hidden');
        }
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('mechanicsContainer').classList.add('hidden');
        document.getElementById('errorState').classList.add('hidden');
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('mechanicsContainer').classList.remove('hidden');
    }

    /**
     * Show error state
     */
    showErrorState(message) {
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('mechanicsContainer').classList.add('hidden');
    }

    /**
     * Close the rating modal
     */
    closeModal() {
        console.log('🔒 Closing rating modal...');
        const modal = document.getElementById('ratingModal');

        if (!modal) {
            console.warn('⚠️ Modal element not found when trying to close');
            return;
        }

        let modalClosed = false;

        // Method 1: Alpine.js (preferred)
        if (modal.__x && modal.__x.$data) {
            try {
                modal.__x.$data.isOpen = false;
                modalClosed = true;
                console.log('✅ Modal closed using Alpine.js');
            } catch (error) {
                console.warn('⚠️ Alpine.js close method failed:', error);
            }
        }

        // Method 2: Direct class manipulation (fallback)
        if (!modalClosed) {
            try {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                modalClosed = true;
                console.log('✅ Modal closed using class manipulation');
            } catch (error) {
                console.warn('⚠️ Class manipulation close method failed:', error);
            }
        }

        // Reset state
        this.currentServiceId = null;
        this.mechanics = [];
        this.ratings = {};
        this.modalJustOpened = false; // Reset the flag

        // Clear content
        const mechanicsContainer = document.getElementById('mechanicsContainer');
        if (mechanicsContainer) {
            mechanicsContainer.innerHTML = '';
        }

        // Check for pending bulk ratings
        this.checkPendingBulkRatings();

        console.log('✅ Modal state reset complete');
    }

    /**
     * Check for pending bulk ratings and show next modal
     */
    checkPendingBulkRatings() {
        const pendingRatings = JSON.parse(sessionStorage.getItem('pending_bulk_ratings') || '[]');

        if (pendingRatings.length > 0) {
            const nextService = pendingRatings.shift();
            sessionStorage.setItem('pending_bulk_ratings', JSON.stringify(pendingRatings));

            // Show next rating modal after a short delay
            setTimeout(() => {
                this.openModalImmediately(nextService);
            }, 1000);
        }
    }

    /**
     * Show success toast notification
     */
    showSuccessToast(message) {
        const toast = document.getElementById('successToast');
        const messageElement = document.getElementById('successMessage');

        messageElement.textContent = message;
        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.add('hidden');
        }, 4000);
    }

    /**
     * Show error toast notification
     */
    showErrorToast(message) {
        const toast = document.getElementById('errorToast');
        const messageElement = document.getElementById('errorMessage');

        messageElement.textContent = message;
        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.add('hidden');
        }, 5000);
    }
}

// Global functions for modal control
function openRatingModal(serviceId) {
    if (window.filamentRatingSystem) {
        window.filamentRatingSystem.loadMechanicsData(serviceId);
    }
}

function closeRatingModal() {
    if (window.filamentRatingSystem) {
        window.filamentRatingSystem.closeModal();
    }
}

function retryLoadRatingModal() {
    if (window.filamentRatingSystem && window.filamentRatingSystem.currentServiceId) {
        window.filamentRatingSystem.loadMechanicsData(window.filamentRatingSystem.currentServiceId);
    }
}

function submitAllRatings() {
    // This function can be implemented later for bulk rating submission
    alert('Fitur kirim semua rating akan segera tersedia');
}

function remindRatingLaterFromModal() {
    if (window.filamentRatingSystem && window.filamentRatingSystem.currentServiceId) {
        // Store reminder for later
        const reminders = JSON.parse(localStorage.getItem('ratingReminders') || '[]');
        reminders.push({
            serviceId: window.filamentRatingSystem.currentServiceId,
            timestamp: Date.now(),
            reminderTime: Date.now() + (2 * 60 * 60 * 1000) // Remind in 2 hours
        });
        localStorage.setItem('ratingReminders', JSON.stringify(reminders));

        // Close the modal
        closeRatingModal();

        // Show confirmation
        if (window.filamentRatingSystem) {
            window.filamentRatingSystem.showSuccessToast('Pengingat rating telah diatur untuk 2 jam ke depan');
        }
    } else {
        alert('Tidak dapat mengatur pengingat. Silakan coba lagi.');
    }
}

/**
 * Check for immediate rating modal triggers
 */
function checkImmediateRatingTriggers() {
    console.log('🔍 Checking for immediate rating triggers...');

    // Check for immediate modal trigger
    fetch('/api/check-rating-popup', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
        }
    })
        .then(response => {
            console.log('📡 API Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📊 Full API Response:', data);

            if (data.trigger_modal && data.service_data) {
                console.log('🎯 Immediate modal trigger detected for service:', data.service_data.service_id);
                window.filamentRatingSystem.openModalImmediately(data.service_data);
            } else {
                console.log('❌ No immediate modal trigger found');
                console.log('   - trigger_modal:', data.trigger_modal);
                console.log('   - service_data:', data.service_data);
            }

            // Handle bulk ratings
            if (data.bulk_ratings && data.bulk_ratings.length > 0) {
                console.log('📦 Bulk ratings found:', data.bulk_ratings.length);
                sessionStorage.setItem('pending_bulk_ratings', JSON.stringify(data.bulk_ratings));
            }

            // Check legacy triggers
            if (data.show_direct_modal && data.service_data) {
                console.log('🔄 Legacy direct modal trigger detected');
                window.filamentRatingSystem.openModalImmediately(data.service_data);
            }
        })
        .catch(error => {
            console.error('❌ Could not check rating triggers:', error);
        });
}

/**
 * Wait for Alpine.js to be ready
 */
function waitForAlpine(callback, maxAttempts = 20) {
    let attempts = 0;

    const checkAlpine = () => {
        attempts++;
        console.log(`🔄 Checking for Alpine.js... attempt ${attempts}/${maxAttempts}`);

        if (window.Alpine) {
            console.log('✅ Alpine.js found!');
            callback();
        } else if (attempts >= maxAttempts) {
            console.warn('⚠️ Alpine.js not found after maximum attempts, proceeding anyway...');
            callback();
        } else {
            setTimeout(checkAlpine, 100);
        }
    };

    checkAlpine();
}

/**
 * Initialize Alpine.js on modal if not already initialized
 */
function initializeModalAlpine() {
    const modal = document.getElementById('ratingModal');
    if (!modal) {
        console.warn('⚠️ Modal not found for Alpine initialization');
        return;
    }

    console.log('🔧 Checking modal Alpine.js initialization...');

    // Check if Alpine is already initialized on this element
    if (!modal.__x && window.Alpine) {
        console.log('🔧 Manually initializing Alpine.js on modal...');
        try {
            // Force Alpine to initialize this element
            window.Alpine.initTree(modal);
            console.log('✅ Alpine.js manually initialized on modal');
        } catch (error) {
            console.warn('⚠️ Failed to manually initialize Alpine.js:', error);
        }
    } else if (modal.__x) {
        console.log('✅ Alpine.js already initialized on modal');
    } else {
        console.warn('⚠️ Alpine.js not available for manual initialization');
    }
}

// Initialize the Filament rating system when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Initializing Filament Rating System...');

    window.filamentRatingSystem = new FilamentRatingSystem();

    // Wait for Alpine.js to be ready before setting up triggers
    waitForAlpine(() => {
        console.log('🎯 Alpine.js ready, initializing modal...');
        initializeModalAlpine();

        // Check for immediate triggers - immediate and frequent checks
        console.log('⏰ Setting up immediate trigger checks...');

        // Immediate check
        checkImmediateRatingTriggers();

        // Quick follow-up checks
        setTimeout(() => {
            console.log('🔄 Quick trigger check (100ms)');
            checkImmediateRatingTriggers();
        }, 100);

        setTimeout(() => {
            console.log('🔄 Second trigger check (300ms)');
            checkImmediateRatingTriggers();
        }, 300);

        setTimeout(() => {
            console.log('🔄 Final trigger check (1000ms)');
            checkImmediateRatingTriggers();
        }, 1000);

        // Also check when page becomes visible (user switches back to tab)
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                console.log('👁️ Page became visible, checking triggers...');
                setTimeout(checkImmediateRatingTriggers, 500);
            }
        });

        console.log('✅ Filament Rating System ready');
    });
});
