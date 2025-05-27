<!-- Filament-Native Rating Content -->
<div class="space-y-4">
    <!-- Service Info -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="font-medium text-gray-900 dark:text-white">{{ $service->service_type }}</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $service->license_plate }} - {{ $service->car_model }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">Pelanggan: {{ $service->customer_name }}</p>
    </div>

    <!-- Mechanics Rating -->
    @foreach($mechanics as $mechanic)
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-4">
        <!-- Mechanic Info -->
        <div class="flex justify-between items-center">
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white">{{ $mechanic->name }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $mechanic->specialization ?? 'Montir Umum' }}</p>
            </div>
            <span id="status-{{ $mechanic->id }}"
                class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                Belum Rating
            </span>
        </div>

        <!-- Star Rating -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Berikan Rating:
            </label>
            <div class="flex space-x-1" id="stars-{{ $mechanic->id }}">
                @for($i = 1; $i <= 5; $i++) <button type="button"
                    onclick="console.log('⭐ Star clicked: mechanic {{ $mechanic->id }}, rating {{ $i }}'); setRating({{ $mechanic->id }}, {{ $i }}); return false;"
                    class="text-gray-300 hover:text-amber-400 transition-colors duration-200 cursor-pointer"
                    id="star-{{ $mechanic->id }}-{{ $i }}"
                    style="background: none; border: none; padding: 2px; color: #d1d5db;">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    </button>
                    @endfor
            </div>

            <!-- Debug Info -->
            <div class="text-xs text-gray-500 mt-1">
                Debug: Mechanic ID {{ $mechanic->id }} - Click stars above to test
            </div>
        </div>

        <!-- Submit Button -->
        <button type="button" id="submit-{{ $mechanic->id }}"
            onclick="submitRating({{ $service->id }}, {{ $mechanic->id }})" disabled
            class="w-full px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed transition-colors duration-200">
            Kirim Rating
        </button>

        <!-- Success Message -->
        <div id="success-{{ $mechanic->id }}" class="hidden bg-green-50 border border-green-200 rounded-lg p-3">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-green-800 text-sm font-medium">Rating berhasil dikirim!</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- JavaScript moved to global scope in AdminPanelProvider -->