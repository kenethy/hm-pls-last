<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default follow-up template
        MessageTemplate::create([
            'name' => 'Follow-up Standar',
            'type' => 'follow_up',
            'content' => "<p>Halo {customer_name},</p>
<p>Terima kasih telah mempercayakan kendaraan Anda kepada Hartono Motor.</p>
<p>Detail servis:</p>
<p>- Jenis: {service_type}<br>
- Kendaraan: {vehicle_model} ({license_plate})<br>
- Nomor Nota: {invoice_number}<br>
- Biaya: Rp {service_cost}</p>
<p>Bagaimana kondisi kendaraan Anda setelah servis? Apakah ada masalah atau pertanyaan yang ingin Anda sampaikan?</p>
<p>Kami sangat menghargai umpan balik Anda untuk meningkatkan layanan kami.</p>
<p>Terima kasih,<br>
Tim Hartono Motor</p>",
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create default feedback template
        MessageTemplate::create([
            'name' => 'Minta Feedback',
            'type' => 'feedback',
            'content' => "Halo {customer_name},\n\n" .
                "Terima kasih telah mempercayakan servis {service_type} untuk mobil {vehicle_model} Anda kepada Hartono Motor.\n\n" .
                "Kami ingin mengetahui pendapat Anda tentang layanan kami. Mohon berikan penilaian Anda dengan membalas pesan ini dengan angka 1-5 (1: Sangat Tidak Puas, 5: Sangat Puas).\n\n" .
                "Kami juga sangat menghargai saran dan masukan Anda untuk meningkatkan kualitas layanan kami.\n\n" .
                "Terima kasih,\nTim Hartono Motor",
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create default promo template
        MessageTemplate::create([
            'name' => 'Tawarkan Promo',
            'type' => 'promo',
            'content' => "Halo {customer_name},\n\n" .
                "Terima kasih telah mempercayakan servis {service_type} untuk mobil {vehicle_model} Anda kepada Hartono Motor.\n\n" .
                "Sebagai pelanggan setia kami, Anda berhak mendapatkan DISKON 10% untuk servis berikutnya dalam 3 bulan ke depan.\n\n" .
                "Gunakan kode promo: HARTONO10\n\n" .
                "Jangan lewatkan kesempatan ini untuk merawat kendaraan Anda dengan harga spesial!\n\n" .
                "Terima kasih,\nTim Hartono Motor",
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
