<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function createPurchase(array $payload, ?int $userId): int
    {
        return DB::transaction(function () use ($payload, $userId) {
            $purchaseId = DB::table('purchases')->insertGetId([
                'supplier_id' => $payload['supplier_id'] ?? null,
                'purchase_no' => $payload['purchase_no'],
                'status' => $payload['status'] ?? 'DRAFT',
                'currency_id' => $payload['currency_id'],
                'total' => 0,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $total = 0;
            foreach ($payload['items'] as $it) {
                $lineTotal = (float)$it['qty'] * (float)$it['cost'];
                $total += $lineTotal;

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id' => $it['product_id'],
                    'qty' => $it['qty'],
                    'cost' => $it['cost'],
                    'line_total' => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('purchases')->where('id', $purchaseId)->update([
                'total' => $total,
                'updated_at' => now(),
            ]);

            return $purchaseId;
        });
    }

    public function receivePurchase(int $purchaseId, StockService $stock, ?int $userId): void
    {
        DB::transaction(function () use ($purchaseId, $stock, $userId) {
            $purchase = DB::table('purchases')->where('id', $purchaseId)->lockForUpdate()->first();
            if (!$purchase) throw ValidationException::withMessages(['purchase' => 'Purchase not found']);

            if ($purchase->status === 'RECEIVED') {
                throw ValidationException::withMessages(['status' => 'Already received']);
            }

            $items = DB::table('purchase_items')->where('purchase_id', $purchaseId)->get();

            foreach ($items as $it) {
                $stock->increase(
                    productId: (int)$it->product_id,
                    qty: (float)$it->qty,
                    userId: $userId,
                    refType: 'purchase',
                    refId: $purchaseId,
                    note: 'Purchase received'
                );
            }

            DB::table('purchases')->where('id', $purchaseId)->update([
                'status' => 'RECEIVED',
                'received_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
