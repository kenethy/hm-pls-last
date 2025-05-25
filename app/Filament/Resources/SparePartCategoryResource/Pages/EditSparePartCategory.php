<?php

namespace App\Filament\Resources\SparePartCategoryResource\Pages;

use App\Filament\Resources\SparePartCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSparePartCategory extends EditRecord
{
    protected static string $resource = SparePartCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
