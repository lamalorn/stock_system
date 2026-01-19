<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleReturnRequest;
use App\Http\Requests\SaleStoreRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(private SaleService $sale, private StockService $stock) {}

    public function index()
    {
        return DB::table('sales')->orderByDesc('id')->paginate(15);
    }

    public function store(SaleStoreRequest $request)
    {
        $data = $request->validated();

        $saleId = $this->sale->createSale($data, $this->stock, $request->user()?->id);

        return response()->json(['message' => 'Created', 'sale_id' => $saleId], 201);
    }

    public function show(int $saleId)
    {
        $sale = DB::table('sales')->where('id', $saleId)->first();
        $items = DB::table('sale_items')->where('sale_id', $saleId)->get();

        return response()->json(['sale' => $sale, 'items' => $items]);
    }

     /**
     * POST /api/sales/{sale}/return
     */
    public function storeReturn(SaleReturnRequest $request, Sale $sale)
    {
        $data = $request->validated();
        $userId = $request->user()->id; 

        return DB::transaction(function () use ($sale, $data, $userId) {

            // 1) Load sale items for this sale
            $saleItems = SaleItem::where('sale_id', $sale->id)->get()->keyBy('id');

            if ($saleItems->isEmpty()) {
                return response()->json([
                    'message' => 'This sale has no items to return.',
                ], 422);
            }

            // 2) Already returned qty per sale_item_id
            $returnedMap = SaleReturnItem::whereIn('sale_item_id', $saleItems->keys())
                ->selectRaw('sale_item_id, COALESCE(SUM(qty),0) as returned_qty')
                ->groupBy('sale_item_id')
                ->pluck('returned_qty', 'sale_item_id');

            // 3) Validate qty not exceed remaining
            foreach ($data['items'] as $row) {
                $saleItemId = (int) $row['sale_item_id'];
                $qty = (int) $row['qty'];

                $si = $saleItems->get($saleItemId);

                if (!$si) {
                    return response()->json([
                        'message' => 'Invalid sale item.',
                        'errors' => [
                            'items' => ["Sale item {$saleItemId} does not belong to this sale."],
                        ],
                    ], 422);
                }

                $alreadyReturned = (int) ($returnedMap[$saleItemId] ?? 0);
                $remaining = (int) $si->qty - $alreadyReturned;

                if ($remaining <= 0) {
                    return response()->json([
                        'message' => 'This item has already been fully returned.',
                        'errors' => [
                            'items' => ["Sale item {$saleItemId} remaining=0"],
                        ],
                    ], 422);
                }

                if ($qty > $remaining) {
                    return response()->json([
                        'message' => 'Return qty exceeds remaining sold qty.',
                        'errors' => [
                            'items' => ["Sale item {$saleItemId} remaining={$remaining}, requested={$qty}"],
                        ],
                    ], 422);
                }
            }

            // 4) Create return header
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'reason' => $data['reason'] ?? null,
                'total_refund' => 0,
                'created_by' => $userId,
            ]);

            // 5) Create return items + increase stock
            $totalRefund = 0;

            foreach ($data['items'] as $row) {
                $si = $saleItems[(int) $row['sale_item_id']];
                $qty = (int) $row['qty'];

                $price = (float) $si->price; // refund based on sold price
                $lineTotal = $qty * $price;
                $totalRefund += $lineTotal;

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $si->id,
                    'product_id' => $si->product_id,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $lineTotal,
                ]);

                // stock back
                Product::whereKey($si->product_id)->increment('stock_qty', $qty);
            }

            // 6) Update header total_refund
            $saleReturn->update(['total_refund' => $totalRefund]);

            // 7) Return response with items
            return response()->json([
                'message' => 'Sale returned successfully',
                'return' => $saleReturn->load('items'),
            ], 201);
        });
    }
}