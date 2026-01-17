<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function createSale(array $payload, StockService $stock, ?int $userId): int
    {
        return DB::transaction(function () use ($payload, $stock, $userId) {

            $saleId = DB::table('sales')->insertGetId([
                'sale_no' => $payload['sale_no'],
                'customer_id' => $payload['customer_id'] ?? null,
                'status' => $payload['status'] ?? 'PAID',
                'sale_type' => $payload['sale_type'] ?? 'RETAIL',
                'currency_id' => $payload['currency_id'],
                'subtotal' => 0,
                'discount' => $payload['discount'] ?? 0,
                'tax' => $payload['tax'] ?? 0,
                'total' => 0,
                'paid_amount' => $payload['paid_amount'] ?? 0,
                'change_amount' => $payload['change_amount'] ?? 0,
                'sold_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $subtotal = 0;

            foreach ($payload['items'] as $it) {
                $product = DB::table('products')->where('id', $it['product_id'])->first();
                if (!$product) throw ValidationException::withMessages(['product' => 'Product not found']);

                $qty = (float)$it['qty'];
                $price = isset($it['price']) ? (float)$it['price'] : (float)$product->price;
                $lineDiscount = isset($it['discount']) ? (float)$it['discount'] : 0;
                $lineTotal = ($qty * $price) - $lineDiscount;
                $subtotal += $lineTotal;

                // Insert main sale item
                $saleItemId = DB::table('sale_items')->insertGetId([
                    'sale_id' => $saleId,
                    'product_id' => $it['product_id'],
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $lineDiscount,
                    'line_total' => $lineTotal,
                    'is_bundle_parent' => (bool)$product->is_bundle,
                    'parent_bundle_item_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // If bundle: expand children from product_bundles
                if ($product->is_bundle) {
                    $bundleItems = DB::table('product_bundles')
                        ->where('bundle_product_id', $product->id)
                        ->get();

                    foreach ($bundleItems as $b) {
                        $childQty = (float)$b->qty * $qty;

                        DB::table('sale_items')->insert([
                            'sale_id' => $saleId,
                            'product_id' => $b->item_product_id,
                            'qty' => $childQty,
                            'price' => 0,
                            'discount' => 0,
                            'line_total' => 0,
                            'is_bundle_parent' => false,
                            'parent_bundle_item_id' => $saleItemId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $stock->decrease(
                            productId: (int)$b->item_product_id,
                            qty: $childQty,
                            userId: $userId,
                            refType: 'sale',
                            refId: $saleId,
                            note: 'Bundle sale'
                        );
                    }
                }
                // If recipe: consume ingredients from product_recipes
                else if ($product->is_recipe) {
                    $recipes = DB::table('product_recipes')->where('product_id', $product->id)->get();
                    foreach ($recipes as $r) {
                        $consume = (float)$r->qty * $qty;
                        $stock->decrease(
                            productId: (int)$r->ingredient_product_id,
                            qty: $consume,
                            userId: $userId,
                            refType: 'recipe',
                            refId: $saleId,
                            note: 'Recipe sale'
                        );
                    }
                }
                // Normal product: decrease itself
                else {
                    $stock->decrease(
                        productId: (int)$product->id,
                        qty: $qty,
                        userId: $userId,
                        refType: 'sale',
                        refId: $saleId,
                        note: 'Retail sale'
                    );
                }
            }

            $total = $subtotal - (float)($payload['discount'] ?? 0) + (float)($payload['tax'] ?? 0);

            DB::table('sales')->where('id', $saleId)->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'updated_at' => now(),
            ]);

            return $saleId;
        });
    }
}
