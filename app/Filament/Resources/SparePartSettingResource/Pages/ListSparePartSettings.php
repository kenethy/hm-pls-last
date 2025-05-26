<?php

namespace App\Filament\Resources\SparePartSettingResource\Pages;

use App\Filament\Resources\SparePartSettingResource;
use App\Models\SparePartSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListSparePartSettings extends ListRecords
{
    protected static string $resource = SparePartSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('clear_cache')
                ->label('Bersihkan Cache')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    SparePartSetting::clearCache();
                    
                    Notification::make()
                        ->title('Cache Berhasil Dibersihkan')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Bersihkan Cache Pengaturan')
                ->modalDescription('Apakah Anda yakin ingin membersihkan cache pengaturan? Ini akan memuat ulang semua pengaturan dari database.')
                ->modalSubmitActionLabel('Ya, Bersihkan'),
        ];
    }
}
