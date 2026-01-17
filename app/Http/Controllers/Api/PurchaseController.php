<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseStoreRequest;
use App\Services\PurchaseService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchase, private StockService $stock) {}

    public function index()
    {
        return DB::table('purchases')->orderByDesc('id')->paginate(15);
    }

    public function store(PurchaseStoreRequest $request)
    {
        $data = $request->validated();

        $id = $this->purchase->createPurchase($data, $request->user()?->id);
        return response()->json(['message' => 'Created', 'purchase_id' => $id], 201);
    }

    public function receive(Request $request, int $purchaseId)
    {
        $this->purchase->receivePurchase($purchaseId, $this->stock, $request->user()?->id);
        return response()->json(['message' => 'Received']);
    }
}
