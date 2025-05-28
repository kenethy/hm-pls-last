<?php

namespace App\Filament\Resources\MechanicReportResource\Pages;

use App\Filament\Resources\MechanicReportResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EditMechanicReport extends EditRecord
{
    protected static string $resource = MechanicReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refreshReport')
                ->label('Refresh Rekap')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->tooltip('Memperbarui rekap kumulatif montir ini berdasarkan data servis terbaru')
                ->visible(fn() => $this->record->is_cumulative)
                ->requiresConfirmation()
                ->modalHeading('Refresh Rekap Kumulatif')
                ->modalDescription('Apakah Anda yakin ingin memperbarui rekap kumulatif montir ini? Data akan dihitung ulang berdasarkan semua servis terbaru.')
                ->modalSubmitActionLabel('Ya, Refresh')
                ->action(function () {
                    try {
                        // Use the updated command that works with cumulative reports
                        Artisan::call('mechanic:sync-reports', [
                            '--mechanic_id' => $this->record->mechanic_id,
                        ]);

                        // Refresh the record to show updated data
                        $this->record->refresh();
                        $this->refreshFormData(['services_count', 'total_labor_cost', 'last_calculated_at']);

                        Notification::make()
                            ->title('Rekap montir berhasil diperbarui')
                            ->success()
                            ->body('Rekap kumulatif montir telah diperbarui berdasarkan data servis terbaru.')
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('Error refreshing individual mechanic report: ' . $e->getMessage(), [
                            'mechanic_id' => $this->record->mechanic_id,
                            'report_id' => $this->record->id,
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Gagal memperbarui rekap montir')
                            ->danger()
                            ->body('Terjadi kesalahan saat memperbarui rekap montir: ' . $e->getMessage())
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
