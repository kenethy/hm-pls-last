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
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->foreignId('category_id')->constrained('spare_part_categories')->onDelete('cascade');
            $table->string('brand')->nullable();
            $table->string('part_number')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('original_price', 12, 2)->nullable(); // For discount display
            $table->integer('stock_quantity')->default(0);
            $table->integer('minimum_stock')->default(5);
            $table->json('images')->nullable(); // Array of image paths
            $table->string('featured_image')->nullable(); // Main product image
            $table->json('specifications')->nullable(); // Technical specs
            $table->json('compatibility')->nullable(); // Compatible vehicle models
            $table->enum('condition', ['new', 'original', 'aftermarket'])->default('new');
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_original')->default(false);
            $table->integer('order')->default(0);
            $table->string('warranty_period')->nullable(); // e.g., "1 tahun", "6 bulan"
            $table->text('installation_notes')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['is_best_seller', 'status']);
            $table->index('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
