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
        Schema::create('mechanic_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('mechanic_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name'); // Store customer name for reference
            $table->string('customer_phone'); // Store customer phone for reference
            $table->tinyInteger('rating')->unsigned()->comment('Rating from 1 to 5 stars');
            $table->text('comment')->nullable();
            $table->string('service_type'); // Store service type for context
            $table->string('vehicle_info')->nullable(); // Store vehicle info for context
            $table->timestamp('service_date'); // Store service date for context
            $table->timestamps();

            // Ensure one rating per customer per mechanic per service
            $table->unique(['service_id', 'mechanic_id', 'customer_phone'], 'unique_service_mechanic_customer_rating');

            // Add indexes for performance
            $table->index(['mechanic_id', 'created_at']);
            $table->index(['service_id']);
            $table->index(['rating']);
            $table->index(['customer_phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mechanic_ratings');
    }
};
