<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsAppConfig;
use App\Models\FollowUpTemplate;

class WhatsAppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default WhatsApp configuration if none exists
        if (WhatsAppConfig::count() === 0) {
            WhatsAppConfig::create([
                'name' => 'Default WhatsApp Config',
                'api_url' => 'http://whatsapp-api:3000',
                'api_username' => null,
                'api_password' => null,
                'webhook_secret' => 'secret',
                'webhook_url' => null,
                'is_active' => true,
                'auto_reply_enabled' => false,
                'auto_reply_message' => null,
                'notes' => 'Default configuration for WhatsApp API integration',
            ]);
        }
    }
}
