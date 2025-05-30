<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    private $baseUrl;
    private $apiKey;
    private $sessionId;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.base_url', 'http://whatsapp-api:3000');
        $this->apiKey = config('services.whatsapp.api_key', 'hartonomotor2024');
        $this->sessionId = config('services.whatsapp.session_id', 'HARTONO');
    }

    /**
     * Start WhatsApp session and get QR code
     */
    public function startSession()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/session/start");

            if ($response->successful()) {
                Log::info('WhatsApp session started successfully', [
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            throw new Exception('Failed to start session: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp session start failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get QR code for session
     */
    public function getQRCode()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/session/qr");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get QR code: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp QR code retrieval failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get QR code as image
     */
    public function getQRCodeImage()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/session/qr/{$this->sessionId}/image");

            if ($response->successful()) {
                return $response->body();
            }

            throw new Exception('Failed to get QR code image: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp QR code image retrieval failed', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId
            ]);
            throw $e;
        }
    }

    /**
     * Check session status
     */
    public function getSessionStatus()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/session/status/{$this->sessionId}");

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => 'Session not found'];
        } catch (Exception $e) {
            Log::error('WhatsApp session status check failed', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send text message
     */
    public function sendMessage($phone, $message)
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);

            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/client/sendMessage/{$this->sessionId}", [
                    'chatId' => $formattedPhone,
                    'contentType' => 'string',
                    'content' => $message
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $formattedPhone,
                    'message_length' => strlen($message),
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            throw new Exception('Failed to send message: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp message sending failed', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'message_length' => strlen($message)
            ]);
            throw $e;
        }
    }

    /**
     * Send image message
     */
    public function sendImage($phone, $imagePath, $caption = '')
    {
        try {
            $formattedPhone = $this->formatPhoneNumber($phone);

            $response = Http::timeout(60)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/client/sendFile/{$this->sessionId}", [
                    'chatId' => $formattedPhone,
                    'contentType' => 'MessageMedia',
                    'content' => $imagePath,
                    'options' => [
                        'caption' => $caption
                    ]
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp image sent successfully', [
                    'phone' => $formattedPhone,
                    'image_path' => $imagePath,
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            throw new Exception('Failed to send image: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp image sending failed', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'image_path' => $imagePath
            ]);
            throw $e;
        }
    }

    /**
     * Get contacts
     */
    public function getContacts()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/client/getContacts/{$this->sessionId}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('Failed to get contacts: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp contacts retrieval failed', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId
            ]);
            throw $e;
        }
    }

    /**
     * Terminate session
     */
    public function terminateSession()
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->delete("{$this->baseUrl}/session/terminate/{$this->sessionId}");

            if ($response->successful()) {
                Log::info('WhatsApp session terminated successfully', [
                    'session_id' => $this->sessionId
                ]);
                return $response->json();
            }

            throw new Exception('Failed to terminate session: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp session termination failed', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present (assuming Indonesia +62)
        if (substr($phone, 0, 2) !== '62') {
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            } else {
                $phone = '62' . $phone;
            }
        }

        return $phone . '@c.us';
    }

    /**
     * Check if phone number is valid WhatsApp number
     */
    public function checkNumber($phone)
    {
        try {
            $formattedPhone = $this->formatPhoneNumber($phone);

            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/client/isRegisteredUser/{$this->sessionId}", [
                    'chatId' => $formattedPhone
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => 'Failed to check number'];
        } catch (Exception $e) {
            Log::error('WhatsApp number check failed', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
