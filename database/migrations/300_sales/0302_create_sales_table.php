<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->string('sale_no', 40)->unique();

            $table->foreignId('customer_id')->nullable()
                ->constrained('customers')->nullOnDelete();

            // enum: PAID, DRAFT, UNPAID, REFUND
            $table->enum('status', ['PAID', 'DRAFT', 'UNPAID', 'REFUND'])
                ->default('PAID');

            // enum: RETAIL, PACKAGE
            $table->enum('sale_type', ['RETAIL', 'PACKAGE'])
                ->default('RETAIL');

            $table->foreignId('currency_id')->constrained('currencies');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);

            $table->foreignId('sold_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('sale_type');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
