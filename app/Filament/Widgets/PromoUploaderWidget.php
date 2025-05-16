<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PromoUploaderWidget extends Widget
{
    protected static string $view = 'filament.widgets.promo-uploader-widget';
    
    // Make this widget full-width
    protected int | string | array $columnSpan = 'full';
    
    // Widget can be used on any page
    public static function canView(): bool
    {
        return true;
    }
}
