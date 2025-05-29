<?php

namespace App\Listeners;

use App\Events\ServiceStatusChanged;
use App\Models\FollowUpTemplate;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendWhatsAppFollowUp
{
    protected WhatsAppService $whatsappService;

    /**
     * Create the event listener.
     */
    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the event.
     */
    public function handle(ServiceStatusChanged $event): void
    {
        $service = $event->service;
        $previousStatus = $event->previousStatus;

        Log::info('SendWhatsAppFollowUp: Processing service status change', [
            'service_id' => $service->id,
            'previous_status' => $previousStatus,
            'current_status' => $service->status,
        ]);

        // Only send follow-up when service is completed
        if ($service->status !== 'completed') {
            Log::info('SendWhatsAppFollowUp: Service not completed, skipping WhatsApp follow-up', [
                'service_id' => $service->id,
                'status' => $service->status,
            ]);
            return;
        }

        // Check if WhatsApp service is available
        if (!$this->whatsappService->isAvailable()) {
            Log::warning('SendWhatsAppFollowUp: WhatsApp service not available', [
                'service_id' => $service->id,
            ]);
            return;
        }

        // Check if customer has phone number
        $customer = $service->customer;
        if (!$customer || !$customer->phone) {
            Log::warning('SendWhatsAppFollowUp: Customer phone number not found', [
                'service_id' => $service->id,
                'customer_id' => $customer?->id,
            ]);
            return;
        }

        // Get active WhatsApp-enabled follow-up templates for service completion
        $templates = FollowUpTemplate::active()
            ->whatsAppEnabled()
            ->autoSend()
            ->forTrigger('service_completion')
            ->get();

        if ($templates->isEmpty()) {
            Log::info('SendWhatsAppFollowUp: No active WhatsApp follow-up templates found', [
                'service_id' => $service->id,
            ]);
            return;
        }

        Log::info('SendWhatsAppFollowUp: Found follow-up templates', [
            'service_id' => $service->id,
            'templates_count' => $templates->count(),
            'template_ids' => $templates->pluck('id')->toArray(),
        ]);

        // Send follow-up message for each template
        foreach ($templates as $template) {
            try {
                // Check if there's a delay configured
                if ($template->delay_minutes > 0) {
                    Log::info('SendWhatsAppFollowUp: Template has delay, scheduling for later', [
                        'service_id' => $service->id,
                        'template_id' => $template->id,
                        'delay_minutes' => $template->delay_minutes,
                    ]);
                    
                    // TODO: Implement job scheduling for delayed messages
                    // For now, we'll send immediately
                }

                $result = $this->whatsappService->sendFollowUpMessage($service, $template);

                if ($result['success']) {
                    // Update template usage statistics
                    $template->incrementUsage();
                    $template->incrementWhatsAppUsage();

                    Log::info('SendWhatsAppFollowUp: Follow-up message sent successfully', [
                        'service_id' => $service->id,
                        'template_id' => $template->id,
                        'customer_phone' => $customer->phone,
                        'whatsapp_message_id' => $result['whatsapp_message_id'] ?? null,
                    ]);
                } else {
                    Log::error('SendWhatsAppFollowUp: Failed to send follow-up message', [
                        'service_id' => $service->id,
                        'template_id' => $template->id,
                        'customer_phone' => $customer->phone,
                        'error' => $result['message'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('SendWhatsAppFollowUp: Exception while sending follow-up message', [
                    'service_id' => $service->id,
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
