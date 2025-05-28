<?php

namespace App\Filament\Resources\MechanicReportResource\Pages;

use App\Filament\Resources\MechanicReportResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ListMechanicReports extends ListRecords
{
    protected static string $resource = MechanicReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('refreshReports')
                ->label('Refresh Rekap Montir')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->tooltip('Memperbarui semua rekap kumulatif montir berdasarkan data servis terbaru')
                ->requiresConfirmation()
                ->modalHeading('Refresh Rekap Montir')
                ->modalDescription('Apakah Anda yakin ingin memperbarui semua rekap kumulatif montir? Proses ini akan menghitung ulang semua data berdasarkan servis terbaru.')
                ->modalSubmitActionLabel('Ya, Refresh')
                ->action(function () {
                    try {
                        // Use the updated command that works with cumulative reports
                        Artisan::call('mechanic:sync-reports', [
                            '--force' => true,
                        ]);

                        Notification::make()
                            ->title('Rekap montir berhasil diperbarui')
                            ->success()
                            ->body('Semua rekap kumulatif montir telah diperbarui berdasarkan data servis terbaru.')
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('Error refreshing mechanic reports: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Gagal memperbarui rekap montir')
                            ->danger()
                            ->body('Terjadi kesalahan saat memperbarui rekap montir: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
