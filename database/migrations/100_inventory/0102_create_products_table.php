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
        // create_products_table.php
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();

            $table->boolean('is_bundle')->default(false);
            $table->boolean('is_recipe')->default(false);

            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies');

            $table->decimal('stock_qty', 12, 2)->default(0);
            $table->decimal('min_qty', 12, 2)->default(0);
            $table->string('unit', 20)->default('pcs');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
