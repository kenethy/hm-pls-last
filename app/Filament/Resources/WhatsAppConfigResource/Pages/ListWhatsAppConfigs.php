<?php

namespace App\Filament\Resources\WhatsAppConfigResource\Pages;

use App\Filament\Resources\WhatsAppConfigResource;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppConfigs extends ListRecords
{
    protected static string $resource = WhatsAppConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('test_all_connections')
                ->label('Test Semua Koneksi')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $service = new WhatsAppService();
                    $result = $service->testConnection();

                    if ($result['success']) {
                        Notification::make()
                            ->title('Test Koneksi Berhasil')
                            ->success()
                            ->body('Semua konfigurasi aktif berhasil terhubung')
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Test Koneksi Gagal')
                            ->danger()
                            ->body($result['message'])
                            ->send();
                    }
                }),
        ];
    }
}
