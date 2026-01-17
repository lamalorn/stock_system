<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockChangeRequest;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function increase(StockChangeRequest $request)
    {
        $data = $request->validated();

        $this->stock->increase(
            productId: (int)$data['product_id'],
            qty: (float)$data['qty'],
            userId: $request->user()?->id,
            refType: 'manual',
            refId: null,
            note: $data['note'] ?? null
        );

        return response()->json(['message' => 'Stock increased']);
    }

    public function decrease(StockChangeRequest $request)
    {
        $data = $request->validated([
            'product_id' => ['required', 'integer'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);

        $this->stock->decrease(
            productId: (int)$data['product_id'],
            qty: (float)$data['qty'],
            userId: $request->user()?->id,
            refType: 'manual',
            refId: null,
            note: $data['note'] ?? null
        );

        return response()->json(['message' => 'Stock decreased']);
    }

    public function alerts()
    {
        $alerts = DB::table('products')
            ->select('id','name','stock_qty','min_qty','unit')
            ->where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'min_qty')
            ->orderBy('stock_qty', 'asc')
            ->get();

        return response()->json($alerts);
    }
}
