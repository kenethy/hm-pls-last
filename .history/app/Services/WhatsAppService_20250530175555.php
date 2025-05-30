<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
                ->get("{$this->baseUrl}/session/qr/image");

            if ($response->successful()) {
                return $response->body();
            }

            throw new Exception('Failed to get QR code image: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp QR code image retrieval failed', [
                'error' => $e->getMessage()
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
                ->get("{$this->baseUrl}/session/status");

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'message' => 'Session not found'];
        } catch (Exception $e) {
            Log::error('WhatsApp session status check failed', [
                'error' => $e->getMessage()
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
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/message/send", [
                    'phone' => $phone,
                    'message' => $message
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phone,
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
                ->delete("{$this->baseUrl}/session/terminate");

            if ($response->successful()) {
                Log::info('WhatsApp session terminated successfully');
                return $response->json();
            }

            throw new Exception('Failed to terminate session: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp session termination failed', [
                'error' => $e->getMessage()
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
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/number/check", [
                    'phone' => $phone
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

    /**
     * Get all chats for chat interface
     */
    public function getChats()
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/chats/list");

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'chats' => []];
        } catch (Exception $e) {
            Log::error('Failed to get WhatsApp chats', [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'chats' => []];
        }
    }

    /**
     * Get messages from a specific chat
     */
    public function getChatMessages($chatId, $limit = 50)
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/chats/messages", [
                    'chatId' => $chatId,
                    'limit' => $limit
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'messages' => []];
        } catch (Exception $e) {
            Log::error('Failed to get chat messages', [
                'error' => $e->getMessage(),
                'chatId' => $chatId
            ]);
            return ['success' => false, 'messages' => []];
        }
    }

    /**
     * Send message to chat
     */
    public function sendChatMessage($chatId, $message)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/message/send", [
                    'chatId' => $chatId,
                    'message' => $message
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp chat message sent successfully', [
                    'chatId' => $chatId,
                    'message_length' => strlen($message),
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            throw new Exception('Failed to send chat message: ' . $response->body());
        } catch (Exception $e) {
            Log::error('WhatsApp chat message sending failed', [
                'error' => $e->getMessage(),
                'chatId' => $chatId,
                'message_length' => strlen($message)
            ]);
            throw $e;
        }
    }

    /**
     * Mark chat as read
     */
    public function markChatAsRead($chatId)
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/chats/mark-read", [
                    'chatId' => $chatId
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false];
        } catch (Exception $e) {
            Log::error('Failed to mark chat as read', [
                'error' => $e->getMessage(),
                'chatId' => $chatId
            ]);
            return ['success' => false];
        }
    }

    /**
     * Download media from message
     */
    public function downloadMedia($messageId)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/message/download-media", [
                    'messageId' => $messageId
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['media'])) {
                    // Save media to storage
                    $filename = 'whatsapp-media/' . uniqid() . '.' . ($data['extension'] ?? 'bin');
                    Storage::disk('public')->put($filename, base64_decode($data['media']));

                    return [
                        'success' => true,
                        'filename' => $filename,
                        'url' => Storage::disk('public')->url($filename),
                        'mimetype' => $data['mimetype'] ?? 'application/octet-stream'
                    ];
                }
            }

            return ['success' => false, 'message' => 'Failed to download media'];
        } catch (Exception $e) {
            Log::error('Failed to download WhatsApp media', [
                'error' => $e->getMessage(),
                'messageId' => $messageId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract phone number from WhatsApp chat ID
     */
    public static function extractPhoneNumber($chatId)
    {
        return str_replace(['@c.us', '@g.us'], '', $chatId);
    }
}
