<?php

namespace App\Filament\Resources\SparePartCategoryResource\Pages;

use App\Filament\Resources\SparePartCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSparePartCategory extends CreateRecord
{
    protected static string $resource = SparePartCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
