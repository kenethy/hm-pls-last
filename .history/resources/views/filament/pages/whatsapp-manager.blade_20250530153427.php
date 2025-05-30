<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Session Status Card -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Status Koneksi WhatsApp
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Status sesi WhatsApp saat ini
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $this->getSessionStatusColor() === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                            <svg class="-ml-0.5 mr-1.5 h-2 w-2 {{ $this->getSessionStatusColor() === 'success' ? 'text-green-400' : 'text-red-400' }}"
                                fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            {{ $this->getSessionStatusText() }}
                        </span>
                    </div>
                </div>

                @if($sessionStatus)
                <div class="mt-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        <strong>Detail Status:</strong>
                        <pre
                            class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded text-xs overflow-auto">{{ json_encode($sessionStatus, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Debug Info (Temporary) -->
        <div
            class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Debug Info:</h4>
            <div class="text-xs text-yellow-700 dark:text-yellow-300 space-y-1">
                <div><strong>isConnected:</strong> {{ $isConnected ? 'true' : 'false' }}</div>
                <div><strong>qrCode exists:</strong> {{ $qrCode ? 'true' : 'false' }}</div>
                <div><strong>qrCode data:</strong> {{ $qrCode ? json_encode($qrCode) : 'null' }}</div>
                <div><strong>getQRCodeUrl():</strong> {{ $this->getQRCodeUrl() ? 'has URL' : 'no URL' }}</div>
            </div>
        </div>

        <!-- QR Code Section -->
        @if(!$isConnected)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        Scan QR Code untuk Menghubungkan WhatsApp
                    </h3>

                    @if($qrCode && $this->getQRCodeUrl())
                    <div class="flex justify-center mb-4">
                        <img src="{{ $this->getQRCodeUrl() }}" alt="WhatsApp QR Code"
                            class="border border-gray-300 dark:border-gray-600 rounded-lg"
                            style="max-width: 300px; max-height: 300px;">
                    </div>
                    @else
                    <div class="flex justify-center mb-4">
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    QR Code belum tersedia.<br>
                                    Klik "Start Session" atau "Get QR Code" untuk memuat QR code.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                        <p><strong>Langkah-langkah:</strong></p>
                        <ol class="list-decimal list-inside space-y-1 text-left max-w-md mx-auto">
                            <li>Buka WhatsApp di ponsel Anda</li>
                            <li>Tap Menu (⋮) atau Settings</li>
                            <li>Pilih "Linked Devices" atau "WhatsApp Web"</li>
                            <li>Tap "Link a Device"</li>
                            <li>Scan QR code di atas</li>
                        </ol>
                    </div>

                    <div class="mt-4">
                        <button type="button" wire:click="checkSessionStatus"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Refresh Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Connected Status -->
        @if($isConnected)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center">
                    <div
                        class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-800 mb-4">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-200" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-2">
                        WhatsApp Terhubung!
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Sistem WhatsApp sudah terhubung dan siap mengirim pesan otomatis.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                            <div class="text-blue-600 dark:text-blue-200 text-sm font-medium">Status</div>
                            <div class="text-blue-900 dark:text-blue-100 text-lg font-semibold">Aktif</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                            <div class="text-green-600 dark:text-green-200 text-sm font-medium">Siap Kirim</div>
                            <div class="text-green-900 dark:text-green-100 text-lg font-semibold">Ya</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                            <div class="text-purple-600 dark:text-purple-200 text-sm font-medium">Auto Follow-up</div>
                            <div class="text-purple-900 dark:text-purple-100 text-lg font-semibold">Tersedia</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Instructions Card -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    Panduan Penggunaan
                </h3>

                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 text-xs font-medium">1</span>
                        </div>
                        <div>
                            <p><strong>Mulai Sesi:</strong> Klik tombol "Start Session" untuk memulai koneksi WhatsApp.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 text-xs font-medium">2</span>
                        </div>
                        <div>
                            <p><strong>Scan QR Code:</strong> Gunakan WhatsApp di ponsel untuk scan QR code yang muncul.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 text-xs font-medium">3</span>
                        </div>
                        <div>
                            <p><strong>Test Pesan:</strong> Setelah terhubung, gunakan "Test Message" untuk menguji
                                pengiriman.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-200 text-xs font-medium">4</span>
                        </div>
                        <div>
                            <p><strong>Follow-up Otomatis:</strong> Gunakan "Send Follow-up Messages" untuk mengirim
                                pesan follow-up ke pelanggan.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Penting!
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Pastikan ponsel tetap terhubung internet</li>
                                    <li>Jangan logout dari WhatsApp Web di ponsel</li>
                                    <li>Sesi akan otomatis terputus jika tidak aktif</li>
                                    <li>Gunakan fitur test message sebelum mengirim follow-up massal</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>