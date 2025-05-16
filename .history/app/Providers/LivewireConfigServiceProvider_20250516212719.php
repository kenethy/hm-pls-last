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
        
        // Set the update route for Livewire
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web']);
        });
        
        // Set the script route for Livewire
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/livewire/livewire.js', $handle)
                ->middleware(['web']);
        });
        
        // Set the upload route for Livewire
        Livewire::setUploadRoute(function ($handle) {
            return Route::post('/livewire/upload', $handle)
                ->middleware(['web', 'auth']);
        });
        
        // Set the upload cleanup route for Livewire
        Livewire::setUploadCleanupRoute(function ($handle) {
            return Route::post('/livewire/upload-cleanup', $handle)
                ->middleware(['web', 'auth']);
        });
    }
}
