<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spare_part_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'textarea', 'boolean', 'number'])->default('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default pricing notification settings
        DB::table('spare_part_settings')->insert([
            [
                'key' => 'pricing_notification_enabled',
                'value' => '1',
                'description' => 'Enable/disable pricing notification',
                'type' => 'boolean',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pricing_notification_title',
                'value' => 'Harga Toko Lebih Murah!',
                'description' => 'Title for pricing notification',
                'type' => 'text',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pricing_notification_message',
                'value' => 'Dapatkan harga sparepart yang lebih murah dengan berbelanja langsung di toko kami! Harga online di marketplace lebih tinggi karena biaya platform. Kunjungi toko atau hubungi WhatsApp untuk harga terbaik.',
                'description' => 'Main message for pricing notification',
                'type' => 'textarea',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pricing_notification_cta_text',
                'value' => 'Hubungi WhatsApp untuk Harga Terbaik',
                'description' => 'Call-to-action button text',
                'type' => 'text',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pricing_notification_whatsapp_number',
                'value' => '6282135202581',
                'description' => 'WhatsApp number for pricing inquiries',
                'type' => 'text',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pricing_notification_display_type',
                'value' => 'banner',
                'description' => 'Display type: modal, banner, or sticky',
                'type' => 'text',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_settings');
    }
};
