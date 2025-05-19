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
        Schema::table('service_reports', function (Blueprint $table) {
            // Add all the missing columns
            if (!Schema::hasColumn('service_reports', 'service_id')) {
                $table->foreignId('service_id')->nullable()->constrained()->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('service_reports', 'title')) {
                $table->string('title')->default('Laporan Digital Paket Napas Baru Premium');
            }
            
            if (!Schema::hasColumn('service_reports', 'code')) {
                $table->string('code')->unique();
            }
            
            if (!Schema::hasColumn('service_reports', 'customer_name')) {
                $table->string('customer_name');
            }
            
            if (!Schema::hasColumn('service_reports', 'license_plate')) {
                $table->string('license_plate');
            }
            
            if (!Schema::hasColumn('service_reports', 'car_model')) {
                $table->string('car_model');
            }
            
            if (!Schema::hasColumn('service_reports', 'technician_name')) {
                $table->string('technician_name')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'summary')) {
                $table->text('summary')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'recommendations')) {
                $table->text('recommendations')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'warranty_info')) {
                $table->text('warranty_info')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'services_performed')) {
                $table->json('services_performed')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'additional_services')) {
                $table->json('additional_services')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'service_date')) {
                $table->timestamp('service_date')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            
            if (!Schema::hasColumn('service_reports', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            // Drop all the added columns
            $columns = [
                'service_id', 'title', 'code', 'customer_name', 'license_plate',
                'car_model', 'technician_name', 'summary', 'recommendations',
                'warranty_info', 'services_performed', 'additional_services',
                'service_date', 'expires_at', 'is_active'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('service_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
