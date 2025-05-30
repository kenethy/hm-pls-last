<?php

// Contoh integrasi dengan tombol "Selesai" di Filament

// 1. Di ServiceResource.php (Filament)
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Http;

// Tambahkan action di table
Action::make('complete_service')
    ->label('Selesai')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->modalHeading('Selesaikan Service')
    ->modalDescription('Apakah Anda yakin ingin menyelesaikan service ini? Pesan follow-up akan dikirim ke customer.')
    ->action(function ($record) {
        // Update status service
        $record->update(['status' => 'completed']);
        
        // Kirim WhatsApp follow-up otomatis
        $this->sendWhatsAppFollowUp($record);
        
        // Notifikasi sukses
        Notification::make()
            ->title('Service Completed')
            ->body('Service berhasil diselesaikan dan pesan follow-up telah dikirim.')
            ->success()
            ->send();
    })
    ->visible(fn ($record) => $record->status !== 'completed');

// Method untuk kirim WhatsApp follow-up
private function sendWhatsAppFollowUp($service)
{
    try {
        $response = Http::post(route('whatsapp-easypanel.send-service-completed'), [
            'customer_phone' => $service->customer_phone,
            'customer_name' => $service->customer_name,
            'service_type' => $service->service_type,
            'service_id' => $service->id
        ]);
        
        if ($response->successful()) {
            Log::info('WhatsApp follow-up sent for service', [
                'service_id' => $service->id,
                'customer_phone' => $service->customer_phone
            ]);
        }
        
    } catch (\Exception $e) {
        Log::error('Failed to send WhatsApp follow-up', [
            'service_id' => $service->id,
            'error' => $e->getMessage()
        ]);
    }
}

// 2. Atau jika menggunakan Observer/Event
// Di ServiceObserver.php
class ServiceObserver
{
    public function updated(Service $service)
    {
        // Jika status berubah menjadi completed
        if ($service->isDirty('status') && $service->status === 'completed') {
            // Kirim WhatsApp follow-up dengan delay
            dispatch(new SendWhatsAppFollowUpJob($service))->delay(now()->addMinutes(5));
        }
    }
}

// 3. Job untuk kirim WhatsApp (dengan queue)
// Di SendWhatsAppFollowUpJob.php
class SendWhatsAppFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function handle()
    {
        try {
            $response = Http::timeout(30)->post(route('whatsapp-easypanel.send-service-completed'), [
                'customer_phone' => $this->service->customer_phone,
                'customer_name' => $this->service->customer_name,
                'service_type' => $this->service->service_type,
                'service_id' => $this->service->id
            ]);
            
            if ($response->successful()) {
                Log::info('WhatsApp follow-up sent successfully', [
                    'service_id' => $this->service->id
                ]);
            } else {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('WhatsApp follow-up job failed', [
                'service_id' => $this->service->id,
                'error' => $e->getMessage()
            ]);
            
            // Retry job if failed
            $this->fail($e);
        }
    }
}

// 4. Environment Variables untuk .env
/*
# WhatsApp Easy Panel Configuration
WHATSAPP_EASYPANEL_API_URL=https://your-subdomain.easypanel.host:3000
WHATSAPP_BASIC_AUTH_USERNAME=admin
WHATSAPP_BASIC_AUTH_PASSWORD=hartonomotor123
WHATSAPP_WEBHOOK_URL=https://hartonomotor.xyz/webhook/whatsapp
WHATSAPP_WEBHOOK_SECRET=your_webhook_secret
WHATSAPP_LOGGING_ENABLED=true
WHATSAPP_AUTO_REPLY_ENABLED=false
*/

// 5. Testing Integration
// Route untuk test di web.php
Route::get('/test-whatsapp', function () {
    $response = Http::post(route('whatsapp-easypanel.send-custom-message'), [
        'phone' => '628123456789', // Ganti dengan nomor test
        'message' => 'Test pesan dari Hartono Motor via Easy Panel!'
    ]);
    
    return response()->json($response->json());
});

// 6. Blade component untuk tombol manual (jika diperlukan)
// Di resources/views/components/whatsapp-send-button.blade.php
/*
<button 
    type="button" 
    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center"
    onclick="sendWhatsAppMessage('{{ $phone }}', '{{ $message }}')"
>
    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.126"/>
    </svg>
    Kirim WhatsApp
</button>

<script>
function sendWhatsAppMessage(phone, message) {
    fetch('{{ route("whatsapp-easypanel.send-custom-message") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            phone: phone,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Pesan WhatsApp berhasil dikirim!');
        } else {
            alert('Gagal mengirim pesan: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim pesan');
    });
}
</script>
*/
