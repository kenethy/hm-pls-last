<?php

namespace App\Filament\Resources\SparePartCategoryResource\Pages;

use App\Filament\Resources\SparePartCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSparePartCategories extends ListRecords
{
    protected static string $resource = SparePartCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
