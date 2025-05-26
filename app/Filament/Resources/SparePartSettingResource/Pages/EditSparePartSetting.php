<?php

namespace App\Filament\Resources\SparePartSettingResource\Pages;

use App\Filament\Resources\SparePartSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSparePartSetting extends EditRecord
{
    protected static string $resource = SparePartSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => !in_array($record->key, [
                    'pricing_notification_enabled',
                    'pricing_notification_title',
                    'pricing_notification_message',
                    'pricing_notification_cta_text',
                    'pricing_notification_whatsapp_number',
                    'pricing_notification_display_type',
                ])), // Prevent deletion of core settings
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
