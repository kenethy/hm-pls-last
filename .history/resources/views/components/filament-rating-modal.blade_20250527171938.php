<!-- Filament-Native Rating Modal Component -->
<div id="ratingModal" class="fi-modal inline-block" x-data="{ isOpen: false }" x-show="isOpen" x-cloak>
    <!-- Modal Overlay -->
    <div x-show="isOpen" x-transition.duration.300ms.opacity 
         class="fi-modal-close-overlay fixed inset-0 z-40 bg-gray-950/50 dark:bg-gray-950/75"></div>
    
    <!-- Modal Container -->
    <div class="fixed inset-0 z-40 overflow-y-auto cursor-pointer">
        <div class="relative grid min-h-full grid-rows-[1fr_auto_1fr] justify-items-center sm:grid-rows-[1fr_auto_3fr] p-4"
             x-on:click.self="closeRatingModal()">
            
            <!-- Modal Window -->
            <div x-show="isOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="fi-modal-window pointer-events-auto relative row-start-2 flex w-full cursor-default flex-col bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mx-auto rounded-xl max-w-2xl">
                
                <!-- Modal Header -->
                <div class="fi-modal-header flex flex-col gap-y-4 py-6 px-6">
                    <div class="flex items-center gap-x-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/20">
                            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div class="grid flex-1 gap-y-1">
                            <h2 class="fi-modal-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                Berikan Rating untuk Montir
                            </h2>
                            <p class="fi-modal-description text-sm text-gray-500 dark:text-gray-400">
                                Kumpulkan feedback pelanggan untuk meningkatkan kualitas layanan
                            </p>
                        </div>
                        <button type="button" 
                                onclick="closeRatingModal()"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid fi-btn-outlined ring-1 text-gray-950 ring-gray-300 hover:bg-gray-400/10 focus-visible:ring-gray-400/40 dark:text-white dark:ring-gray-700">
                            <svg class="fi-btn-icon transition duration-75 h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="fi-modal-content flex flex-col gap-y-4 py-6 px-6 flex-1">
                    <!-- Service Information Card -->
                    <div id="serviceInfo" class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <div class="fi-section-content p-6">
                            <div class="fi-section-header-ctn flex items-center gap-x-3">
                                <div class="fi-section-header-icon flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/20">
                                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                    Informasi Servis
                                </h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">
                                <div class="fi-fo-field-wrp">
                                    <div class="fi-fo-field-wrp-label">
                                        <label class="fi-fo-field-wrp-label-text text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                            Jenis Servis
                                        </label>
                                    </div>
                                    <div class="fi-fo-field-wrp-hint">
                                        <span id="serviceType" class="text-sm text-gray-500 dark:text-gray-400"></span>
                                    </div>
                                </div>
                                <div class="fi-fo-field-wrp">
                                    <div class="fi-fo-field-wrp-label">
                                        <label class="fi-fo-field-wrp-label-text text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                            Tanggal Servis
                                        </label>
                                    </div>
                                    <div class="fi-fo-field-wrp-hint">
                                        <span id="serviceDate" class="text-sm text-gray-500 dark:text-gray-400"></span>
                                    </div>
                                </div>
                                <div class="fi-fo-field-wrp sm:col-span-2">
                                    <div class="fi-fo-field-wrp-label">
                                        <label class="fi-fo-field-wrp-label-text text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                            Kendaraan
                                        </label>
                                    </div>
                                    <div class="fi-fo-field-wrp-hint">
                                        <span id="vehicleInfo" class="text-sm text-gray-500 dark:text-gray-400"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mechanics Rating Section -->
                    <div id="mechanicsContainer" class="space-y-4">
                        <!-- Mechanics will be dynamically loaded here -->
                    </div>

                    <!-- Loading State -->
                    <div id="loadingState" class="hidden">
                        <div class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <div class="fi-section-content p-6">
                                <div class="flex items-center justify-center py-8">
                                    <div class="flex items-center gap-x-3">
                                        <svg class="h-5 w-5 animate-spin text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Memuat data montir...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div id="errorState" class="hidden">
                        <div class="fi-section-content-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                            <div class="fi-section-content p-6">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/20 mb-4">
                                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400 mb-3">Terjadi kesalahan saat memuat data</p>
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
                    <button type="button" 
                            onclick="remindRatingLaterFromModal()"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-warning fi-btn-color-warning fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid text-white bg-amber-600 hover:bg-amber-500 focus-visible:ring-amber-500/50 dark:bg-amber-500 dark:hover:bg-amber-400">
                        <svg class="fi-btn-icon transition duration-75 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ingatkan Nanti
                    </button>
                    
                    <div class="ms-auto flex items-center gap-x-3">
                        <button type="button" 
                                onclick="closeRatingModal()"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid fi-btn-outlined ring-1 text-gray-950 ring-gray-300 hover:bg-gray-400/10 focus-visible:ring-gray-400/40 dark:text-white dark:ring-gray-700">
                            Tutup
                        </button>
                        <button type="button" 
                                id="submitAllRatings" 
                                onclick="submitAllRatings()"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-primary fi-btn-color-primary fi-size-sm fi-btn-size-sm gap-1 px-2.5 py-1.5 text-sm inline-grid text-white bg-amber-600 hover:bg-amber-500 focus-visible:ring-amber-500/50 dark:bg-amber-500 dark:hover:bg-amber-400 hidden">
                            <svg class="fi-btn-icon transition duration-75 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Kirim Semua Rating
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
