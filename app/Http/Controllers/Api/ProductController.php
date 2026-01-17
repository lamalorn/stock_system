<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');

        return Product::query()
            ->when($q, fn($qq) => $qq->where('name','ilike',"%$q%")
                ->orWhere('barcode','ilike',"%$q%")
                ->orWhere('sku','ilike',"%$q%")
            )
            ->latest()
            ->paginate(15);
    }

    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product;
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();

        $data['updated_by'] = $request->user()?->id;

        // Don't allow direct stock_qty edit here (stock should change via StockService)
        unset($data['stock_qty']);

        $product->update($data);
        return response()->json(['message' => 'Updated', 'product' => $product]);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
