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
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the existing unique constraint on license_plate
            $table->dropUnique(['license_plate']);

            // Add a composite unique constraint on customer_id and license_plate
            $table->unique(['customer_id', 'license_plate'], 'vehicles_customer_license_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('vehicles_customer_license_unique');

            // Add back the original unique constraint on license_plate
            $table->unique(['license_plate']);
        });
    }
};
