<!-- Filament-Native Rating Modal Component -->
<div id="ratingModal" class="fi-modal inline-block" x-data="{ isOpen: false }" x-show="isOpen" x-cloak>
    <!-- Modal Overlay -->
    <div x-show="isOpen" x-transition.duration.300ms.opacity
        class="fi-modal-close-overlay fixed inset-0 z-40 bg-gray-950/50 dark:bg-gray-950/75"></div>

    <!-- Modal Container -->
    <div class="fixed inset-0 z-40 overflow-y-auto cursor-pointer">
        <div class="relative grid min-h-full grid-rows-[1fr_auto_1fr] justify-items-center sm:grid-rows-[1fr_auto_3fr] p-4"
            x-on:click.self="if (window.filamentRatingSystem) { window.filamentRatingSystem.closeModal(); }">

            <!-- Modal Window -->
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="fi-modal-window pointer-events-auto relative row-start-2 flex w-full cursor-default flex-col bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mx-auto rounded-xl max-w-2xl">

                <!-- Modal Header -->
                <div class="fi-modal-header flex flex-col gap-y-4 py-6 px-6">
                    <div class="flex items-center gap-x-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/20">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div class="grid flex-1 gap-y-1">
                            <h2
                                class="fi-modal-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                Berikan Rating untuk Montir
                            </h2>
                            <p class="fi-modal-description text-sm text-gray-500 dark:text-gray-400">
                                Kumpulkan feedback pelanggan untuk meningkatkan kualitas layanan
                            </p>
                        </div>
                        <button type="button"
                            onclick="if (window.filamentRatingSystem) { window.filamentRatingSystem.closeModal(); }"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid fi-btn-outlined ring-1 text-gray-950 ring-gray-300 hover:bg-gray-400/10 focus-visible:ring-gray-400/40 dark:text-white dark:ring-gray-700">
                            <svg class="fi-btn-icon transition duration-75 h-4 w-4 text-gray-400 dark:text-gray-500"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="fi-modal-content flex flex-col gap-y-4 py-6 px-6 flex-1">
                    <!-- Service Information Card - Minimalis -->
                    <div id="serviceInfo" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                <span id="serviceType"></span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span id="vehicleInfo"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Mechanics Rating Section -->
                    <div id="mechanicsContainer" class="space-y-4">
                        <!-- Mechanics will be dynamically loaded here -->
                    </div>

                    <!-- Loading State -->
                    <div id="loadingState" class="hidden">
                        <div
                            class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <div class="fi-section-content p-6">
                                <div class="flex items-center justify-center py-8">
                                    <div class="flex items-center gap-x-3">
                                        <svg class="h-5 w-5 animate-spin text-gray-500 dark:text-gray-400" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Memuat data
                                            montir...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div id="errorState" class="hidden">
                        <div
                            class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <div class="fi-section-content p-6">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20 mb-4">
                                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                            </path>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400 mb-3">Terjadi kesalahan
                                        saat memuat data</p>
                                    <button onclick="retryLoadRatingModal()"
                                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-danger fi-btn-color-danger fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid text-white bg-red-600 hover:bg-red-500 focus-visible:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-400">
                                        Coba Lagi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="fi-modal-footer flex flex-wrap items-center gap-x-3 px-6 py-4 bg-gray-50 dark:bg-white/5">
                    <button type="button" onclick="remindRatingLaterFromModal()"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-warning fi-btn-color-warning fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid text-white bg-amber-600 hover:bg-amber-500 focus-visible:ring-amber-500/50 dark:bg-amber-500 dark:hover:bg-amber-400">
                        <svg class="fi-btn-icon transition duration-75 h-4 w-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ingatkan Nanti
                    </button>

                    <div class="ms-auto flex items-center gap-x-3">
                        <button type="button"
                            onclick="if (window.filamentRatingSystem) { window.filamentRatingSystem.closeModal(); }"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid fi-btn-outlined ring-1 text-gray-950 ring-gray-300 hover:bg-gray-400/10 focus-visible:ring-gray-400/40 dark:text-white dark:ring-gray-700">
                            Tutup
                        </button>
                        <button type="button" id="submitAllRatings" onclick="submitAllRatings()"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-primary fi-btn-color-primary fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid text-white bg-amber-600 hover:bg-amber-500 focus-visible:ring-amber-500/50 dark:bg-amber-500 dark:hover:bg-amber-400 hidden">
                            <svg class="fi-btn-icon transition duration-75 h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Kirim Semua Rating
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Individual Mechanic Rating Template -->
<template id="mechanicRatingTemplate">
    <div
        class="mechanic-rating-card fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="fi-section-content p-6">
            <!-- Mechanic Header -->
            <div class="flex items-center gap-x-3 mb-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="mechanic-name text-sm font-semibold leading-6 text-gray-950 dark:text-white"></h4>
                    <p class="mechanic-specialization text-xs text-gray-500 dark:text-gray-400"></p>
                </div>
                <div
                    class="rating-status-badge fi-badge inline-flex items-center justify-center whitespace-nowrap rounded-md border px-2 py-1 text-xs font-medium ring-1 ring-inset transition duration-75">
                </div>
            </div>

            <!-- Rating Section -->
            <div class="rating-section">
                <!-- Star Rating -->
                <div class="fi-fo-field-wrp mb-4">
                    <div class="fi-fo-field-wrp-label mb-2">
                        <label
                            class="fi-fo-field-wrp-label-text text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Rating (1-5 bintang)
                        </label>
                    </div>
                    <div class="star-rating flex items-center gap-x-1" data-mechanic-id="">
                        <button type="button"
                            class="star transition duration-150 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded"
                            data-rating="1">
                            <svg class="h-6 w-6 text-gray-300 hover:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                        <button type="button"
                            class="star transition duration-150 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded"
                            data-rating="2">
                            <svg class="h-6 w-6 text-gray-300 hover:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                        <button type="button"
                            class="star transition duration-150 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded"
                            data-rating="3">
                            <svg class="h-6 w-6 text-gray-300 hover:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                        <button type="button"
                            class="star transition duration-150 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded"
                            data-rating="4">
                            <svg class="h-6 w-6 text-gray-300 hover:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                        <button type="button"
                            class="star transition duration-150 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded"
                            data-rating="5">
                            <svg class="h-6 w-6 text-gray-300 hover:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                    </div>
                    <p class="fi-fo-field-wrp-hint mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Klik bintang untuk memberikan rating
                    </p>
                </div>

                <!-- Comment Section -->
                <div class="fi-fo-field-wrp mb-4">
                    <div class="fi-fo-field-wrp-label mb-2">
                        <label
                            class="fi-fo-field-wrp-label-text text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Komentar (Opsional)
                        </label>
                    </div>
                    <textarea
                        class="rating-comment fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white dark:bg-white/5 [&:not(:focus)]:shadow-sm [&:not(:focus)]:ring-1 [&:not(:focus)]:ring-gray-950/10 [&:not(:focus)]:dark:ring-white/20 [&:not(:focus)]:rounded-lg focus:ring-2 focus:ring-amber-600 focus:dark:ring-amber-500 disabled:ring-gray-200 disabled:dark:ring-gray-700 disabled:dark:bg-gray-800 rounded-lg resize-none"
                        rows="3" placeholder="Bagikan pengalaman Anda dengan montir ini..."></textarea>
                    <p class="fi-fo-field-wrp-hint mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Maksimal 1000 karakter
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="button"
                        class="submit-individual-rating fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-success fi-btn-color-success fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid text-white bg-green-600 hover:bg-green-500 focus-visible:ring-green-500/50 dark:bg-green-500 dark:hover:bg-green-400 disabled:opacity-50 disabled:cursor-not-allowed"
                        data-mechanic-id="" disabled>
                        <svg class="fi-btn-icon transition duration-75 h-4 w-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Kirim Rating
                    </button>
                </div>
            </div>

            <!-- Already Rated Section -->
            <div class="already-rated-section hidden">
                <div
                    class="fi-section-content-ctn rounded-lg bg-green-50 ring-1 ring-green-600/10 dark:bg-green-400/10 dark:ring-green-400/30 p-4">
                    <div class="flex items-center gap-x-3 mb-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/20">
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">Sudah Diberi Rating</span>
                    </div>
                    <div class="existing-stars flex items-center gap-x-1 mb-2"></div>
                    <p class="existing-comment text-sm text-green-700 dark:text-green-300 mb-1"></p>
                    <p class="existing-date text-xs text-green-600 dark:text-green-400"></p>
                </div>
            </div>

            <!-- Success Message -->
            <div class="success-message hidden mt-4">
                <div
                    class="fi-section-content-ctn rounded-lg bg-green-50 ring-1 ring-green-600/10 dark:bg-green-400/10 dark:ring-green-400/30 p-4">
                    <div class="flex items-center gap-x-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/20">
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">Rating berhasil
                            dikirim!</span>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div class="error-message hidden mt-4">
                <div
                    class="fi-section-content-ctn rounded-lg bg-red-50 ring-1 ring-red-600/10 dark:bg-red-400/10 dark:ring-red-400/30 p-4">
                    <div class="flex items-center gap-x-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20">
                            <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                        </div>
                        <span class="error-text text-sm font-medium text-red-700 dark:text-red-400">Terjadi
                            kesalahan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Toast Notifications -->
<div id="successToast" class="fixed top-4 right-4 z-50 hidden transform transition-transform duration-300">
    <div
        class="fi-no-notification-ctn pointer-events-auto w-80 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/20">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p id="successMessage" class="text-sm font-medium text-gray-900 dark:text-white">Rating berhasil
                        dikirim!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="errorToast" class="fixed top-4 right-4 z-50 hidden transform transition-transform duration-300">
    <div
        class="fi-no-notification-ctn pointer-events-auto w-80 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20">
                        <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p id="errorMessage" class="text-sm font-medium text-gray-900 dark:text-white">Terjadi kesalahan!
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>