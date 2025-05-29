<?php

namespace App\Filament\Resources\WhatsAppConfigResource\Pages;

use App\Filament\Resources\WhatsAppConfigResource;
use App\Models\WhatsAppConfig;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppConfig extends EditRecord
{
    protected static string $resource = WhatsAppConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('test_connection')
                ->label('Test Koneksi')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $service = new WhatsAppService();
                    $result = $service->testConnection();

                    if ($result['success']) {
                        Notification::make()
                            ->title('Koneksi Berhasil')
                            ->success()
                            ->body($result['message'])
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Koneksi Gagal')
                            ->danger()
                            ->body($result['message'])
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this config is set to active, deactivate all others
        if ($data['is_active'] ?? false) {
            WhatsAppConfig::where('id', '!=', $this->record->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
