<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\WhatsAppFollowUpMessage;
use App\Jobs\SendWhatsAppFollowUpJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAutomaticFollowUps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send-follow-ups 
                            {--limit=10 : Maximum number of follow-ups to send}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic WhatsApp follow-up messages to customers who need follow-up';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Starting automatic WhatsApp follow-up process...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No messages will actually be sent');
        }

        // Get default follow-up template
        $template = MessageTemplate::where('type', 'follow_up')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->error('No default follow-up template found. Please create one first.');
            return 1;
        }

        // Get customers needing follow-up
        $customers = Customer::query()
            ->where('is_active', true)
            ->where('service_count', '>', 0)
            ->where(function ($query) {
                $query->whereNull('last_service_date')
                    ->orWhere('last_service_date', '<=', now()->subMonths(3));
            })
            ->whereDoesntHave('whatsappFollowUpMessages', function ($query) {
                $query->where('status', 'sent')
                    ->where('sent_at', '>=', now()->subDays(30)); // Don't send if already sent in last 30 days
            })
            ->limit($limit)
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No customers need follow-up at this time.');
            return 0;
        }

        $this->info("Found {$customers->count()} customers needing follow-up");

        $created = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            try {
                // Get customer's latest service for template variables
                $latestService = $customer->services()->latest()->first();
                
                if (!$latestService) {
                    $this->warn("Customer {$customer->name} has no services, skipping");
                    $skipped++;
                    continue;
                }

                // Generate message content
                $messageContent = $template->getFormattedContent($latestService);

                if ($dryRun) {
                    $this->line("Would send to {$customer->name} ({$customer->phone}):");
                    $this->line("Message: " . substr($messageContent, 0, 100) . "...");
                    $this->line("---");
                    continue;
                }

                // Create follow-up message record
                $followUpMessage = WhatsAppFollowUpMessage::create([
                    'service_id' => $latestService->id,
                    'customer_id' => $customer->id,
                    'message_template_id' => $template->id,
                    'phone' => $customer->phone,
                    'message_content' => $messageContent,
                    'scheduled_at' => now(), // Send immediately
                    'status' => 'pending',
                ]);

                // Queue the job to send the message
                SendWhatsAppFollowUpJob::dispatch($followUpMessage);

                $this->info("Queued follow-up for {$customer->name} ({$customer->phone})");
                $created++;

                // Small delay to prevent overwhelming the system
                usleep(100000); // 0.1 second

            } catch (\Exception $e) {
                $this->error("Failed to create follow-up for {$customer->name}: {$e->getMessage()}");
                $skipped++;
                
                Log::error('Failed to create automatic follow-up', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!$dryRun) {
            $this->info("Follow-up process completed:");
            $this->info("- Created: {$created}");
            $this->info("- Skipped: {$skipped}");
            
            Log::info('Automatic follow-up process completed', [
                'created' => $created,
                'skipped' => $skipped,
                'total_customers' => $customers->count()
            ]);
        } else {
            $this->info("Dry run completed. Would have created {$customers->count()} follow-up messages.");
        }

        return 0;
    }
}
