<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Gallery;
use App\Models\GalleryCategory;

class EnhancedGalleryWidget extends Widget
{
    protected static string $view = 'filament.widgets.enhanced-gallery-widget';

    // Make this widget full-width
    protected int | string | array $columnSpan = 'full';

    // Widget can be used on any page
    public static function canView(): bool
    {
        return true;
    }

    public function getGalleryStats()
    {
        return [
            'total' => Gallery::count(),
            'featured' => Gallery::where('is_featured', true)->count(),
            'categories' => GalleryCategory::count(),
            'recent' => Gallery::latest()->take(5)->get(),
            'popular_categories' => GalleryCategory::withCount('galleryItems')
                ->orderBy('gallery_items_count', 'desc')
                ->take(5)
                ->get(),
        ];
    }
}
