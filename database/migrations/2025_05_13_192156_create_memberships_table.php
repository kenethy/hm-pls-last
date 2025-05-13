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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('membership_number')->unique()->comment('Unique membership number');
            $table->string('card_type')->default('regular')->comment('Type of membership card (regular, silver, gold, etc.)');
            $table->integer('points')->default(0)->comment('Current points balance');
            $table->integer('lifetime_points')->default(0)->comment('Total points earned over lifetime');
            $table->date('join_date')->comment('Date when customer joined the membership program');
            $table->date('expiry_date')->nullable()->comment('Membership expiration date if applicable');
            $table->boolean('is_active')->default(true)->comment('Whether the membership is currently active');
            $table->text('notes')->nullable()->comment('Additional notes about the membership');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
