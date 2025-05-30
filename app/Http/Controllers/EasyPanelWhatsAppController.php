<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasyPanelWhatsAppController extends Controller
{
    private string $whatsappApiUrl;
    private array $basicAuth;

    public function __construct()
    {
        // URL WhatsApp API di Easy Panel (akan dapat subdomain dari Easy Panel)
        $this->whatsappApiUrl = config('whatsapp.easypanel_api_url');
        
        // Basic Auth credentials
        $this->basicAuth = [
            config('whatsapp.basic_auth.username', 'admin'),
            config('whatsapp.basic_auth.password', 'hartonomotor123')
        ];
    }

    /**
     * Kirim pesan otomatis ketika service selesai
     */
    public function sendServiceCompletedMessage(Request $request): JsonResponse
    {
        $request->validate([
            'customer_phone' => 'required|string',
            'customer_name' => 'nullable|string',
            'service_type' => 'required|string',
            'service_id' => 'required|integer'
        ]);

        try {
            $customerName = $request->customer_name ?: 'Pelanggan';
            
            // Template pesan follow-up
            $message = $this->generateFollowUpMessage(
                $customerName,
                $request->service_type,
                $request->service_id
            );
            
            // Kirim pesan via Easy Panel WhatsApp API
            $response = Http::timeout(30)
                ->withBasicAuth($this->basicAuth[0], $this->basicAuth[1])
                ->post($this->whatsappApiUrl . '/send/message', [
                    'phone' => $this->formatPhoneNumber($request->customer_phone),
                    'message' => $message
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Log successful message
                Log::info('WhatsApp follow-up sent via Easy Panel', [
                    'phone' => $request->customer_phone,
                    'service_id' => $request->service_id,
                    'service_type' => $request->service_type
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan follow-up berhasil dikirim',
                    'data' => $data
                ]);
            } else {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('WhatsApp follow-up failed', [
                'error' => $e->getMessage(),
                'phone' => $request->customer_phone,
                'service_id' => $request->service_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate template pesan follow-up
     */
    private function generateFollowUpMessage(string $customerName, string $serviceType, int $serviceId): string
    {
        return "Halo {$customerName}! 👋\n\n" .
               "Terima kasih telah menggunakan layanan *{$serviceType}* di Hartono Motor.\n\n" .
               "🔧 Service ID: #{$serviceId}\n" .
               "📅 Tanggal: " . now()->format('d/m/Y H:i') . "\n\n" .
               "Bagaimana pengalaman service Anda hari ini? 😊\n" .
               "Kami sangat menghargai feedback Anda untuk terus meningkatkan pelayanan.\n\n" .
               "Jika ada pertanyaan atau keluhan, jangan ragu untuk menghubungi kami.\n\n" .
               "Salam hangat,\n" .
               "*Tim Hartono Motor* 🚗\n" .
               "📍 Alamat: [Alamat Workshop]\n" .
               "📞 Telp: [Nomor Telepon]\n" .
               "🌐 Website: hartonomotor.xyz";
    }

    /**
     * Format nomor telepon untuk WhatsApp
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to international format
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Test koneksi ke WhatsApp API
     */
    public function testConnection(): JsonResponse
    {
        try {
            $response = Http::timeout(10)
                ->withBasicAuth($this->basicAuth[0], $this->basicAuth[1])
                ->get($this->whatsappApiUrl . '/app/devices');
            
            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi ke WhatsApp API berhasil',
                    'data' => $data
                ]);
            } else {
                throw new \Exception('API response error: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kirim pesan custom (untuk testing)
     */
    public function sendCustomMessage(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        try {
            $response = Http::timeout(30)
                ->withBasicAuth($this->basicAuth[0], $this->basicAuth[1])
                ->post($this->whatsappApiUrl . '/send/message', [
                    'phone' => $this->formatPhoneNumber($request->phone),
                    'message' => $request->message
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Custom WhatsApp message sent', [
                    'phone' => $request->phone,
                    'message_length' => strlen($request->message)
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan berhasil dikirim',
                    'data' => $data
                ]);
            } else {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('Custom WhatsApp message failed', [
                'error' => $e->getMessage(),
                'phone' => $request->phone
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle webhook dari WhatsApp API (incoming messages)
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Log incoming webhook
            Log::info('WhatsApp webhook received', $data);
            
            // Process incoming message jika diperlukan
            // Misalnya auto-reply untuk pertanyaan tertentu
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }
}
