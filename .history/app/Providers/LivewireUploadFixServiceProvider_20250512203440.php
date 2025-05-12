<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
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
        // Tunggu sampai semua service provider lain di-boot
        $this->app->booted(function () {
            // Tambahkan middleware auth ke route livewire.upload-file
            $router = app('router');
            $routes = $router->getRoutes();

            // Cari route dengan nama 'livewire.upload-file'
            foreach ($routes as $route) {
                if ($route->getName() === 'livewire.upload-file') {
                    // Tambahkan middleware auth ke route ini
                    $route->middleware(['auth']);
                    break;
                }
            }
        });
    }
}
