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
        Schema::table('follow_up_templates', function (Blueprint $table) {
            // WhatsApp specific fields
            $table->boolean('whatsapp_enabled')->default(true)->after('is_active');
            $table->boolean('include_attachments')->default(false)->after('whatsapp_enabled');
            $table->enum('whatsapp_message_type', ['text', 'image', 'file', 'contact', 'link'])->default('text')->after('include_attachments');
            $table->string('attachment_path')->nullable()->after('whatsapp_message_type');
            $table->text('whatsapp_caption')->nullable()->after('attachment_path');
            
            // Enhanced template variables
            $table->json('available_variables')->nullable()->after('whatsapp_caption');
            $table->boolean('auto_send_on_completion')->default(false)->after('available_variables');
            $table->integer('delay_minutes')->default(0)->after('auto_send_on_completion');
            
            // Tracking fields
            $table->integer('whatsapp_sent_count')->default(0)->after('delay_minutes');
            $table->timestamp('last_whatsapp_sent_at')->nullable()->after('whatsapp_sent_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_up_templates', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_enabled',
                'include_attachments', 
                'whatsapp_message_type',
                'attachment_path',
                'whatsapp_caption',
                'available_variables',
                'auto_send_on_completion',
                'delay_minutes',
                'whatsapp_sent_count',
                'last_whatsapp_sent_at'
            ]);
        });
    }
};
