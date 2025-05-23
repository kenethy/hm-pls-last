<?php

namespace App\Providers;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
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
        // Configure Filament's FileUpload component with increased limits
        FileUpload::configureUsing(function (FileUpload $fileUpload): void {
            $fileUpload
                ->disk('public')
                ->visibility('public')
                ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])
                ->maxSize(10240) // Increased to 10MB
                ->uploadProgressIndicatorPosition('left')
                ->panelLayout('integrated');
        });
    }
}
