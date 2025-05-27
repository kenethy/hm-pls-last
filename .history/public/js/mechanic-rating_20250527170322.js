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

function remindRatingLaterFromModal() {
    if (window.ratingSystem && window.ratingSystem.currentServiceId) {
        // Use the existing remind later functionality
        remindRatingLater(window.ratingSystem.currentServiceId);

        // Close the modal
        closeRatingModal();

        // Show confirmation
        if (window.ratingSystem) {
            window.ratingSystem.showSuccessToast('Pengingat rating telah diatur untuk 2 jam ke depan');
        }
    } else {
        alert('Tidak dapat mengatur pengingat. Silakan coba lagi.');
    }
}

/**
 * Show automatic rating popup notification after service completion
 */
function showRatingNotificationPopup(serviceData) {
    // Create notification popup HTML
    const popupHtml = `
        <div id="ratingNotificationPopup" class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Servis Selesai - Kumpulkan Rating Montir
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Servis untuk <strong>${serviceData.customer_name}</strong> telah selesai.
                                    </p>
                                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                        <p><strong>Jenis Servis:</strong> ${serviceData.service_type}</p>
                                        <p><strong>Kendaraan:</strong> ${serviceData.vehicle_info}</p>
                                        <p><strong>Montir:</strong> ${serviceData.mechanics.map(m => m.name).join(', ')}</p>
                                    </div>
                                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900 rounded-md">
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            💡 <strong>Tip:</strong> Kumpulkan rating dari pelanggan sekarang selagi servis masih fresh di ingatan!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="openRatingModalFromNotification(${serviceData.service_id})"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            Rating Sekarang
                        </button>
                        <button type="button" onclick="remindRatingLater(${serviceData.service_id})"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Ingatkan Nanti
                        </button>
                        <button type="button" onclick="dismissRatingNotification()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing popup if any
    const existingPopup = document.getElementById('ratingNotificationPopup');
    if (existingPopup) {
        existingPopup.remove();
    }

    // Add popup to body
    document.body.insertAdjacentHTML('beforeend', popupHtml);

    // Auto-dismiss after 30 seconds if no action taken
    setTimeout(() => {
        dismissRatingNotification();
    }, 30000);
}

/**
 * Open rating modal directly from notification
 */
function openRatingModalFromNotification(serviceId) {
    dismissRatingNotification();

    // Small delay to ensure notification is dismissed before opening modal
    setTimeout(() => {
        if (window.ratingSystem) {
            window.ratingSystem.openRatingModal(serviceId);
        } else {
            // Fallback if rating system not initialized
            openRatingModal(serviceId);
        }
    }, 100);
}

/**
 * Remind rating later - store in localStorage for later reminder
 */
function remindRatingLater(serviceId) {
    // Store reminder in localStorage
    const reminders = JSON.parse(localStorage.getItem('ratingReminders') || '[]');
    const reminder = {
        serviceId: serviceId,
        timestamp: Date.now(),
        reminderTime: Date.now() + (2 * 60 * 60 * 1000) // Remind in 2 hours
    };

    reminders.push(reminder);
    localStorage.setItem('ratingReminders', JSON.stringify(reminders));

    dismissRatingNotification();

    // Show confirmation
    if (window.ratingSystem) {
        window.ratingSystem.showSuccessToast('Pengingat rating telah diatur untuk 2 jam ke depan');
    }
}

/**
 * Dismiss rating notification popup
 */
function dismissRatingNotification() {
    const popup = document.getElementById('ratingNotificationPopup');
    if (popup) {
        popup.remove();
    }
}

/**
 * Check for pending rating reminders
 */
function checkRatingReminders() {
    const reminders = JSON.parse(localStorage.getItem('ratingReminders') || '[]');
    const now = Date.now();
    const pendingReminders = [];
    const remainingReminders = [];

    reminders.forEach(reminder => {
        if (now >= reminder.reminderTime) {
            pendingReminders.push(reminder);
        } else {
            remainingReminders.push(reminder);
        }
    });

    // Update localStorage with remaining reminders
    localStorage.setItem('ratingReminders', JSON.stringify(remainingReminders));

    // Show pending reminders
    pendingReminders.forEach(reminder => {
        showRatingReminderToast(reminder.serviceId);
    });
}

/**
 * Show rating reminder toast
 */
function showRatingReminderToast(serviceId) {
    const toastHtml = `
        <div id="ratingReminderToast" class="fixed top-4 right-4 z-50 max-w-sm w-full bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-600 rounded-lg shadow-lg">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            Pengingat Rating Montir
                        </p>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            Jangan lupa kumpulkan rating untuk servis yang telah selesai.
                        </p>
                        <div class="mt-3 flex space-x-2">
                            <button onclick="openRatingModal(${serviceId}); document.getElementById('ratingReminderToast').remove();"
                                    class="text-xs bg-yellow-600 text-white px-2 py-1 rounded hover:bg-yellow-700">
                                Rating Sekarang
                            </button>
                            <button onclick="document.getElementById('ratingReminderToast').remove();"
                                    class="text-xs bg-gray-500 text-white px-2 py-1 rounded hover:bg-gray-600">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing toast if any
    const existingToast = document.getElementById('ratingReminderToast');
    if (existingToast) {
        existingToast.remove();
    }

    // Add toast to body
    document.body.insertAdjacentHTML('beforeend', toastHtml);

    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        const toast = document.getElementById('ratingReminderToast');
        if (toast) {
            toast.remove();
        }
    }, 10000);
}

