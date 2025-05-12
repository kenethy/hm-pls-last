<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Filament\Widgets\GalleryLivewireWidget;
use Filament\Resources\Pages\CreateRecord;

class CreateGallery extends CreateRecord
{
    protected static string $resource = GalleryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            GalleryLivewireWidget::class,
        ];
    }
}
