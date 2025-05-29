<?php

namespace App\Filament\Resources\WhatsAppMessageResource\Pages;

use App\Filament\Resources\WhatsAppMessageResource;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppMessages extends ListRecords
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('send_manual_message')
                ->label('Kirim Pesan Manual')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('phone_number')
                        ->label('Nomor Telepon')
                        ->required()
                        ->tel()
                        ->helperText('Format: 628123456789'),

                    Forms\Components\Textarea::make('message')
                        ->label('Pesan')
                        ->required()
                        ->rows(4),

                    Forms\Components\Select::make('customer_id')
                        ->label('Customer (Opsional)')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (array $data) {
                    $service = new WhatsAppService();
                    $result = $service->sendTextMessage(
                        phoneNumber: $data['phone_number'],
                        message: $data['message'],
                        customerId: $data['customer_id'] ?? null,
                        isAutomated: false,
                        triggeredBy: 'manual'
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->title('Pesan Berhasil Dikirim')
                            ->success()
                            ->body('Pesan WhatsApp telah dikirim ke ' . $data['phone_number'])
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Mengirim Pesan')
                            ->danger()
                            ->body($result['message'])
                            ->send();
                    }
                }),

            Actions\Action::make('retry_failed_messages')
                ->label('Kirim Ulang Pesan Gagal')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kirim Ulang Pesan Gagal')
                ->modalDescription('Apakah Anda yakin ingin mengirim ulang semua pesan yang gagal?')
                ->action(function () {
                    $failedMessages = WhatsAppMessage::where('status', 'failed')->get();
                    $service = new WhatsAppService();
                    $successCount = 0;
                    $failCount = 0;

                    foreach ($failedMessages as $message) {
                        $result = $service->sendTextMessage(
                            phoneNumber: $message->phone_number,
                            message: $message->content,
                            serviceId: $message->service_id,
                            customerId: $message->customer_id,
                            followUpTemplateId: $message->follow_up_template_id,
                            isAutomated: $message->is_automated,
                            triggeredBy: 'manual'
                        );

                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    }

                    Notification::make()
                        ->title('Proses Kirim Ulang Selesai')
                        ->success()
                        ->body("Berhasil: {$successCount}, Gagal: {$failCount}")
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WhatsAppMessageResource\Widgets\WhatsAppMessageStatsWidget::class,
        ];
    }
}
