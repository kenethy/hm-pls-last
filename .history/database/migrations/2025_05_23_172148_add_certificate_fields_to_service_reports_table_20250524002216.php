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
            // E-Certificate fields
            $table->string('certificate_number')->nullable()->unique()->after('code');
            $table->timestamp('certificate_issued_date')->nullable()->after('certificate_number');
            $table->timestamp('certificate_valid_until')->nullable()->after('certificate_issued_date');
            $table->string('certificate_verification_code', 8)->nullable()->after('certificate_valid_until');
            $table->string('health_status')->nullable()->after('certificate_verification_code');
            $table->integer('overall_condition_score')->nullable()->after('health_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_number',
                'certificate_issued_date',
                'certificate_valid_until',
                'certificate_verification_code',
                'health_status',
                'overall_condition_score',
            ]);
        });
    }
};
