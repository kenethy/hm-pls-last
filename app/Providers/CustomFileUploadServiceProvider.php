<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Config;

class CustomFileUploadServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Tidak perlu mendaftarkan layanan tambahan
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Daftarkan komponen Blade
        Blade::component('custom-file-upload', \App\View\Components\CustomFileUpload::class);
        
        // Ubah konfigurasi Livewire untuk file upload
        Config::set('livewire.temporary_file_upload.middleware', ['web', 'auth']);
    }
}
