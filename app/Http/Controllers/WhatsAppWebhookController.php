<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhook.
     */
    public function handle(Request $request): Response
    {
        Log::info('WhatsApp webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed');
            return response('Unauthorized', 401);
        }

        try {
            $data = $request->all();
            
            // Process the webhook data
            $this->processWebhookData($data);
            
            return response('OK', 200);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response('Internal Server Error', 500);
        }
    }

    /**
     * Verify webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $config = WhatsAppConfig::getActive();
        
        if (!$config || !$config->webhook_secret) {
            // If no webhook secret is configured, skip verification
            return true;
        }

        $signature = $request->header('X-Signature');
        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $config->webhook_secret);
        
        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Process webhook data.
     */
    protected function processWebhookData(array $data): void
    {
        // Handle different types of webhook events
        if (isset($data['event'])) {
            switch ($data['event']) {
                case 'message':
                    $this->handleIncomingMessage($data);
                    break;
                    
                case 'message_status':
                    $this->handleMessageStatus($data);
                    break;
                    
                case 'connection':
                    $this->handleConnectionStatus($data);
                    break;
                    
                default:
                    Log::info('Unknown webhook event type', ['event' => $data['event']]);
            }
        }
    }

    /**
     * Handle incoming message.
     */
    protected function handleIncomingMessage(array $data): void
    {
        if (!isset($data['message'])) {
            return;
        }

        $message = $data['message'];
        
        // Store incoming message
        WhatsAppMessage::create([
            'phone_number' => $message['from'] ?? '',
            'message_type' => $message['type'] ?? 'text',
            'content' => $message['body'] ?? $message['caption'] ?? '',
            'status' => 'received',
            'direction' => 'incoming',
            'api_response' => $data,
            'received_at' => now(),
        ]);

        Log::info('Incoming WhatsApp message processed', [
            'from' => $message['from'] ?? '',
            'type' => $message['type'] ?? 'text',
        ]);
    }

    /**
     * Handle message status update.
     */
    protected function handleMessageStatus(array $data): void
    {
        if (!isset($data['message_id']) || !isset($data['status'])) {
            return;
        }

        // Find and update message status
        $message = WhatsAppMessage::where('api_message_id', $data['message_id'])->first();
        
        if ($message) {
            $message->update([
                'status' => $data['status'],
                'status_updated_at' => now(),
            ]);

            Log::info('WhatsApp message status updated', [
                'message_id' => $data['message_id'],
                'status' => $data['status'],
            ]);
        }
    }

    /**
     * Handle connection status update.
     */
    protected function handleConnectionStatus(array $data): void
    {
        $config = WhatsAppConfig::getActive();
        
        if ($config) {
            $config->updateConnectionStatus([
                'connected' => $data['connected'] ?? false,
                'status' => $data['status'] ?? 'unknown',
                'devices' => $data['devices'] ?? [],
                'last_check' => now()->toISOString(),
                'webhook_update' => true,
            ]);

            Log::info('WhatsApp connection status updated via webhook', [
                'connected' => $data['connected'] ?? false,
                'status' => $data['status'] ?? 'unknown',
            ]);
        }
    }
}
