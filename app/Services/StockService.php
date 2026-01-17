<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function increase(int $productId, float $qty, ?int $userId, string $refType = 'manual', ?int $refId = null, ?string $note = null): void
    {
        if ($qty <= 0) throw ValidationException::withMessages(['qty' => 'Quantity must be > 0']);

        DB::transaction(function () use ($productId, $qty, $userId, $refType, $refId, $note) {

            // Lock product row
            $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();
            if (!$product) throw ValidationException::withMessages(['product_id' => 'Product not found']);

            DB::table('products')->where('id', $productId)->update([
                'stock_qty' => DB::raw("stock_qty + {$qty}"),
                'updated_at' => now(),
                'updated_by' => $userId,
            ]);

            DB::table('stock_movements')->insert([
                'product_id' => $productId,
                'movement_type' => 'IN',
                'qty' => $qty,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'note' => $note,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        });
    }

    public function decrease(int $productId, float $qty, ?int $userId, string $refType = 'manual', ?int $refId = null, ?string $note = null): void
    {
        if ($qty <= 0) throw ValidationException::withMessages(['qty' => 'Quantity must be > 0']);

        DB::transaction(function () use ($productId, $qty, $userId, $refType, $refId, $note) {

            $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();
            if (!$product) throw ValidationException::withMessages(['product_id' => 'Product not found']);

            if ((float)$product->stock_qty < $qty) {
                throw ValidationException::withMessages(['stock' => 'Not enough stock']);
            }

            DB::table('products')->where('id', $productId)->update([
                'stock_qty' => DB::raw("stock_qty - {$qty}"),
                'updated_at' => now(),
                'updated_by' => $userId,
            ]);

            DB::table('stock_movements')->insert([
                'product_id' => $productId,
                'movement_type' => 'OUT',
                'qty' => $qty,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'note' => $note,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        });
    }
}
