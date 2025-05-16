<?php

namespace App\Providers;

use App\Livewire\Promo\PromoImageUploader;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('promo.promo-image-uploader', PromoImageUploader::class);

        // Register custom upload route for Livewire file uploads
        // We'll only handle file uploads, not other Livewire operations
        // This ensures we don't interfere with login or other functionality
    }
}
