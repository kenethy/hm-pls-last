<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GalleryUploadWidget extends Widget
{
    protected static string $view = 'filament.widgets.gallery-upload-widget';
    
    // Widget dapat digunakan di halaman manapun
    public static function canView(): bool
    {
        return true;
    }
}
