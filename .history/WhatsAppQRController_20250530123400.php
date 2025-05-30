<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppQRController extends Controller
{
    private string $whatsappApiUrl;
    private array $basicAuth;

    public function __construct()
    {
        // URL API WhatsApp di VPS yang sama (internal communication)
        $this->whatsappApiUrl = config('whatsapp.api_url', 'http://localhost:3000');

        // Basic Auth credentials
        $this->basicAuth = [
            config('whatsapp.basic_auth.username', 'admin'),
            config('whatsapp.basic_auth.password', '')
        ];
    }

    /**
     * Tampilkan halaman QR Code
     */
    public function index()
    {
        return view('whatsapp.qr-generator', [
            'title' => 'WhatsApp QR Code Generator',
            'api_url' => $this->whatsappApiUrl
        ]);
    }

    /**
     * Generate Fresh QR Code
     */
    public function generateFreshQR(): JsonResponse
    {
        try {
            $response = Http::timeout(30)
                ->withBasicAuth($this->basicAuth[0], $this->basicAuth[1])
                ->get($this->whatsappApiUrl . '/app/login-fresh');

            if ($response->successful()) {
                $data = $response->json();

                // Log successful QR generation
                Log::info('WhatsApp Fresh QR generated', [
                    'qr_duration' => $data['results']['qr_duration'] ?? null,
                    'processing_time' => $data['results']['total_time_ms'] ?? null,
                    'fresh' => $data['results']['fresh'] ?? false
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                Log::error('WhatsApp API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate QR code'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp QR generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check WhatsApp connection status
     */
    public function checkStatus(): JsonResponse
    {
        try {
            $response = Http::timeout(10)->get($this->whatsappApiUrl . '/app/devices');

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to check status'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp status check failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp message (for future use)
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        try {
            $response = Http::timeout(30)->post($this->whatsappApiUrl . '/send/message', [
                'phone' => $request->phone,
                'message' => $request->message
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('WhatsApp message sent', [
                    'phone' => $request->phone,
                    'message_length' => strlen($request->message)
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send message'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp message send failed', [
                'error' => $e->getMessage(),
                'phone' => $request->phone
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ], 500);
        }
    }
}
