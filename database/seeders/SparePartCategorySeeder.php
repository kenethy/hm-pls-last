<?php

namespace Database\Seeders;

use App\Models\SparePartCategory;
use Illuminate\Database\Seeder;

class SparePartCategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Mesin',
                'slug' => 'mesin',
                'description' => 'Komponen mesin dan sistem pembakaran',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => '#dc2626',
                'order' => 1,
                'meta_title' => 'Sparepart Mesin Mobil - Hartono Motor',
                'meta_description' => 'Jual sparepart mesin mobil berkualitas. Filter oli, busi, timing belt, dan komponen mesin lainnya.',
                'meta_keywords' => 'sparepart mesin, filter oli, busi, timing belt, komponen mesin',
            ],
            [
                'name' => 'Oli & Cairan',
                'slug' => 'oli-cairan',
                'description' => 'Oli mesin, transmisi, dan cairan kendaraan',
                'icon' => 'heroicon-o-beaker',
                'color' => '#059669',
                'order' => 2,
                'meta_title' => 'Oli Mesin & Cairan Mobil - Hartono Motor',
                'meta_description' => 'Oli mesin berkualitas untuk semua jenis mobil. Shell, Castrol, Pertamina dengan harga terbaik.',
                'meta_keywords' => 'oli mesin, oli transmisi, coolant, brake fluid',
            ],
            [
                'name' => 'Rem',
                'slug' => 'rem',
                'description' => 'Sistem pengereman dan komponen rem',
                'icon' => 'heroicon-o-stop-circle',
                'color' => '#dc2626',
                'order' => 3,
                'meta_title' => 'Sparepart Rem Mobil - Hartono Motor',
                'meta_description' => 'Kampas rem, cakram rem, minyak rem berkualitas untuk keamanan berkendara.',
                'meta_keywords' => 'kampas rem, cakram rem, brake pad, disc brake',
            ],
            [
                'name' => 'Kopling',
                'slug' => 'kopling',
                'description' => 'Sistem kopling dan transmisi',
                'icon' => 'heroicon-o-arrow-path',
                'color' => '#7c3aed',
                'order' => 4,
                'meta_title' => 'Sparepart Kopling Mobil - Hartono Motor',
                'meta_description' => 'Kampas kopling, plat kopling, bearing kopling original dan aftermarket.',
                'meta_keywords' => 'kampas kopling, plat kopling, clutch disc, clutch cover',
            ],
            [
                'name' => 'Suspensi',
                'slug' => 'suspensi',
                'description' => 'Sistem suspensi dan kemudi',
                'icon' => 'heroicon-o-arrows-up-down',
                'color' => '#ea580c',
                'order' => 5,
                'meta_title' => 'Sparepart Suspensi Mobil - Hartono Motor',
                'meta_description' => 'Shock absorber, per, ball joint, tie rod untuk kenyamanan berkendara.',
                'meta_keywords' => 'shock absorber, per mobil, ball joint, tie rod',
            ],
            [
                'name' => 'Kelistrikan',
                'slug' => 'kelistrikan',
                'description' => 'Komponen kelistrikan dan elektronik',
                'icon' => 'heroicon-o-bolt',
                'color' => '#eab308',
                'order' => 6,
                'meta_title' => 'Sparepart Kelistrikan Mobil - Hartono Motor',
                'meta_description' => 'Aki, alternator, starter, lampu, dan komponen kelistrikan mobil.',
                'meta_keywords' => 'aki mobil, alternator, starter, lampu mobil',
            ],
        ];

        foreach ($categories as $category) {
            SparePartCategory::create($category);
        }
    }
}
