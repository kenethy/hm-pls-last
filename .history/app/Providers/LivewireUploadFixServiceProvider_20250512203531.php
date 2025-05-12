<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class LivewireUploadFixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override konfigurasi middleware Livewire file upload
        config(['livewire.temporary_file_upload.middleware' => ['web', 'auth']]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Tidak perlu melakukan apa-apa di sini
    }
}
