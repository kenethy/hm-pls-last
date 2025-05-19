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
        Schema::create('service_report_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_report_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->string('inspection_point');
            $table->enum('status', ['ok', 'warning', 'needs_repair'])->default('ok');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_report_checklist_items');
    }
};
