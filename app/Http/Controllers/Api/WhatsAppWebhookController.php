<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppFollowUpMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhook
     */
    public function handle(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('WhatsApp webhook received', [
                'data' => $data,
                'headers' => $request->headers->all()
            ]);

            // Validate API key if configured
            $expectedApiKey = config('services.whatsapp.api_key');
            if ($expectedApiKey && $request->header('X-API-Key') !== $expectedApiKey) {
                Log::warning('WhatsApp webhook unauthorized', [
                    'provided_key' => $request->header('X-API-Key'),
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Handle different webhook events
            if (isset($data['event'])) {
                switch ($data['event']) {
                    case 'message':
                        $this->handleMessageEvent($data);
                        break;
                    case 'message_ack':
                        $this->handleMessageAckEvent($data);
                        break;
                    case 'qr':
                        $this->handleQrEvent($data);
                        break;
                    case 'ready':
                        $this->handleReadyEvent($data);
                        break;
                    case 'disconnected':
                        $this->handleDisconnectedEvent($data);
                        break;
                    default:
                        Log::info('Unknown WhatsApp webhook event', ['event' => $data['event']]);
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle incoming message event
     */
    private function handleMessageEvent(array $data)
    {
        Log::info('WhatsApp message received', $data);
        
        // You can implement auto-reply logic here if needed
        // For now, just log the incoming message
    }

    /**
     * Handle message acknowledgment event
     */
    private function handleMessageAckEvent(array $data)
    {
        if (!isset($data['messageId']) || !isset($data['ack'])) {
            return;
        }

        $messageId = $data['messageId'];
        $ackType = $data['ack'];

        Log::info('WhatsApp message ACK received', [
            'message_id' => $messageId,
            'ack_type' => $ackType
        ]);

        // Find the follow-up message by WhatsApp message ID
        $followUpMessage = WhatsAppFollowUpMessage::where('whatsapp_message_id', $messageId)->first();

        if ($followUpMessage) {
            // Update message status based on ACK type
            switch ($ackType) {
                case 1: // Message sent to server
                    Log::info('Message sent to server', ['follow_up_id' => $followUpMessage->id]);
                    break;
                case 2: // Message delivered to device
                    Log::info('Message delivered to device', ['follow_up_id' => $followUpMessage->id]);
                    break;
                case 3: // Message read by user
                    Log::info('Message read by user', ['follow_up_id' => $followUpMessage->id]);
                    break;
                case -1: // Message failed
                    $followUpMessage->markAsFailed('Message delivery failed (ACK: -1)');
                    Log::warning('Message delivery failed', ['follow_up_id' => $followUpMessage->id]);
                    break;
            }

            // Update response data with ACK information
            $responseData = $followUpMessage->response_data ?? [];
            $responseData['ack_history'][] = [
                'ack_type' => $ackType,
                'timestamp' => now()->toISOString()
            ];
            $followUpMessage->update(['response_data' => $responseData]);
        }
    }

    /**
     * Handle QR code event
     */
    private function handleQrEvent(array $data)
    {
        Log::info('WhatsApp QR code generated', $data);
        
        // You could store QR code data in cache for the admin panel
        if (isset($data['qr'])) {
            cache()->put('whatsapp_qr_code', $data['qr'], now()->addMinutes(5));
        }
    }

    /**
     * Handle ready event (WhatsApp connected)
     */
    private function handleReadyEvent(array $data)
    {
        Log::info('WhatsApp session ready', $data);
        
        // Update session status in cache
        cache()->put('whatsapp_session_status', [
            'connected' => true,
            'ready_at' => now()->toISOString(),
            'session_data' => $data
        ], now()->addHours(24));
    }

    /**
     * Handle disconnected event
     */
    private function handleDisconnectedEvent(array $data)
    {
        Log::warning('WhatsApp session disconnected', $data);
        
        // Update session status in cache
        cache()->put('whatsapp_session_status', [
            'connected' => false,
            'disconnected_at' => now()->toISOString(),
            'reason' => $data['reason'] ?? 'Unknown'
        ], now()->addHours(24));
    }
}
