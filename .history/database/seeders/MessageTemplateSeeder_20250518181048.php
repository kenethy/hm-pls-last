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
            'content' => "<p>Halo {customer_name},</p>
<p>Terima kasih telah mempercayakan servis {service_type} untuk kendaraan {vehicle_model} ({license_plate}) Anda kepada Hartono Motor.</p>
<p>Kami ingin mengetahui pendapat Anda tentang layanan kami.</p>
<p>Mohon berikan penilaian Anda dengan membalas pesan ini dengan angka 1-5:</p>
<p>1: Sangat Tidak Puas<br>
2: Tidak Puas<br>
3: Cukup<br>
4: Puas<br>
5: Sangat Puas</p>
<p>Kami juga sangat menghargai saran dan masukan Anda untuk meningkatkan kualitas layanan kami.</p>
<p>Terima kasih,<br>
Tim Hartono Motor</p>",
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create default promo template
        MessageTemplate::create([
            'name' => 'Tawarkan Promo',
            'type' => 'promo',
            'content' => "<p>Halo {customer_name},</p>
<p>Terima kasih telah mempercayakan servis {service_type} untuk kendaraan {vehicle_model} ({license_plate}) Anda kepada Hartono Motor.</p>
<p>Sebagai pelanggan setia kami, Anda berhak mendapatkan:</p>
<p>✨ DISKON 10% ✨</p>
<p>Untuk servis berikutnya dalam 3 bulan ke depan.</p>
<p>Gunakan kode promo: <b>HARTONO10</b></p>
<p>Jangan lewatkan kesempatan ini untuk merawat kendaraan Anda dengan harga spesial!</p>
<p>Terima kasih,<br>
Tim Hartono Motor</p>",
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create a detailed service report template
        MessageTemplate::create([
            'name' => 'Laporan Servis Detail',
            'type' => 'custom',
            'content' => "<p>Halo {customer_name},</p>
<p>Terima kasih telah mempercayakan kendaraan Anda kepada Hartono Motor.</p>
<p><b>LAPORAN SERVIS</b></p>
<p>Tanggal: {service_date}<br>
Kendaraan: {vehicle_model}<br>
Plat Nomor: {license_plate}<br>
Jenis Servis: {service_type}<br>
Deskripsi: {service_description}<br>
Montir: {mechanic_names}<br>
Nomor Nota: {invoice_number}</p>
<p><b>TOTAL BIAYA: Rp {service_cost}</b></p>
<p>Kami telah menyelesaikan servis kendaraan Anda sesuai dengan standar kualitas Hartono Motor. Jika Anda memiliki pertanyaan atau membutuhkan informasi lebih lanjut, jangan ragu untuk menghubungi kami.</p>
<p>Terima kasih atas kepercayaan Anda.</p>
<p>Salam,<br>
Tim Hartono Motor</p>",
            'is_default' => false,
            'is_active' => true,
        ]);
    }
}
