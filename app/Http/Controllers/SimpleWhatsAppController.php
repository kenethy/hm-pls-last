<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use LaravelWhatsApp\WhatsApp;
use Illuminate\Support\Facades\Log;

class SimpleWhatsAppController extends Controller
{
    private $whatsapp;

    public function __construct()
    {
        $this->whatsapp = new WhatsApp();
    }

    /**
     * Tampilkan halaman QR Code
     */
    public function index()
    {
        return view('whatsapp.simple-qr-generator', [
            'title' => 'WhatsApp QR Generator - Hartono Motor (Simple Version)'
        ]);
    }

    /**
     * Generate QR Code
     */
    public function generateQR(): JsonResponse
    {
        try {
            $qrCode = $this->whatsapp->generateQR();
            
            Log::info('WhatsApp QR generated successfully');
            
            return response()->json([
                'success' => true,
                'qr_code' => $qrCode,
                'message' => 'QR Code generated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp QR generation failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check WhatsApp connection status
     */
    public function checkStatus(): JsonResponse
    {
        try {
            $status = $this->whatsapp->getStatus();
            
            return response()->json([
                'success' => true,
                'status' => $status,
                'connected' => $status === 'connected'
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp status check failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        try {
            $result = $this->whatsapp->sendMessage(
                $request->phone, 
                $request->message
            );
            
            Log::info('WhatsApp message sent', [
                'phone' => $request->phone,
                'message_length' => strlen($request->message)
            ]);
            
            return response()->json([
                'success' => true,
                'result' => $result,
                'message' => 'Message sent successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp message send failed', [
                'error' => $e->getMessage(),
                'phone' => $request->phone
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send follow-up message after service completion
     */
    public function sendFollowUp(Request $request): JsonResponse
    {
        $request->validate([
            'customer_phone' => 'required|string',
            'service_type' => 'required|string',
            'customer_name' => 'nullable|string'
        ]);

        try {
            $customerName = $request->customer_name ?: 'Pelanggan';
            
            $message = "Halo {$customerName}, \n\n" .
                      "Terima kasih telah menggunakan layanan {$request->service_type} di Hartono Motor. \n\n" .
                      "Bagaimana pengalaman service Anda hari ini? \n" .
                      "Kami sangat menghargai feedback Anda untuk terus meningkatkan pelayanan. \n\n" .
                      "Salam, \n" .
                      "Tim Hartono Motor";
            
            $result = $this->whatsapp->sendMessage(
                $request->customer_phone, 
                $message
            );
            
            Log::info('WhatsApp follow-up sent', [
                'phone' => $request->customer_phone,
                'service_type' => $request->service_type
            ]);
            
            return response()->json([
                'success' => true,
                'result' => $result,
                'message' => 'Follow-up message sent successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp follow-up failed', [
                'error' => $e->getMessage(),
                'phone' => $request->customer_phone
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send follow-up: ' . $e->getMessage()
            ], 500);
        }
    }
}
