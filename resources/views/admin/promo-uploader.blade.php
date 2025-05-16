<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Uploader - Hartono Motor</title>
    
    <!-- Tailwind CSS via Play CDN - for development only -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        // Configure Tailwind to match Filament's color scheme
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js - load only if not already loaded -->
    <script>
        if (typeof Alpine === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer><\/script>');
        }
    </script>
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <style>
        /* Basic styles to match Filament look and feel */
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            background-color: #f9fafb;
        }
        
        /* Custom button styles to match Filament */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: white;
            background-color: #f59e0b;
            border: 1px solid #d97706;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-primary:hover {
            background-color: #d97706;
            border-color: #b45309;
        }
        
        .btn-primary:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #f59e0b;
        }
    </style>
</head>
<body>
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">
                        Promo Uploader
                    </h1>
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="text-amber-600 hover:text-amber-800">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </header>
        
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <livewire:promo.promo-uploader />
                </div>
            </div>
        </main>
    </div>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Promo Upload Helper Script -->
    <script src="{{ asset('js/promo-upload-helper.js') }}"></script>
</body>
</html>
