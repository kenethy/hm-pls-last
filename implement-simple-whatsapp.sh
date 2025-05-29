#!/bin/bash

# Simple WhatsApp Integration Implementation
# This script implements a much simpler approach using external services

echo "📱 Simple WhatsApp Integration Implementation"
echo "============================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

LARAVEL_DIR="/hm-new"

echo -e "${BLUE}📋 Implementing simple WhatsApp integration...${NC}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo ""

# Function to create simple WhatsApp service
create_whatsapp_service() {
    echo -e "${YELLOW}🔧 Step 1: Creating simple WhatsApp service...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Create WhatsApp service directory
    mkdir -p app/Services
    
    # Create simple WhatsApp service using cURL
    cat > app/Services/WhatsAppService.php << 'EOF'
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $apiUrl;
    private $apiKey;
    
    public function __construct()
    {
        // Using Fonnte.com as simple WhatsApp API (free tier available)
        $this->apiUrl = 'https://api.fonnte.com/send';
        $this->apiKey = config('services.whatsapp.api_key', 'your-fonnte-api-key');
    }
    
    /**
     * Send WhatsApp message
     */
    public function sendMessage($phone, $message)
    {
        try {
            // Clean phone number (remove +, spaces, etc)
            $phone = preg_replace('/[^0-9]/', '', $phone);
            
            // Add country code if not present (Indonesia: 62)
            if (!str_starts_with($phone, '62')) {
                $phone = '62' . ltrim($phone, '0');
            }
            
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);
            
            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phone,
                    'message' => substr($message, 0, 50) . '...'
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $response->json()
                ];
            } else {
                Log::error('WhatsApp message failed', [
                    'phone' => $phone,
                    'error' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to send message',
                    'error' => $response->body()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('WhatsApp service error', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Service error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send follow-up message after service completion
     */
    public function sendFollowUpMessage($customer, $service)
    {
        $message = $this->generateFollowUpMessage($customer, $service);
        return $this->sendMessage($customer->phone, $message);
    }
    
    /**
     * Generate follow-up message template
     */
    private function generateFollowUpMessage($customer, $service)
    {
        return "Halo {$customer->name},\n\n" .
               "Terima kasih telah menggunakan layanan Hartono Motor.\n\n" .
               "Detail Service:\n" .
               "- Layanan: {$service->service_type}\n" .
               "- Kendaraan: {$service->vehicle_info}\n" .
               "- Tanggal: " . $service->completed_at->format('d/m/Y H:i') . "\n\n" .
               "Jika ada pertanyaan atau keluhan, silakan hubungi kami.\n\n" .
               "Salam,\n" .
               "Tim Hartono Motor\n" .
               "📍 Alamat Workshop\n" .
               "📞 Telepon: 0xxx-xxxx-xxxx";
    }
    
    /**
     * Test connection
     */
    public function testConnection()
    {
        try {
            // Send test message to admin number
            $testPhone = '6281234567890'; // Replace with admin number
            $testMessage = 'Test koneksi WhatsApp API - ' . now()->format('d/m/Y H:i:s');
            
            return $this->sendMessage($testPhone, $testMessage);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }
}
EOF
    
    echo -e "${GREEN}✅ WhatsApp service created${NC}"
    echo ""
}

# Function to update Laravel configuration
update_laravel_config() {
    echo -e "${YELLOW}⚙️ Step 2: Updating Laravel configuration...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Add WhatsApp config to services.php
    if [ -f "config/services.php" ]; then
        # Backup original
        cp config/services.php config/services.php.backup
        
        # Add WhatsApp configuration
        cat >> config/services.php << 'EOF'

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service Configuration
    |--------------------------------------------------------------------------
    */
    
    'whatsapp' => [
        'api_key' => env('WHATSAPP_API_KEY'),
        'admin_phone' => env('WHATSAPP_ADMIN_PHONE', '6281234567890'),
        'enabled' => env('WHATSAPP_ENABLED', true),
    ],
EOF
        
        echo -e "${GREEN}✅ Services configuration updated${NC}"
    fi
    
    # Add environment variables
    if [ -f ".env" ]; then
        echo "" >> .env
        echo "# WhatsApp Configuration" >> .env
        echo "WHATSAPP_API_KEY=your-fonnte-api-key-here" >> .env
        echo "WHATSAPP_ADMIN_PHONE=6281234567890" >> .env
        echo "WHATSAPP_ENABLED=true" >> .env
        
        echo -e "${GREEN}✅ Environment variables added${NC}"
    fi
    
    echo ""
}

# Function to create Filament admin interface
create_filament_interface() {
    echo -e "${YELLOW}🎛️ Step 3: Creating Filament admin interface...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Create WhatsApp test page
    mkdir -p app/Filament/Pages
    
    cat > app/Filament/Pages/WhatsAppTest.php << 'EOF'
<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\WhatsAppService;

class WhatsAppTest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.whatsapp-test';
    protected static ?string $navigationGroup = 'WhatsApp Integration';
    protected static ?string $title = 'Test WhatsApp';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'phone' => '6281234567890',
            'message' => 'Test pesan dari Hartono Motor - ' . now()->format('d/m/Y H:i:s')
        ]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('phone')
                    ->label('Nomor WhatsApp')
                    ->placeholder('6281234567890')
                    ->required()
                    ->tel(),
                    
                Textarea::make('message')
                    ->label('Pesan')
                    ->placeholder('Masukkan pesan yang akan dikirim...')
                    ->required()
                    ->rows(5),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Kirim Pesan')
                ->color('success')
                ->action('sendMessage'),
                
            Action::make('test')
                ->label('Test Koneksi')
                ->color('info')
                ->action('testConnection'),
        ];
    }
    
    public function sendMessage(): void
    {
        $data = $this->form->getState();
        
        $whatsappService = new WhatsAppService();
        $result = $whatsappService->sendMessage($data['phone'], $data['message']);
        
        if ($result['success']) {
            Notification::make()
                ->title('Pesan Berhasil Dikirim')
                ->body('Pesan WhatsApp telah dikirim ke ' . $data['phone'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Gagal Mengirim Pesan')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }
    
    public function testConnection(): void
    {
        $whatsappService = new WhatsAppService();
        $result = $whatsappService->testConnection();
        
        if ($result['success']) {
            Notification::make()
                ->title('Koneksi Berhasil')
                ->body('WhatsApp API terhubung dengan baik')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Koneksi Gagal')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }
}
EOF
    
    # Create view file
    mkdir -p resources/views/filament/pages
    
    cat > resources/views/filament/pages/whatsapp-test.blade.php << 'EOF'
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Test WhatsApp Integration
            </h3>
            
            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <strong>Petunjuk:</strong><br>
                    1. Pastikan API Key sudah diisi di file .env<br>
                    2. Gunakan format nomor: 6281234567890 (dengan kode negara 62)<br>
                    3. Klik "Test Koneksi" untuk memverifikasi API<br>
                    4. Klik "Kirim Pesan" untuk mengirim pesan test
                </p>
            </div>
            
            {{ $this->form }}
            
            <div class="mt-6 flex gap-3">
                {{ $this->getFormActions() }}
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Konfigurasi API
            </h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <span class="font-medium">Status API:</span>
                    <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 rounded">
                        Perlu Konfigurasi
                    </span>
                </div>
                
                <div>
                    <span class="font-medium">Provider:</span>
                    <span class="ml-2">Fonnte.com (Free Tier Available)</span>
                </div>
                
                <div>
                    <span class="font-medium">Setup:</span>
                    <ol class="ml-2 mt-2 space-y-1 list-decimal list-inside">
                        <li>Daftar di <a href="https://fonnte.com" target="_blank" class="text-blue-600 hover:underline">fonnte.com</a></li>
                        <li>Dapatkan API Key dari dashboard</li>
                        <li>Update WHATSAPP_API_KEY di file .env</li>
                        <li>Scan QR code untuk menghubungkan WhatsApp</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
EOF
    
    echo -e "${GREEN}✅ Filament interface created${NC}"
    echo ""
}

# Function to integrate with service completion
integrate_service_completion() {
    echo -e "${YELLOW}🔗 Step 4: Integrating with service completion...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Create service observer for auto follow-up
    mkdir -p app/Observers
    
    cat > app/Observers/ServiceObserver.php << 'EOF'
<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class ServiceObserver
{
    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        // Check if service was just completed
        if ($service->isDirty('status') && $service->status === 'completed') {
            $this->sendFollowUpMessage($service);
        }
    }
    
    /**
     * Send follow-up WhatsApp message
     */
    private function sendFollowUpMessage(Service $service): void
    {
        try {
            // Check if WhatsApp is enabled
            if (!config('services.whatsapp.enabled')) {
                return;
            }
            
            // Check if customer has phone number
            if (!$service->customer || !$service->customer->phone) {
                Log::warning('Cannot send WhatsApp follow-up: missing customer phone', [
                    'service_id' => $service->id
                ]);
                return;
            }
            
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->sendFollowUpMessage($service->customer, $service);
            
            if ($result['success']) {
                Log::info('WhatsApp follow-up sent successfully', [
                    'service_id' => $service->id,
                    'customer_phone' => $service->customer->phone
                ]);
            } else {
                Log::error('WhatsApp follow-up failed', [
                    'service_id' => $service->id,
                    'error' => $result['message']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('WhatsApp follow-up exception', [
                'service_id' => $service->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
EOF
    
    # Register observer in AppServiceProvider
    if [ -f "app/Providers/AppServiceProvider.php" ]; then
        # Add observer registration
        echo -e "${BLUE}Note: Add this to AppServiceProvider.php boot() method:${NC}"
        echo -e "${YELLOW}Service::observe(ServiceObserver::class);${NC}"
    fi
    
    echo -e "${GREEN}✅ Service completion integration created${NC}"
    echo ""
}

# Function to show setup instructions
show_setup_instructions() {
    echo -e "${GREEN}🎉 Simple WhatsApp Integration Created!${NC}"
    echo "====================================="
    echo ""
    
    echo -e "${BLUE}📋 What was created:${NC}"
    echo -e "  ✅ Simple WhatsApp service using external API"
    echo -e "  ✅ Filament admin interface for testing"
    echo -e "  ✅ Auto follow-up on service completion"
    echo -e "  ✅ No Docker complexity - just PHP code"
    echo ""
    
    echo -e "${BLUE}📋 Next Steps:${NC}"
    echo -e "1. ${YELLOW}Register for WhatsApp API:${NC}"
    echo -e "   - Visit: https://fonnte.com"
    echo -e "   - Sign up for free account"
    echo -e "   - Get your API key"
    echo ""
    echo -e "2. ${YELLOW}Update Laravel configuration:${NC}"
    echo -e "   - Edit .env file"
    echo -e "   - Set WHATSAPP_API_KEY=your-actual-api-key"
    echo -e "   - Set WHATSAPP_ADMIN_PHONE=your-phone-number"
    echo ""
    echo -e "3. ${YELLOW}Register Service Observer:${NC}"
    echo -e "   - Edit app/Providers/AppServiceProvider.php"
    echo -e "   - Add to boot() method:"
    echo -e "     ${BLUE}use App\\Models\\Service;${NC}"
    echo -e "     ${BLUE}use App\\Observers\\ServiceObserver;${NC}"
    echo -e "     ${BLUE}Service::observe(ServiceObserver::class);${NC}"
    echo ""
    echo -e "4. ${YELLOW}Test the integration:${NC}"
    echo -e "   - Visit: https://hartonomotor.xyz/admin"
    echo -e "   - Go to: WhatsApp Integration → Test WhatsApp"
    echo -e "   - Click: Test Koneksi"
    echo -e "   - Send test message"
    echo ""
    
    echo -e "${BLUE}🔧 Alternative APIs (if Fonnte doesn't work):${NC}"
    echo -e "  - Wablas.com"
    echo -e "  - Woowa.id"
    echo -e "  - Qontak.com"
    echo -e "  - Or any WhatsApp Gateway API"
    echo ""
    
    echo -e "${BLUE}💡 Benefits of this approach:${NC}"
    echo -e "  ✅ No Docker complexity"
    echo -e "  ✅ No build errors"
    echo -e "  ✅ Uses reliable external service"
    echo -e "  ✅ Easy to maintain and debug"
    echo -e "  ✅ Works immediately after API key setup"
    echo ""
}

# Main execution
echo -e "${BLUE}Starting simple WhatsApp integration...${NC}"
echo ""

create_whatsapp_service
update_laravel_config
create_filament_interface
integrate_service_completion
show_setup_instructions

echo -e "${GREEN}✅ Simple WhatsApp integration implementation completed!${NC}"
