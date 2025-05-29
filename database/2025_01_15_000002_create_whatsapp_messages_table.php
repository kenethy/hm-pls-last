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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->nullable();
            $table->string('phone_number', 20);
            $table->enum('message_type', ['text', 'image', 'file', 'contact', 'link', 'location'])->default('text');
            $table->text('content');
            $table->text('caption')->nullable();
            $table->string('media_path')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('follow_up_template_id')->nullable()->constrained('follow_up_templates')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('api_response')->nullable();
            $table->boolean('is_automated')->default(false);
            $table->string('triggered_by')->nullable(); // 'service_completion', 'manual', 'scheduled'
            $table->timestamps();
            
            // Indexes for performance
            $table->index('phone_number');
            $table->index('status');
            $table->index('service_id');
            $table->index('customer_id');
            $table->index('is_automated');
            $table->index('triggered_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