/**
 * Check for session-based rating popup triggers
 */
function checkSessionRatingTriggers() {
    console.log('🔍 Checking for rating popup triggers...');

    // Check if there's a pending rating popup trigger
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
            console.log('📊 API Response data:', data);

            // Check for direct modal display (new behavior)
            if (data.show_direct_modal && data.service_data) {
                console.log('✅ Direct modal trigger detected! Service ID:', data.service_data.service_id);
                // Small delay to ensure page is fully loaded
                setTimeout(() => {
                    console.log('🚀 Opening rating modal directly...');
                    openRatingModalDirectly(data.service_data);
                }, 1500);
            }
            // Legacy support for notification popup
            else if (data.show_popup && data.service_data) {
                console.log('⚠️ Legacy popup trigger detected! Service ID:', data.service_data.service_id);
                // Small delay to ensure page is fully loaded
                setTimeout(() => {
                    showRatingNotificationPopup(data.service_data);
                }, 1500);
            }
            else {
                console.log('❌ No rating triggers found in response');
            }

            // Check for pending reminders
            if (data.reminders && data.reminders.length > 0) {
                console.log('⏰ Found pending reminders:', data.reminders.length);
                data.reminders.forEach(reminder => {
                    if (Date.now() >= reminder.remind_at * 1000) {
                        showRatingReminderToast(reminder.service_data.service_id);
                    }
                });
            }
        })
        .catch(error => {
            console.error('❌ Could not check rating popup triggers:', error);
        });
}

/**
 * Open rating modal directly without intermediate notification
 */
function openRatingModalDirectly(serviceData) {
    console.log('🎯 openRatingModalDirectly called with:', serviceData);
    console.log('🔧 Rating system available:', !!window.ratingSystem);

    // Ensure rating system is initialized
    if (window.ratingSystem) {
        console.log('✅ Rating system found, opening modal for service:', serviceData.service_id);
        // Open the rating modal directly
        window.ratingSystem.openRatingModal(serviceData.service_id);
    } else {
        console.log('⚠️ Rating system not initialized, setting up fallback...');
        // Fallback if rating system not initialized
        setTimeout(() => {
            console.log('🔄 Fallback attempt - Rating system available:', !!window.ratingSystem);
            if (window.ratingSystem) {
                console.log('✅ Rating system now available, opening modal');
                window.ratingSystem.openRatingModal(serviceData.service_id);
            } else {
                console.log('❌ Rating system still not available, showing notification popup as final fallback');
                // Final fallback - show notification popup
                showRatingNotificationPopup(serviceData);
            }
        }, 1000);
    }
}

/**
 * Enhanced rating notification popup with better UX
 */
function showEnhancedRatingNotification(serviceData) {
    // Use Filament's native notification system if available
    if (window.Filament && window.Filament.notifications) {
        window.Filament.notifications.send({
            title: '🎉 Servis Selesai - Kumpulkan Rating!',
            body: `Servis untuk ${serviceData.customer_name} telah selesai. Kumpulkan rating montir sekarang!`,
            color: 'success',
            duration: 15000,
            actions: [
                {
                    label: '⭐ Rating Sekarang',
                    action: () => openRatingModal(serviceData.service_id)
                },
                {
                    label: '⏰ Ingatkan Nanti',
                    action: () => remindRatingLater(serviceData.service_id)
                }
            ]
        });
    } else {
        // Fallback to custom popup
        showRatingNotificationPopup(serviceData);
    }
}

// Initialize the rating system when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Rating system initializing...');

    window.ratingSystem = new MechanicRatingSystem();
    console.log('✅ Rating system initialized:', !!window.ratingSystem);

    // Check for pending rating reminders every 5 minutes
    checkRatingReminders();
    setInterval(checkRatingReminders, 5 * 60 * 1000);

    // Check for session-based rating triggers more aggressively
    console.log('⏰ Setting up rating trigger checks...');
    setTimeout(checkSessionRatingTriggers, 1000);  // Reduced delay
    setTimeout(checkSessionRatingTriggers, 3000);  // Additional check
    setTimeout(checkSessionRatingTriggers, 5000);  // Another check

    // Also check when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            console.log('👁️ Page became visible, checking for rating triggers...');
            setTimeout(checkSessionRatingTriggers, 500);
        }
    });

    // Check periodically for the first minute after page load
    let checkCount = 0;
    const periodicCheck = setInterval(() => {
        checkCount++;
        console.log(`🔄 Periodic check #${checkCount}`);
        checkSessionRatingTriggers();

        if (checkCount >= 6) { // Stop after 6 checks (1 minute)
            clearInterval(periodicCheck);
            console.log('⏹️ Stopped periodic checking');
        }
    }, 10000); // Every 10 seconds
});
