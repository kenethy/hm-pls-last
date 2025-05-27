/**
 * Mechanic Rating System JavaScript
 * Handles star rating interactions, modal display, and AJAX submissions
 */

class MechanicRatingSystem {
    constructor() {
        this.currentServiceId = null;
        this.mechanics = [];
        this.ratings = {};
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupCSRF();
    }

    setupCSRF() {
        // Setup CSRF token for AJAX requests
        if (this.csrfToken) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = this.csrfToken;
        }
    }

    bindEvents() {
        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('ratingModal');
            if (e.target === modal) {
                this.closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    /**
     * Open rating modal for a specific service
     */
    async openRatingModal(serviceId) {
        this.currentServiceId = serviceId;
        const modal = document.getElementById('ratingModal');
        
        // Show modal and loading state
        modal.classList.remove('hidden');
        this.showLoadingState();

        try {
            // Fetch service and mechanics data
            const response = await fetch(`/api/ratings/service/${serviceId}/modal`);
            const data = await response.json();

            if (data.success) {
                this.displayServiceInfo(data.service);
                this.displayMechanics(data.mechanics);
                this.hideLoadingState();
            } else {
                this.showErrorState(data.message || 'Gagal memuat data servis');
            }
        } catch (error) {
            console.error('Error loading rating modal:', error);
            this.showErrorState('Terjadi kesalahan saat memuat data');
        }
    }

    /**
     * Display service information in the modal
     */
    displayServiceInfo(service) {
        document.getElementById('serviceType').textContent = service.service_type;
        document.getElementById('serviceDate').textContent = service.date;
        document.getElementById('vehicleInfo').textContent = service.vehicle;
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
            }

            container.appendChild(mechanicCard);
        });

        this.updateSubmitAllButton();
    }

    /**
     * Display existing rating for a mechanic
     */
    displayExistingRating(mechanicCard, rating) {
        const ratingSection = mechanicCard.querySelector('.rating-section');
        const alreadyRatedSection = mechanicCard.querySelector('.already-rated-section');
        const statusBadge = mechanicCard.querySelector('.rating-status-badge');

        // Hide rating section, show already rated section
        ratingSection.classList.add('hidden');
        alreadyRatedSection.classList.remove('hidden');

        // Update status badge
        statusBadge.textContent = 'Sudah Diberi Rating';
        statusBadge.classList.add('bg-green-100', 'text-green-800');

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
    }

    /**
     * Setup star rating interaction for a mechanic
     */
    setupStarRating(mechanicCard, mechanicId) {
        const stars = mechanicCard.querySelectorAll('.star');
        const statusBadge = mechanicCard.querySelector('.rating-status-badge');
        
        statusBadge.textContent = 'Belum Diberi Rating';
        statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');

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
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';

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
        const statusBadge = mechanicCard.querySelector('.rating-status-badge');

        // Hide rating section, show already rated section
        ratingSection.classList.add('hidden');
        alreadyRatedSection.classList.remove('hidden');

        // Update status badge
        statusBadge.textContent = 'Sudah Diberi Rating';
        statusBadge.classList.remove('bg-yellow-100', 'text-yellow-800');
        statusBadge.classList.add('bg-green-100', 'text-green-800');

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
                starsHtml += '<i class="fas fa-star text-yellow-400"></i>';
            } else {
                starsHtml += '<i class="fas fa-star text-gray-300"></i>';
            }
        }
        return starsHtml;
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
        const modal = document.getElementById('ratingModal');
        modal.classList.add('hidden');
        
        // Reset state
        this.currentServiceId = null;
        this.mechanics = [];
        this.ratings = {};
        
        // Clear content
        document.getElementById('mechanicsContainer').innerHTML = '';
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
    window.ratingSystem.openRatingModal(serviceId);
}

function closeRatingModal() {
    window.ratingSystem.closeModal();
}

function retryLoadRatingModal() {
    if (window.ratingSystem.currentServiceId) {
        window.ratingSystem.openRatingModal(window.ratingSystem.currentServiceId);
    }
}

function submitAllRatings() {
    // This function can be implemented later for bulk rating submission
    alert('Fitur kirim semua rating akan segera tersedia');
}

// Initialize the rating system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.ratingSystem = new MechanicRatingSystem();
});
