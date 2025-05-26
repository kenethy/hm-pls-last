<?php

namespace Database\Seeders;

use App\Models\SparePart;
use App\Models\SparePartCategory;
use Illuminate\Database\Seeder;

class SparePartSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get categories
        $oliCategory = SparePartCategory::where('slug', 'oli-cairan')->first();
        $mesinCategory = SparePartCategory::where('slug', 'mesin')->first();
        $remCategory = SparePartCategory::where('slug', 'rem')->first();
        $koplingCategory = SparePartCategory::where('slug', 'kopling')->first();

        $spareParts = [
            [
                'name' => 'Shell Helix Ultra 5W-40',
                'slug' => 'shell-helix-ultra-5w-40',
                'description' => '<p>Oli mesin sintetis premium Shell Helix Ultra 5W-40 memberikan perlindungan maksimal untuk mesin modern. Diformulasikan dengan teknologi PurePlus untuk performa optimal dan efisiensi bahan bakar yang lebih baik.</p><p><strong>Keunggulan:</strong></p><ul><li>Perlindungan mesin hingga 40% lebih baik</li><li>Mengurangi gesekan dan keausan</li><li>Menjaga kebersihan mesin</li><li>Cocok untuk kondisi berkendara ekstrem</li></ul>',
                'short_description' => 'Oli mesin sintetis premium untuk perlindungan maksimal dan performa optimal mesin.',
                'category_id' => $oliCategory->id,
                'brand' => 'Shell',
                'part_number' => 'SH-5W40-4L',
                'price' => 450000,
                'original_price' => 500000,
                'stock_quantity' => 25,
                'minimum_stock' => 5,
                'specifications' => [
                    ['name' => 'Viskositas', 'value' => '5W-40'],
                    ['name' => 'Volume', 'value' => '4 Liter'],
                    ['name' => 'Tipe', 'value' => 'Full Synthetic'],
                    ['name' => 'API Rating', 'value' => 'SN/CF'],
                ],
                'compatibility' => [
                    ['brand' => 'Toyota', 'model' => 'Avanza, Innova, Camry', 'year' => '2010-2023'],
                    ['brand' => 'Honda', 'model' => 'Civic, Accord, CR-V', 'year' => '2012-2023'],
                    ['brand' => 'Mitsubishi', 'model' => 'Pajero, Outlander', 'year' => '2015-2023'],
                ],
                'condition' => 'original',
                'status' => 'active',
                'is_featured' => true,
                'is_best_seller' => true,
                'is_original' => true,
                'order' => 1,
                'warranty_period' => '1 tahun',
                'meta_title' => 'Shell Helix Ultra 5W-40 - Oli Mesin Sintetis Premium',
                'meta_description' => 'Beli Shell Helix Ultra 5W-40 oli mesin sintetis premium di Hartono Motor. Perlindungan maksimal untuk mesin modern dengan harga terbaik.',
                'meta_keywords' => 'shell helix ultra, oli mesin sintetis, 5w-40, oli premium',
                'marketplace_links' => [
                    [
                        'platform' => 'tokopedia',
                        'url' => 'https://www.tokopedia.com/hartono-m/shell-helix-ultra-5w-40',
                    ],
                    [
                        'platform' => 'shopee',
                        'url' => 'https://shopee.co.id/hartono_motor/shell-helix-ultra-5w-40',
                    ],
                    [
                        'platform' => 'lazada',
                        'url' => 'https://www.lazada.co.id/shop/hartono-motor-sidoarjo/shell-helix-ultra',
                    ],
                ],
            ],
            [
                'name' => 'Castrol GTX 20W-50',
                'slug' => 'castrol-gtx-20w-50',
                'description' => '<p>Oli mesin konvensional Castrol GTX 20W-50 dengan teknologi Double Action Formula yang memberikan perlindungan ganda terhadap lumpur dan keausan mesin.</p><p><strong>Manfaat:</strong></p><ul><li>Melindungi dari pembentukan lumpur</li><li>Mengurangi keausan mesin</li><li>Menjaga performa mesin optimal</li><li>Cocok untuk iklim tropis</li></ul>',
                'short_description' => 'Oli mesin konvensional dengan perlindungan ganda untuk mesin yang tahan lama.',
                'category_id' => $oliCategory->id,
                'brand' => 'Castrol',
                'part_number' => 'CTL-20W50-4L',
                'price' => 180000,
                'stock_quantity' => 40,
                'minimum_stock' => 10,
                'specifications' => [
                    ['name' => 'Viskositas', 'value' => '20W-50'],
                    ['name' => 'Volume', 'value' => '4 Liter'],
                    ['name' => 'Tipe', 'value' => 'Conventional'],
                    ['name' => 'API Rating', 'value' => 'SL/CF'],
                ],
                'compatibility' => [
                    ['brand' => 'Toyota', 'model' => 'Kijang, Avanza, Rush', 'year' => '2000-2020'],
                    ['brand' => 'Daihatsu', 'model' => 'Xenia, Terios', 'year' => '2005-2020'],
                    ['brand' => 'Suzuki', 'model' => 'APV, Ertiga', 'year' => '2008-2020'],
                ],
                'condition' => 'original',
                'status' => 'active',
                'is_featured' => true,
                'is_best_seller' => false,
                'is_original' => true,
                'order' => 2,
                'warranty_period' => '6 bulan',
                'meta_title' => 'Castrol GTX 20W-50 - Oli Mesin Konvensional Terpercaya',
                'meta_description' => 'Oli mesin Castrol GTX 20W-50 dengan Double Action Formula. Perlindungan optimal untuk mesin dengan harga ekonomis.',
                'meta_keywords' => 'castrol gtx, oli mesin konvensional, 20w-50, oli murah',
            ],
            [
                'name' => 'NGK Spark Plug Iridium',
                'slug' => 'ngk-spark-plug-iridium',
                'description' => '<p>Busi NGK Iridium dengan teknologi elektroda iridium untuk pembakaran yang lebih sempurna dan tahan lama hingga 100.000 km.</p><p><strong>Keunggulan:</strong></p><ul><li>Elektroda iridium tahan lama</li><li>Pembakaran lebih sempurna</li><li>Hemat bahan bakar</li><li>Performa mesin optimal</li></ul>',
                'short_description' => 'Busi iridium premium untuk pembakaran sempurna dan daya tahan maksimal.',
                'category_id' => $mesinCategory->id,
                'brand' => 'NGK',
                'part_number' => 'NGK-IR-001',
                'price' => 85000,
                'original_price' => 95000,
                'stock_quantity' => 60,
                'minimum_stock' => 15,
                'specifications' => [
                    ['name' => 'Tipe', 'value' => 'Iridium'],
                    ['name' => 'Gap', 'value' => '0.8mm'],
                    ['name' => 'Thread', 'value' => '14mm'],
                    ['name' => 'Durability', 'value' => '100,000 km'],
                ],
                'compatibility' => [
                    ['brand' => 'Toyota', 'model' => 'Vios, Yaris, Avanza', 'year' => '2015-2023'],
                    ['brand' => 'Honda', 'model' => 'Jazz, City, Brio', 'year' => '2014-2023'],
                ],
                'condition' => 'original',
                'status' => 'active',
                'is_featured' => false,
                'is_best_seller' => true,
                'is_original' => true,
                'order' => 3,
                'warranty_period' => '2 tahun',
                'meta_title' => 'NGK Spark Plug Iridium - Busi Premium Tahan Lama',
                'meta_description' => 'Busi NGK Iridium dengan teknologi elektroda iridium. Pembakaran sempurna dan tahan hingga 100.000 km.',
                'meta_keywords' => 'ngk busi, busi iridium, spark plug, busi premium',
            ],
        ];

        foreach ($spareParts as $sparePart) {
            SparePart::create($sparePart);
        }
    }
}
