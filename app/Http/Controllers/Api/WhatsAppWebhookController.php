<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhook.
     */
    public function handle(Request $request)
    {
        Log::info('WhatsApp Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp Webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();

        // Process incoming message
        if (isset($data['message'])) {
            $this->processIncomingMessage($data);
        }

        // Process message status update
        if (isset($data['status'])) {
            $this->processStatusUpdate($data);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Verify webhook signature.
     */
    private function verifySignature(Request $request): bool
    {
        $config = WhatsAppConfig::getActive();
        if (!$config || !$config->webhook_secret) {
            return true; // Skip verification if no secret configured
        }

        $signature = $request->header('X-Signature');
        if (!$signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $config->webhook_secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process incoming message.
     */
    private function processIncomingMessage(array $data): void
    {
        $message = $data['message'];
        
        Log::info('Processing incoming WhatsApp message', [
            'from' => $message['from'] ?? 'unknown',
            'type' => $message['type'] ?? 'unknown',
            'content' => $message['body'] ?? '',
        ]);

        // Store incoming message (optional)
        WhatsAppMessage::create([
            'message_id' => $message['id'] ?? null,
            'phone_number' => $this->formatPhoneNumber($message['from'] ?? ''),
            'message_type' => $message['type'] ?? 'text',
            'content' => $message['body'] ?? '',
            'status' => 'received',
            'is_automated' => false,
            'triggered_by' => 'incoming',
        ]);

        // Auto-reply logic (if enabled)
        $this->handleAutoReply($message);
    }

    /**
     * Process message status update.
     */
    private function processStatusUpdate(array $data): void
    {
        $status = $data['status'];
        $messageId = $status['message_id'] ?? null;

        if (!$messageId) {
            return;
        }

        Log::info('Processing WhatsApp status update', [
            'message_id' => $messageId,
            'status' => $status['status'] ?? 'unknown',
        ]);

        // Update message status in database
        $message = WhatsAppMessage::where('message_id', $messageId)->first();
        if ($message) {
            switch ($status['status']) {
                case 'sent':
                    $message->markAsSent();
                    break;
                case 'delivered':
                    $message->markAsDelivered();
                    break;
                case 'read':
                    $message->markAsRead();
                    break;
                case 'failed':
                    $message->markAsFailed($status['error'] ?? 'Unknown error');
                    break;
            }
        }
    }

    /**
     * Handle auto-reply.
     */
    private function handleAutoReply(array $message): void
    {
        $config = WhatsAppConfig::getActive();
        
        if (!$config || !$config->auto_reply_enabled || !$config->auto_reply_message) {
            return;
        }

        // Don't reply to our own messages
        if (isset($message['fromMe']) && $message['fromMe']) {
            return;
        }

        Log::info('Sending auto-reply', [
            'to' => $message['from'],
            'message' => $config->auto_reply_message,
        ]);

        // Send auto-reply (implement based on your WhatsApp service)
        // $whatsappService = new WhatsAppService();
        // $whatsappService->sendTextMessage($message['from'], $config->auto_reply_message);
    }

    /**
     * Format phone number.
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove @s.whatsapp.net suffix
        $cleaned = str_replace('@s.whatsapp.net', '', $phoneNumber);
        
        // Add + prefix if not present
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }
}
