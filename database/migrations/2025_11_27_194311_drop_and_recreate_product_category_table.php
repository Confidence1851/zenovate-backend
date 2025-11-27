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
        // Drop the existing product_category table
        Schema::dropIfExists('product_category');

        // Recreate as product_categories table with category info stored directly (no product_id)
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');

        // Recreate the old structure if needed for rollback
        Schema::create('product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('category_name');
            $table->string('category_slug');
            $table->text('category_description')->nullable();
            $table->string('category_image_path')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('category_slug');
            $table->index(['product_id', 'category_slug']);
        });
    }
};
