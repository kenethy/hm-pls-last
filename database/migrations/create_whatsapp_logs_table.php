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
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // 'qr_generated', 'message_sent', 'status_check'
            $table->string('phone')->nullable()->index();
            $table->text('message')->nullable();
            $table->json('response_data')->nullable();
            $table->string('status')->index(); // 'success', 'failed'
            $table->text('error_message')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->string('qr_uuid')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
