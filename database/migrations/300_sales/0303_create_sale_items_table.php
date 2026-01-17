<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->decimal('qty', 12, 2);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            // Bundle support
            $table->boolean('is_bundle_parent')->default(false);
            $table->foreignId('parent_bundle_item_id')->nullable()->constrained('sale_items')->cascadeOnDelete();

            $table->timestamps();

            $table->index(['sale_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
