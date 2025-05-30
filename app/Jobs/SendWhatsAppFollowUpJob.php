<?php

namespace App\Jobs;

use App\Models\WhatsAppFollowUpMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class SendWhatsAppFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    protected WhatsAppFollowUpMessage $followUpMessage;

    /**
     * Create a new job instance.
     */
    public function __construct(WhatsAppFollowUpMessage $followUpMessage)
    {
        $this->followUpMessage = $followUpMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            // Check if message is still pending
            if ($this->followUpMessage->status !== 'pending') {
                Log::info('Follow-up message is not pending, skipping', [
                    'message_id' => $this->followUpMessage->id,
                    'status' => $this->followUpMessage->status
                ]);
                return;
            }

            // Check if scheduled time has arrived
            if ($this->followUpMessage->scheduled_at && $this->followUpMessage->scheduled_at->isFuture()) {
                Log::info('Follow-up message is scheduled for future, re-queuing', [
                    'message_id' => $this->followUpMessage->id,
                    'scheduled_at' => $this->followUpMessage->scheduled_at
                ]);
                
                // Re-queue for the scheduled time
                $this->release($this->followUpMessage->scheduled_at->diffInSeconds(now()));
                return;
            }

            Log::info('Sending WhatsApp follow-up message', [
                'message_id' => $this->followUpMessage->id,
                'customer_id' => $this->followUpMessage->customer_id,
                'phone' => $this->followUpMessage->phone
            ]);

            // Send the message
            $response = $whatsappService->sendMessage(
                $this->followUpMessage->phone,
                $this->followUpMessage->message_content
            );

            // Check if sending was successful
            if (isset($response['success']) && $response['success']) {
                $this->followUpMessage->markAsSent(
                    $response['messageId'] ?? null,
                    $response
                );

                Log::info('WhatsApp follow-up message sent successfully', [
                    'message_id' => $this->followUpMessage->id,
                    'whatsapp_message_id' => $response['messageId'] ?? null
                ]);
            } else {
                throw new Exception('WhatsApp API returned unsuccessful response: ' . json_encode($response));
            }

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp follow-up message', [
                'message_id' => $this->followUpMessage->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Mark as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $this->followUpMessage->markAsFailed(
                    $e->getMessage(),
                    ['last_attempt' => $this->attempts(), 'error' => $e->getMessage()]
                );
            }

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('WhatsApp follow-up job failed permanently', [
            'message_id' => $this->followUpMessage->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark message as failed if not already marked
        if ($this->followUpMessage->status === 'pending') {
            $this->followUpMessage->markAsFailed(
                $exception->getMessage(),
                ['final_failure' => true, 'attempts' => $this->attempts()]
            );
        }
    }
}
