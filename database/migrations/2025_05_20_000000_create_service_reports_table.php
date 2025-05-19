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
        Schema::create('service_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('title')->default('Laporan Digital Paket Napas Baru Premium');
            $table->string('unique_code')->unique();
            $table->string('customer_name');
            $table->string('license_plate');
            $table->string('car_model');
            $table->string('technician_name')->nullable();
            $table->text('summary')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('warranty_info')->nullable();
            $table->json('services_performed')->nullable();
            $table->json('additional_services')->nullable();
            $table->timestamp('service_date');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_reports');
    }
};
