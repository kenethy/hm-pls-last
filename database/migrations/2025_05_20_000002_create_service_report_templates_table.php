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
        Schema::create('service_report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vehicle_type')->nullable();
            $table->text('description')->nullable();
            $table->json('checklist_items');
            $table->json('services_performed')->nullable();
            $table->text('warranty_info')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_report_templates');
    }
};
