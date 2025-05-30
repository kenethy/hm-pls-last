<?php

namespace App\Filament\Pages;

use App\Services\WhatsAppService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;

class WhatsAppManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'WhatsApp Manager';
    protected static ?string $title = 'WhatsApp Manager';
    protected static string $view = 'filament.pages.whatsapp-manager';
    protected static ?int $navigationSort = 100;

    public $sessionStatus = null;
    public $qrCode = null;
    public $isConnected = false;

    public function mount()
    {
        $this->checkSessionStatus();
    }

    protected function getWhatsAppService(): WhatsAppService
    {
        return app(WhatsAppService::class);
    }

    public function checkSessionStatus()
    {
        try {
            $whatsappService = $this->getWhatsAppService();
            $status = $whatsappService->getSessionStatus();
            $this->sessionStatus = $status;
            $this->isConnected = isset($status['success']) && $status['success'] === true;

            if (!$this->isConnected) {
                $this->getQRCode();
            }
        } catch (\Exception $e) {
            $this->sessionStatus = ['success' => false, 'message' => $e->getMessage()];
            $this->isConnected = false;
        }
    }

    public function getQRCode()
    {
        try {
            $whatsappService = $this->getWhatsAppService();
            $qrData = $whatsappService->getQRCode();
            $this->qrCode = $qrData;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error getting QR Code')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startSession')
                ->label('Start Session')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn() => !$this->isConnected)
                ->action(function () {
                    try {
                        $whatsappService = $this->getWhatsAppService();
                        $whatsappService->startSession();

                        Notification::make()
                            ->title('Session Started')
                            ->body('WhatsApp session has been started. Please scan the QR code.')
                            ->success()
                            ->send();

                        $this->checkSessionStatus();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Start Session')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('refreshStatus')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->checkSessionStatus();

                    Notification::make()
                        ->title('Status Refreshed')
                        ->body('Session status has been updated.')
                        ->success()
                        ->send();
                }),

            Action::make('terminateSession')
                ->label('Terminate Session')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->visible(fn() => $this->isConnected)
                ->requiresConfirmation()
                ->modalHeading('Terminate WhatsApp Session')
                ->modalDescription('Are you sure you want to terminate the WhatsApp session? You will need to scan QR code again.')
                ->action(function () {
                    try {
                        $whatsappService = $this->getWhatsAppService();
                        $whatsappService->terminateSession();

                        Notification::make()
                            ->title('Session Terminated')
                            ->body('WhatsApp session has been terminated.')
                            ->success()
                            ->send();

                        $this->checkSessionStatus();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Terminate Session')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('testMessage')
                ->label('Test Message')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn() => $this->isConnected)
                ->form([
                    \Filament\Forms\Components\TextInput::make('phone')
                        ->label('Phone Number')
                        ->placeholder('628123456789')
                        ->required()
                        ->helperText('Enter phone number with country code (e.g., 628123456789)'),
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Test Message')
                        ->default('Hello from Hartono Motor! This is a test message from our automated system.')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $whatsappService = $this->getWhatsAppService();
                        $result = $whatsappService->sendMessage($data['phone'], $data['message']);

                        if (isset($result['success']) && $result['success']) {
                            Notification::make()
                                ->title('Test Message Sent')
                                ->body("Message sent successfully to {$data['phone']}")
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Failed to send message: ' . json_encode($result));
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Send Test Message')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('sendFollowUp')
                ->label('Send Follow-up Messages')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->visible(fn() => $this->isConnected)
                ->requiresConfirmation()
                ->modalHeading('Send Follow-up Messages')
                ->modalDescription('This will send follow-up messages to all customers who need follow-up. Are you sure?')
                ->action(function () {
                    try {
                        // Get customers needing follow-up
                        $customers = \App\Models\Customer::query()
                            ->where('is_active', true)
                            ->where('service_count', '>', 0)
                            ->where(function ($query) {
                                $query->whereNull('last_service_date')
                                    ->orWhere('last_service_date', '<=', now()->subMonths(3));
                            })
                            ->limit(10) // Limit to prevent overwhelming
                            ->get();

                        $sent = 0;
                        $failed = 0;

                        foreach ($customers as $customer) {
                            try {
                                // Get default follow-up template
                                $template = \App\Models\MessageTemplate::where('type', 'follow_up')
                                    ->where('is_default', true)
                                    ->where('is_active', true)
                                    ->first();

                                if (!$template) {
                                    throw new \Exception('No default follow-up template found');
                                }

                                // Get customer's latest service for template variables
                                $latestService = $customer->services()->latest()->first();

                                if ($latestService) {
                                    $message = $template->getFormattedContent($latestService);
                                } else {
                                    // Fallback message if no service found
                                    $message = str_replace(
                                        ['{customer_name}', '{service_type}', '{vehicle_model}'],
                                        [$customer->name, 'servis terakhir', 'kendaraan'],
                                        $template->content
                                    );
                                }

                                $whatsappService = $this->getWhatsAppService();
                                $result = $whatsappService->sendMessage($customer->phone, $message);

                                if (isset($result['success']) && $result['success']) {
                                    $sent++;
                                } else {
                                    $failed++;
                                }

                                // Small delay to prevent rate limiting
                                sleep(1);
                            } catch (\Exception $e) {
                                $failed++;
                                \Log::error('Failed to send follow-up to customer', [
                                    'customer_id' => $customer->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Follow-up Messages Sent')
                            ->body("Successfully sent: {$sent}, Failed: {$failed}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Send Follow-up Messages')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getSessionStatusColor(): string
    {
        if ($this->isConnected) {
            return 'success';
        }

        return 'danger';
    }

    public function getSessionStatusText(): string
    {
        if ($this->isConnected) {
            return 'Connected';
        }

        return 'Disconnected';
    }

    public function getQRCodeUrl(): ?string
    {
        if ($this->qrCode && isset($this->qrCode['qr'])) {
            return "data:image/png;base64," . base64_encode($this->qrCode['qr']);
        }

        return null;
    }
}
