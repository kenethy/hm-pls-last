<?php

namespace App\Services;

use App\Models\WhatsAppConfig;
use App\Models\WhatsAppMessage;
use App\Models\Customer;
use App\Models\Service;
use App\Models\FollowUpTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected ?WhatsAppConfig $config;

    public function __construct()
    {
        $this->config = WhatsAppConfig::getActive();
    }

    /**
     * Check if WhatsApp service is available.
     */
    public function isAvailable(): bool
    {
        return $this->config && $this->config->is_active;
    }

    /**
     * Test connection to WhatsApp API.
     */
    public function testConnection(): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'WhatsApp configuration not found or inactive',
            ];
        }

        try {
            $response = $this->makeApiRequest('GET', '/app/devices');
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->config->updateConnectionStatus([
                    'connected' => true,
                    'status' => 'Connected',
                    'devices' => $data['results'] ?? [],
                    'last_check' => now()->toISOString(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'API returned error: ' . $response->body(),
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp connection test failed', [
                'error' => $e->getMessage(),
                'config_id' => $this->config->id,
            ]);

            $this->config->updateConnectionStatus([
                'connected' => false,
                'status' => 'Connection failed',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a text message.
     */
    public function sendTextMessage(
        string $phoneNumber,
        string $message,
        ?int $serviceId = null,
        ?int $customerId = null,
        ?int $followUpTemplateId = null,
        bool $isAutomated = false,
        string $triggeredBy = 'manual'
    ): array {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'WhatsApp service not available',
            ];
        }

        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);

        // Create message record
        $whatsappMessage = WhatsAppMessage::create([
            'phone_number' => $phoneNumber,
            'message_type' => 'text',
            'content' => $message,
            'status' => 'pending',
            'service_id' => $serviceId,
            'customer_id' => $customerId,
            'follow_up_template_id' => $followUpTemplateId,
            'is_automated' => $isAutomated,
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $response = $this->makeApiRequest('POST', '/send/message', [
                'phone' => $formattedPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $whatsappMessage->update([
                    'api_response' => $data,
                ]);

                $whatsappMessage->markAsSent($data['results']['message_id'] ?? null);

                Log::info('WhatsApp message sent successfully', [
                    'message_id' => $whatsappMessage->id,
                    'phone' => $phoneNumber,
                    'service_id' => $serviceId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $data,
                    'whatsapp_message_id' => $whatsappMessage->id,
                ];
            }

            $errorMessage = 'API error: ' . $response->body();
            $whatsappMessage->markAsFailed($errorMessage);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (Exception $e) {
            $errorMessage = 'Exception: ' . $e->getMessage();
            $whatsappMessage->markAsFailed($errorMessage);

            Log::error('WhatsApp message sending failed', [
                'error' => $e->getMessage(),
                'message_id' => $whatsappMessage->id,
                'phone' => $phoneNumber,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        }
    }

    /**
     * Send follow-up message using template.
     */
    public function sendFollowUpMessage(
        Service $service,
        FollowUpTemplate $template
    ): array {
        if (!$template->whatsapp_enabled) {
            return [
                'success' => false,
                'message' => 'WhatsApp not enabled for this template',
            ];
        }

        $customer = $service->customer;
        if (!$customer || !$customer->phone) {
            return [
                'success' => false,
                'message' => 'Customer phone number not found',
            ];
        }

        // Replace template variables
        $message = $this->replaceTemplateVariables($template->message, $service);

        return $this->sendTextMessage(
            phoneNumber: $customer->phone,
            message: $message,
            serviceId: $service->id,
            customerId: $customer->id,
            followUpTemplateId: $template->id,
            isAutomated: true,
            triggeredBy: 'service_completion'
        );
    }

    /**
     * Format phone number for WhatsApp API.
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if not present (assuming Indonesia +62)
        if (!str_starts_with($cleaned, '62')) {
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '62' . substr($cleaned, 1);
            } else {
                $cleaned = '62' . $cleaned;
            }
        }
        
        return $cleaned . '@s.whatsapp.net';
    }

    /**
     * Replace template variables with actual values.
     */
    protected function replaceTemplateVariables(string $template, Service $service): string
    {
        $variables = [
            '{customer_name}' => $service->customer->name ?? 'Customer',
            '{service_type}' => $service->service_type ?? 'Service',
            '{vehicle_info}' => $service->vehicle_info ?? 'Vehicle',
            '{completion_date}' => $service->updated_at->format('d/m/Y H:i'),
            '{total_cost}' => 'Rp ' . number_format($service->total_cost ?? 0, 0, ',', '.'),
            '{workshop_name}' => 'Hartono Motor',
            '{workshop_phone}' => config('app.workshop_phone', ''),
            '{workshop_address}' => config('app.workshop_address', ''),
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    /**
     * Make API request to WhatsApp service.
     */
    protected function makeApiRequest(string $method, string $endpoint, array $data = []): \Illuminate\Http\Client\Response
    {
        $url = $this->config->getApiEndpoint($endpoint);
        $request = Http::timeout(30);

        // Add basic auth if configured
        if ($credentials = $this->config->getBasicAuthCredentials()) {
            $request = $request->withBasicAuth($credentials['username'], $credentials['password']);
        }

        return $request->$method($url, $data);
    }
}
