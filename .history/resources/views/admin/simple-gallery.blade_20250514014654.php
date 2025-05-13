<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Gallery Manager - Hartono Motor</title>

    <!-- Use Filament styles -->
    <style>
        /* Ensure we have basic styling even if Filament styles aren't loaded */
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
        }
    </style>

    <!-- We'll use Alpine.js that's already loaded by Filament -->

    <!-- Livewire Styles -->
    @livewireStyles
</head>

<body class="bg-gray-100">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">
                        Simple Gallery Manager
                    </h1>
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="text-blue-600 hover:text-blue-800">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Tabs for Upload and Manage -->
                    <div x-data="{ activeTab: 'upload' }">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button @click="activeTab = 'upload'"
                                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'upload', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'upload' }"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Upload Gambar
                                </button>
                                <button @click="activeTab = 'manage'"
                                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'manage', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'manage' }"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
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
                </div>
            </div>
        </main>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Gallery Upload Helper Script -->
    <script src="{{ asset('js/gallery-upload-helper.js') }}"></script>
</body>

</html>