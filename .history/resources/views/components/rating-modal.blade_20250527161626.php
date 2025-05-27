<!-- Rating Modal Component -->
<div id="ratingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 xl:w-2/5 shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-star text-yellow-500 mr-2"></i>
                Berikan Rating untuk Montir
            </h3>
            <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
                onclick="closeRatingModal()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Modal Content -->
        <div class="mt-4">
            <!-- Service Information -->
            <div id="serviceInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-blue-900 mb-2">Informasi Servis</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-blue-700 font-medium">Jenis Servis:</span>
                        <span id="serviceType" class="text-blue-800 ml-1"></span>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Tanggal:</span>
                        <span id="serviceDate" class="text-blue-800 ml-1"></span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-blue-700 font-medium">Kendaraan:</span>
                        <span id="vehicleInfo" class="text-blue-800 ml-1"></span>
                    </div>
                </div>
            </div>

            <!-- Mechanics Rating Section -->
            <div id="mechanicsContainer">
                <!-- Mechanics will be dynamically loaded here -->
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center py-8 hidden">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <p class="mt-2 text-gray-600">Memuat data montir...</p>
            </div>

            <!-- Error State -->
            <div id="errorState" class="text-center py-8 hidden">
                <div class="text-red-500 mb-2">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <p class="text-red-600">Terjadi kesalahan saat memuat data</p>
                <button onclick="retryLoadRatingModal()"
                    class="mt-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                    Coba Lagi
                </button>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <div class="flex space-x-3">
                <button type="button" onclick="remindRatingLaterFromModal()"
                    class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors duration-200">
                    <i class="fas fa-clock mr-2"></i>
                    Ingatkan Nanti
                </button>
            </div>
            <div class="flex space-x-3">
                <button type="button" onclick="closeRatingModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                    Tutup
                </button>
                <button type="button" id="submitAllRatings" onclick="submitAllRatings()"
                    class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors duration-200 hidden">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Kirim Semua Rating
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Individual Mechanic Rating Template -->
<template id="mechanicRatingTemplate">
    <div class="mechanic-rating-card bg-white border border-gray-200 rounded-lg p-4 mb-4 shadow-sm">
        <!-- Mechanic Info -->
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-user-cog text-gray-600 text-lg"></i>
            </div>
            <div>
                <h5 class="font-medium text-gray-900 mechanic-name"></h5>
                <p class="text-sm text-gray-600 mechanic-specialization"></p>
            </div>
            <div class="ml-auto">
                <span class="rating-status-badge px-2 py-1 text-xs rounded-full"></span>
            </div>
        </div>

        <!-- Rating Section -->
        <div class="rating-section">
            <!-- Star Rating -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating (1-5 bintang)</label>
                <div class="star-rating flex space-x-1" data-mechanic-id="">
                    <button type="button"
                        class="star text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-150"
                        data-rating="1">
                        <i class="fas fa-star"></i>
                    </button>
                    <button type="button"
                        class="star text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-150"
                        data-rating="2">
                        <i class="fas fa-star"></i>
                    </button>
                    <button type="button"
                        class="star text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-150"
                        data-rating="3">
                        <i class="fas fa-star"></i>
                    </button>
                    <button type="button"
                        class="star text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-150"
                        data-rating="4">
                        <i class="fas fa-star"></i>
                    </button>
                    <button type="button"
                        class="star text-2xl text-gray-300 hover:text-yellow-400 transition-colors duration-150"
                        data-rating="5">
                        <i class="fas fa-star"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Klik bintang untuk memberikan rating</p>
            </div>

            <!-- Comment Section -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Komentar (Opsional)
                </label>
                <textarea
                    class="rating-comment w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                    rows="3" placeholder="Bagikan pengalaman Anda dengan montir ini..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Maksimal 1000 karakter</p>
            </div>

            <!-- Submit Button for Individual Rating -->
            <div class="flex justify-end">
                <button type="button"
                    class="submit-individual-rating px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    data-mechanic-id="" disabled>
                    <i class="fas fa-check mr-2"></i>
                    Kirim Rating
                </button>
            </div>
        </div>

        <!-- Already Rated Section -->
        <div class="already-rated-section hidden">
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span class="text-green-700 font-medium">Sudah Diberi Rating</span>
                </div>
                <div class="mt-2">
                    <div class="existing-stars flex space-x-1 mb-2"></div>
                    <p class="existing-comment text-sm text-green-700"></p>
                    <p class="existing-date text-xs text-green-600"></p>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <div class="success-message hidden bg-green-50 border border-green-200 rounded-lg p-3 mt-3">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <span class="text-green-700 font-medium">Rating berhasil dikirim!</span>
            </div>
        </div>

        <!-- Error Message -->
        <div class="error-message hidden bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <span class="text-red-700 font-medium error-text">Terjadi kesalahan</span>
            </div>
        </div>
    </div>
</template>

<!-- Success Toast Notification -->
<div id="successToast"
    class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 hidden transform transition-transform duration-300">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="successMessage">Rating berhasil dikirim!</span>
    </div>
</div>

<!-- Error Toast Notification -->
<div id="errorToast"
    class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 hidden transform transition-transform duration-300">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span id="errorMessage">Terjadi kesalahan!</span>
    </div>
</div>