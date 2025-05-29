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
        Schema::create('follow_up_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('message');
            $table->text('description')->nullable();
            $table->enum('trigger_event', ['service_completion', 'booking_confirmation', 'payment_reminder', 'custom'])->default('service_completion');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            // Index for performance
            $table->index('is_active');
            $table->index('trigger_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_templates');
    }
};
