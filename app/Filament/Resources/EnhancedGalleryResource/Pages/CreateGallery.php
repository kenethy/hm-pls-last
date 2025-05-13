<?php

namespace App\Filament\Resources\EnhancedGalleryResource\Pages;

use App\Filament\Resources\EnhancedGalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGallery extends CreateRecord
{
    protected static string $resource = EnhancedGalleryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
