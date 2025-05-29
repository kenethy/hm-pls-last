<?php

namespace App\Filament\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Resources\WhatsAppMessageResource;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppMessage extends CreateRecord
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function afterCreate(): void
    {
        // Automatically send the message after creation if it's pending
        if ($this->record->status === 'pending') {
            $service = new WhatsAppService();
            $result = $service->sendTextMessage(
                phoneNumber: $this->record->phone_number,
                message: $this->record->content,
                serviceId: $this->record->service_id,
                customerId: $this->record->customer_id,
                followUpTemplateId: $this->record->follow_up_template_id,
                isAutomated: $this->record->is_automated,
                triggeredBy: $this->record->triggered_by
            );

            if ($result['success']) {
                Notification::make()
                    ->title('Pesan Berhasil Dikirim')
                    ->success()
                    ->body('Pesan WhatsApp telah dikirim ke ' . $this->record->phone_number)
                    ->send();
            } else {
                Notification::make()
                    ->title('Gagal Mengirim Pesan')
                    ->danger()
                    ->body($result['message'])
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
