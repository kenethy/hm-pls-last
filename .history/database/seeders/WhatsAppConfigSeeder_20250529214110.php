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
                'webhook_url' => url('/api/whatsapp/webhook'),
                'is_active' => true,
                'auto_reply_enabled' => false,
                'auto_reply_message' => null,
                'notes' => 'Default configuration for WhatsApp API integration',
            ]);
        }

        // Create default follow-up template if none exists for service completion
        if (!FollowUpTemplate::where('trigger_event', 'service_completion')->where('whatsapp_enabled', true)->exists()) {
            FollowUpTemplate::create([
                'name' => 'WhatsApp Service Completion Follow-up',
                'description' => 'Automatic WhatsApp follow-up message sent when service is completed',
                'trigger_event' => 'service_completion',
                'message' => "Halo {customer_name}!\n\nServis {service_type} untuk kendaraan {vehicle_info} telah selesai di Hartono Motor.\n\nTotal biaya: {total_cost}\nTanggal selesai: {completion_date}\n\nTerima kasih telah mempercayakan kendaraan Anda kepada kami. Jika ada pertanyaan, silakan hubungi kami.\n\nSalam,\nTim Hartono Motor",
                'is_active' => true,
                'whatsapp_enabled' => true,
                'whatsapp_message_type' => 'text',
                'auto_send_on_completion' => true,
                'delay_minutes' => 0,
                'available_variables' => [
                    'customer_name' => 'Nama Customer',
                    'service_type' => 'Jenis Servis',
                    'vehicle_info' => 'Informasi Kendaraan',
                    'total_cost' => 'Total Biaya',
                    'completion_date' => 'Tanggal Selesai',
                    'workshop_name' => 'Nama Bengkel',
                ],
            ]);
        }
    }
}
