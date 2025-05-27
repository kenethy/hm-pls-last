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
        Schema::table('spare_part_categories', function (Blueprint $table) {
            // Change icon column from VARCHAR to TEXT to accommodate longer SVG content
            $table->text('icon')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spare_part_categories', function (Blueprint $table) {
            // Revert back to VARCHAR(255) if needed
            $table->string('icon', 255)->nullable()->change();
        });
    }
};
