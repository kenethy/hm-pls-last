<?php

namespace App\Filament\Resources\SparePartSettingResource\Pages;

use App\Filament\Resources\SparePartSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSparePartSetting extends CreateRecord
{
    protected static string $resource = SparePartSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
