<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Livewire\Gallery\GalleryUploader;
use App\Livewire\Gallery\GalleryManager;
use App\Livewire\Gallery\SimpleGalleryUploader;
use App\Livewire\Promo\PromoUploader;

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
        Livewire::component('gallery.gallery-uploader', GalleryUploader::class);
        Livewire::component('gallery.gallery-manager', GalleryManager::class);
        Livewire::component('gallery.simple-gallery-uploader', SimpleGalleryUploader::class);
    }
}
