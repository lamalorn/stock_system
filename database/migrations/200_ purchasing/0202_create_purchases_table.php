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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->string('purchase_no', 50)->unique();
            $table->string('status', 30);
            $table->foreignId('currency_id')->constrained();
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
