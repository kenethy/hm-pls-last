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
        Schema::create('whatsapp_config', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default WhatsApp Config');
            $table->string('api_url')->default('http://localhost:3000');
            $table->string('api_username')->nullable();
            $table->string('api_password')->nullable();
            $table->string('webhook_secret')->default('secret');
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_reply_enabled')->default(false);
            $table->text('auto_reply_message')->nullable();
            $table->json('connection_status')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index for performance
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_config');
    }
};
