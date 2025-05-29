<?php

namespace Database\Seeders;

use App\Models\WhatsAppConfig;
use App\Models\FollowUpTemplate;
use Illuminate\Database\Seeder;

class WhatsAppIntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default WhatsApp configuration
        WhatsAppConfig::create([
            'name' => 'Default WhatsApp Config',
            'api_url' => 'http://localhost:3000',
            'api_username' => 'admin',
            'api_password' => 'admin',
            'webhook_secret' => 'secret',
            'webhook_url' => url('/api/whatsapp/webhook'),
            'is_active' => true,
            'auto_reply_enabled' => false,
            'notes' => 'Konfigurasi default untuk WhatsApp API',
        ]);

        // Create follow-up templates
        $templates = [
            [
                'name' => 'Terima Kasih Servis Selesai',
                'message' => "Halo {customer_name}! 👋\n\nTerima kasih telah mempercayakan {vehicle_info} Anda kepada Hartono Motor.\n\nServis {service_type} telah selesai pada {completion_date} dengan total biaya {total_cost}.\n\nJika ada pertanyaan atau keluhan, jangan ragu untuk menghubungi kami.\n\nSalam,\nTim Hartono Motor 🔧",
                'description' => 'Pesan terima kasih setelah servis selesai',
                'trigger_event' => 'service_completion',
                'is_active' => true,
                'whatsapp_enabled' => true,
                'whatsapp_message_type' => 'text',
                'auto_send_on_completion' => true,
                'delay_minutes' => 0,
            ],
            [
                'name' => 'Follow-up Kepuasan Pelanggan',
                'message' => "Halo {customer_name}! 😊\n\nBagaimana kondisi {vehicle_info} setelah servis {service_type} di Hartono Motor?\n\nKami berharap Anda puas dengan layanan kami. Jika ada masalah atau saran, silakan beritahu kami.\n\nTerima kasih atas kepercayaan Anda!\n\n{workshop_name}\n📞 {workshop_phone}",
                'description' => 'Follow-up kepuasan pelanggan 1 hari setelah servis',
                'trigger_event' => 'service_completion',
                'is_active' => true,
                'whatsapp_enabled' => true,
                'whatsapp_message_type' => 'text',
                'auto_send_on_completion' => false,
                'delay_minutes' => 1440, // 24 hours
            ],
            [
                'name' => 'Pengingat Servis Berkala',
                'message' => "Halo {customer_name}! 🚗\n\nSudah waktunya untuk servis berkala {vehicle_info} Anda.\n\nUntuk menjaga performa optimal kendaraan, kami sarankan melakukan servis rutin setiap 3-6 bulan.\n\nHubungi kami untuk jadwal servis:\n📞 {workshop_phone}\n📍 {workshop_address}\n\nTerima kasih,\nHartono Motor",
                'description' => 'Pengingat servis berkala untuk pelanggan',
                'trigger_event' => 'custom',
                'is_active' => true,
                'whatsapp_enabled' => true,
                'whatsapp_message_type' => 'text',
                'auto_send_on_completion' => false,
                'delay_minutes' => 0,
            ],
            [
                'name' => 'Konfirmasi Booking',
                'message' => "Halo {customer_name}! ✅\n\nBooking servis Anda telah dikonfirmasi:\n\n🚗 Kendaraan: {vehicle_info}\n🔧 Jenis Servis: {service_type}\n📅 Tanggal: {completion_date}\n\nMohon datang tepat waktu. Jika ada perubahan, silakan hubungi kami.\n\nTerima kasih,\nHartono Motor\n📞 {workshop_phone}",
                'description' => 'Konfirmasi booking servis',
                'trigger_event' => 'booking_confirmation',
                'is_active' => true,
                'whatsapp_enabled' => true,
                'whatsapp_message_type' => 'text',
                'auto_send_on_completion' => false,
                'delay_minutes' => 0,
            ],
        ];

        foreach ($templates as $template) {
            FollowUpTemplate::create($template);
        }
    }
}
