<?php

namespace Database\Seeders;

use App\Models\ServiceReportTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceReportTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default template
        ServiceReportTemplate::create([
            'name' => 'Template Standar Napas Baru Premium',
            'vehicle_type' => 'Semua Tipe',
            'description' => 'Template standar untuk paket layanan Napas Baru Premium',
            'is_default' => true,
            'checklist_items' => [
                // Mesin
                ['inspection_point' => 'Kondisi Oli Mesin'],
                ['inspection_point' => 'Filter Oli'],
                ['inspection_point' => 'Filter Udara'],
                ['inspection_point' => 'Filter Bahan Bakar'],
                ['inspection_point' => 'Kebocoran Oli'],
                ['inspection_point' => 'Kondisi Belt'],
                ['inspection_point' => 'Sistem Pendingin'],
                ['inspection_point' => 'Radiator & Selang'],
                ['inspection_point' => 'Kondisi Air Radiator'],
                ['inspection_point' => 'Kondisi Busi'],
                
                // Sistem Bahan Bakar
                ['inspection_point' => 'Injektor Bahan Bakar'],
                ['inspection_point' => 'Throttle Body'],
                ['inspection_point' => 'Selang Bahan Bakar'],
                ['inspection_point' => 'Tangki Bahan Bakar'],
                
                // Sistem Rem
                ['inspection_point' => 'Kondisi Minyak Rem'],
                ['inspection_point' => 'Pad Rem Depan'],
                ['inspection_point' => 'Pad Rem Belakang'],
                ['inspection_point' => 'Cakram Rem'],
                ['inspection_point' => 'Selang Rem'],
                ['inspection_point' => 'Rem Tangan'],
                
                // Sistem Kemudi & Suspensi
                ['inspection_point' => 'Power Steering'],
                ['inspection_point' => 'Tie Rod'],
                ['inspection_point' => 'Ball Joint'],
                ['inspection_point' => 'Bushing Kaki-kaki'],
                ['inspection_point' => 'Shock Absorber Depan'],
                ['inspection_point' => 'Shock Absorber Belakang'],
                ['inspection_point' => 'Per/Pegas'],
                
                // Sistem Transmisi
                ['inspection_point' => 'Kondisi Oli Transmisi'],
                ['inspection_point' => 'Kebocoran Transmisi'],
                ['inspection_point' => 'Kopling'],
                ['inspection_point' => 'Propeller Shaft'],
                ['inspection_point' => 'CV Joint & Boot'],
                
                // Sistem Kelistrikan
                ['inspection_point' => 'Baterai'],
                ['inspection_point' => 'Terminal Baterai'],
                ['inspection_point' => 'Alternator'],
                ['inspection_point' => 'Starter'],
                ['inspection_point' => 'Lampu Depan'],
                ['inspection_point' => 'Lampu Belakang'],
                ['inspection_point' => 'Lampu Sein'],
                ['inspection_point' => 'Lampu Rem'],
                ['inspection_point' => 'Lampu Interior'],
                ['inspection_point' => 'Klakson'],
                ['inspection_point' => 'Wiper'],
                
                // Ban & Velg
                ['inspection_point' => 'Kondisi Ban Depan'],
                ['inspection_point' => 'Kondisi Ban Belakang'],
                ['inspection_point' => 'Tekanan Angin Ban'],
                ['inspection_point' => 'Kondisi Velg'],
                
                // Eksterior & Interior
                ['inspection_point' => 'Kaca Depan & Belakang'],
                ['inspection_point' => 'Kaca Spion'],
                ['inspection_point' => 'Kondisi Body'],
                ['inspection_point' => 'Pintu & Kunci'],
                ['inspection_point' => 'AC & Sistem Pendingin Kabin'],
            ],
            'services_performed' => [
                [
                    'service_name' => 'Tune Up Mesin',
                    'description' => 'Penyetelan dan pembersihan komponen mesin untuk performa optimal'
                ],
                [
                    'service_name' => 'Gurah Mesin',
                    'description' => 'Pembersihan menyeluruh pada ruang bakar dan sistem bahan bakar'
                ],
                [
                    'service_name' => 'Ganti Oli & Filter',
                    'description' => 'Penggantian oli mesin dan filter oli dengan kualitas terbaik'
                ],
                [
                    'service_name' => 'Pembersihan Throttle Body',
                    'description' => 'Pembersihan throttle body untuk aliran udara yang lebih baik'
                ],
            ],
            'warranty_info' => '<p>Garansi Tune-Up 1 Minggu</p><p>Syarat dan ketentuan berlaku:</p><ul><li>Garansi berlaku untuk masalah yang sama pada komponen yang telah diservis</li><li>Kerusakan akibat penggunaan yang tidak normal tidak termasuk dalam garansi</li><li>Klaim garansi harus disertai dengan bukti laporan servis ini</li></ul>',
            'recommendations' => '<p>Rekomendasi perawatan rutin:</p><ul><li>Ganti oli mesin setiap 5.000 km</li><li>Periksa tekanan ban setiap 2 minggu</li><li>Lakukan tune up mesin setiap 10.000 km</li><li>Periksa sistem rem setiap 10.000 km</li></ul>',
        ]);
    }
}
