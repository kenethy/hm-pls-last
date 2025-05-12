<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LivewireUploadFixServiceProvider extends ServiceProvider
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
        // Hapus route livewire.upload-file yang sudah ada
        $this->removeExistingUploadRoute();

        // Daftarkan ulang route dengan middleware yang benar
        Route::post('/livewire/upload-file', '\Livewire\Features\SupportFileUploads\FileUploadController@handle')
            ->middleware(['web'])
            ->name('livewire.upload-file');
    }

    /**
     * Hapus route livewire.upload-file yang sudah ada
     */
    protected function removeExistingUploadRoute(): void
    {
        $routes = Route::getRoutes();
        
        // Cari dan hapus route dengan nama 'livewire.upload-file'
        foreach ($routes as $route) {
            if ($route->getName() === 'livewire.upload-file') {
                $routes->refreshNameLookups();
                break;
            }
        }
    }
}
