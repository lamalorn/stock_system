<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleStoreRequest;
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
}
