<?php

namespace App\Filament\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Resources\WhatsAppMessageResource;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsAppMessage extends ViewRecord
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend')
                ->label('Kirim Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['failed', 'pending']))
                ->requiresConfirmation()
                ->modalHeading('Kirim Ulang Pesan')
                ->modalDescription('Apakah Anda yakin ingin mengirim ulang pesan ini?')
                ->action(function () {
                    $service = new WhatsAppService();
                    $result = $service->sendTextMessage(
                        phoneNumber: $this->record->phone_number,
                        message: $this->record->content,
                        serviceId: $this->record->service_id,
                        customerId: $this->record->customer_id,
                        followUpTemplateId: $this->record->follow_up_template_id,
                        isAutomated: $this->record->is_automated,
                        triggeredBy: 'manual'
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->title('Pesan Berhasil Dikirim Ulang')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Mengirim Ulang Pesan')
                            ->danger()
                            ->body($result['message'])
                            ->send();
                    }
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
