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
            if (e.target.id === 'ratingModal') {
                this.closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
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
                modal.classList.remove('hidden');
                modal.style.display = 'block';
                modalOpened = true;
                console.log('✅ Modal opened using class manipulation');
            } catch (error) {
                console.warn('⚠️ Class manipulation method failed:', error);
            }
        }

        // Method 3: Wait for Alpine.js to initialize (last resort)
        if (!modalOpened) {
            console.log('🔄 Waiting for Alpine.js to initialize...');
            let attempts = 0;
            const maxAttempts = 10;

            const waitForAlpine = setInterval(() => {
                attempts++;
                console.log(`🔄 Alpine.js check attempt ${attempts}/${maxAttempts}`);

                if (modal.__x && modal.__x.$data) {
                    try {
                        modal.__x.$data.isOpen = true;
                        modalOpened = true;
                        console.log('✅ Modal opened after Alpine.js initialization');
                        clearInterval(waitForAlpine);
                    } catch (error) {
                        console.warn('⚠️ Alpine.js still not ready:', error);
                    }
                } else if (attempts >= maxAttempts) {
                    console.error('❌ Alpine.js failed to initialize after maximum attempts');
                    // Final fallback - force show modal
                    modal.classList.remove('hidden');
                    modal.style.display = 'block';
                    modalOpened = true;
                    clearInterval(waitForAlpine);
                }
            }, 200); // Check every 200ms
        }

        if (modalOpened) {
            // Load service data immediately
            this.displayServiceInfo(serviceData);
            this.loadMechanicsData(serviceData.service_id);
        } else {
            console.error('❌ Failed to open modal using any method');
        }
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
     * Setup star rating interaction
     */
    setupStarRating(mechanicCard, mechanicId) {
        const stars = mechanicCard.querySelectorAll('.star');

        stars.forEach((star, index) => {
            // Hover effects
            star.addEventListener('mouseenter', () => {
                this.highlightStars(stars, index + 1);
            });

            star.addEventListener('mouseleave', () => {
                const currentRating = this.ratings[mechanicId]?.rating || 0;
                this.highlightStars(stars, currentRating);
            });

            // Click to select rating
            star.addEventListener('click', () => {
                const rating = index + 1;
                this.setRating(mechanicId, rating);
                this.highlightStars(stars, rating);
                this.updateSubmitButton(mechanicCard, mechanicId);
            });
        });
    }

    /**
     * Highlight stars up to a certain rating
     */
    highlightStars(stars, rating) {
        stars.forEach((star, index) => {
            const svg = star.querySelector('svg');
            if (index < rating) {
                svg.classList.remove('text-gray-300');
                svg.classList.add('text-amber-400');
            } else {
                svg.classList.remove('text-amber-400');
                svg.classList.add('text-gray-300');
            }
        });
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

        // Handle comment input
        commentTextarea.addEventListener('input', (e) => {
            if (!this.ratings[mechanicId]) {
                this.ratings[mechanicId] = {};
            }
            this.ratings[mechanicId].comment = e.target.value;
        });

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

// Initialize the Filament rating system when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Initializing Filament Rating System...');

    window.filamentRatingSystem = new FilamentRatingSystem();

    // Check for immediate triggers multiple times
    console.log('⏰ Setting up trigger checks...');
    setTimeout(() => {
        console.log('🔄 First trigger check (500ms)');
        checkImmediateRatingTriggers();
    }, 500);

    setTimeout(() => {
        console.log('🔄 Second trigger check (2000ms)');
        checkImmediateRatingTriggers();
    }, 2000);

    setTimeout(() => {
        console.log('🔄 Third trigger check (5000ms)');
        checkImmediateRatingTriggers();
    }, 5000);

    // Also check when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            console.log('👁️ Page became visible, checking triggers...');
            setTimeout(checkImmediateRatingTriggers, 500);
        }
    });

    console.log('✅ Filament Rating System ready');
});
