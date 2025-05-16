<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireConfigServiceProvider extends ServiceProvider
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
        // Configure Livewire routes to work in Docker environment

        // In Livewire 3, we need to use the correct methods
        // These are the available methods in Livewire 3
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web']);
        });

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/livewire/livewire.js', $handle)
                ->middleware(['web']);
        });

        // Add custom routes for file uploads
        // These will be used by Filament's FileUpload component
        Route::post('/livewire/upload', function () {
            return app(\Livewire\Features\SupportFileUploads\FileUploadController::class)->handle();
        })->middleware(['web', 'auth'])->name('livewire.upload');

        Route::post('/livewire/upload-cleanup', function () {
            return app(\Livewire\Features\SupportFileUploads\FileUploadController::class)->cleanup();
        })->middleware(['web', 'auth'])->name('livewire.upload-cleanup');
    }
}
