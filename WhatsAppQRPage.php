<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class WhatsAppQRPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static string $view = 'filament.pages.whatsapp-qr';
    protected static ?string $navigationLabel = 'WhatsApp QR';
    protected static ?string $title = 'WhatsApp QR Code Generator';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?int $navigationSort = 1;

    public ?string $qrCodeUrl = null;
    public ?array $qrDetails = null;
    public ?string $connectionStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateFreshQR')
                ->label('Generate Fresh QR')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->action('generateFreshQR'),
                
            Action::make('checkStatus')
                ->label('Check Status')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('checkStatus'),
        ];
    }

    public function generateFreshQR()
    {
        try {
            $response = Http::timeout(30)->get(config('whatsapp.api_url') . '/app/login-fresh');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 'SUCCESS') {
                    $this->qrCodeUrl = $data['results']['qr_link'];
                    $this->qrDetails = $data['results'];
                    
                    Notification::make()
                        ->title('QR Code Generated')
                        ->body('Fresh QR code berhasil dibuat. Scan dengan WhatsApp Anda.')
                        ->success()
                        ->send();
                } else {
                    throw new \Exception($data['message'] ?? 'Unknown error');
                }
            } else {
                throw new \Exception('API request failed');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Gagal membuat QR code: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkStatus()
    {
        try {
            $response = Http::timeout(10)->get(config('whatsapp.api_url') . '/app/devices');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 'SUCCESS') {
                    $devices = $data['results'] ?? [];
                    
                    if (count($devices) > 0) {
                        $deviceNames = collect($devices)->pluck('name')->filter()->implode(', ');
                        $this->connectionStatus = "Terhubung: " . ($deviceNames ?: 'Unknown Device');
                        
                        Notification::make()
                            ->title('Status Connected')
                            ->body($this->connectionStatus)
                            ->success()
                            ->send();
                    } else {
                        $this->connectionStatus = "Tidak ada perangkat terhubung";
                        
                        Notification::make()
                            ->title('No Devices')
                            ->body('Tidak ada perangkat WhatsApp yang terhubung')
                            ->warning()
                            ->send();
                    }
                } else {
                    throw new \Exception($data['message'] ?? 'Unknown error');
                }
            } else {
                throw new \Exception('API request failed');
            }
        } catch (\Exception $e) {
            $this->connectionStatus = "Error: " . $e->getMessage();
            
            Notification::make()
                ->title('Connection Error')
                ->body('Gagal mengecek status: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function mount()
    {
        $this->checkStatus();
    }
}
