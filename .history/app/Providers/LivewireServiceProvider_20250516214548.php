<?php

namespace App\Providers;

use App\Livewire\Promo\PromoImageUploader;
use Illuminate\Support\Facades\Route;
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

        // Configure Livewire routes to work in Docker environment
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web']);
        });

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/livewire/livewire.js', $handle)
                ->middleware(['web']);
        });

        // Add custom route for file uploads
        Route::post('/livewire/upload', function () {
            return app(\Livewire\Features\SupportFileUploads\FileUploadController::class)->handle();
        })->middleware(['web', 'auth'])->name('livewire.upload');
    }
}
