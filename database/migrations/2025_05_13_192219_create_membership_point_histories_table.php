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
        Schema::create('membership_point_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points')->comment('Number of points added or deducted');
            $table->string('type')->comment('Type of transaction (earn, redeem, adjustment, etc.)');
            $table->text('description')->nullable()->comment('Description of the transaction');
            $table->string('reference')->nullable()->comment('Reference number or identifier');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_point_histories');
    }
};
