<?php

namespace App\Filament\Resources\WhatsAppConfigResource\Pages;

use App\Filament\Resources\WhatsAppConfigResource;
use App\Models\WhatsAppConfig;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppConfig extends CreateRecord
{
    protected static string $resource = WhatsAppConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If this config is set to active, deactivate all others
        if ($data['is_active'] ?? false) {
            WhatsAppConfig::where('is_active', true)->update(['is_active' => false]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
