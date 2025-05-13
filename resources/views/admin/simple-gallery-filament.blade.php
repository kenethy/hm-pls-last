<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold">Simple Gallery Manager</h2>
                <div>
                    <a href="{{ route('filament.admin.resources.galleries.index') }}" class="text-primary-600 hover:text-primary-900">
                        Kembali ke Daftar Galeri
                    </a>
                </div>
            </div>
        </x-slot>
        
        <!-- Tabs for Upload and Manage -->
        <div x-data="{ activeTab: 'upload' }">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button 
                        @click="activeTab = 'upload'" 
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'upload', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'upload' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Upload Gambar
                    </button>
                    <button 
                        @click="activeTab = 'manage'" 
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'manage', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'manage' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Kelola Galeri
                    </button>
                </nav>
            </div>
            
            <div class="mt-4">
                <!-- Upload Tab -->
                <div x-show="activeTab === 'upload'">
                    <livewire:gallery.simple-gallery-uploader />
                </div>
                
                <!-- Manage Tab -->
                <div x-show="activeTab === 'manage'">
                    <livewire:gallery.gallery-manager />
                </div>
            </div>
        </div>
    </x-filament::section>
    
    <!-- Gallery Upload Helper Script -->
    <script src="{{ asset('js/gallery-upload-helper.js') }}"></script>
</x-filament-panels::page>
