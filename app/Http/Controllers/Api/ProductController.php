<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
     // GET /products/summary
    public function summary()
    {
        $total = Product::query()->count();
        $active = Product::query()->where('is_active', true)->count();
        $disabled = Product::query()->where('is_active', false)->count();

        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'min_qty')
            ->count();

        return response()->json([
            'total_products' => (int)$total,
            'active' => (int)$active,
            'low_stock' => (int)$lowStock,
            'disabled' => (int)$disabled,
        ]);
    }

    // GET /products?search=&category_id=&status=&stock=&sort=&page=&per_page=
    public function index(Request $request)
    {
        $search = trim((string)$request->query('search', ''));
        $categoryId = $request->query('category_id', '');
        $status = trim((string)$request->query('status', '')); // active|disabled
        $stock = trim((string)$request->query('stock', ''));   // low|ok
        $sort = trim((string)$request->query('sort', 'newest')); // newest|oldest|a-z|z-a|stock-low|stock-high

        $perPage = (int)$request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $q = Product::query()
            ->leftJoin('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('currencies as cur', 'cur.id', '=', 'products.currency_id')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.stock_qty',
                'products.min_qty',
                'products.unit',
                'products.price',
                'products.cost',
                'products.is_active',
                'products.is_bundle',
                'products.is_recipe',
                'products.created_at',
                'products.updated_at',
                'products.category_id',
                DB::raw("COALESCE(c.name, '-') as category_name"),
                DB::raw("COALESCE(cur.symbol, '') as currency_symbol"),
                DB::raw("COALESCE(cur.code, '') as currency_code"),
            ]);

        if ($search !== '') {
            $s = mb_strtolower($search);
            $q->where(function ($qq) use ($s) {
                $qq->whereRaw('LOWER(products.name) LIKE ?', ["%{$s}%"])
                   ->orWhereRaw('LOWER(COALESCE(products.sku, \'\')) LIKE ?', ["%{$s}%"]);
            });
        }

        if ($categoryId !== '' && strtolower((string)$categoryId) !== 'all') {
            $q->where('products.category_id', (int)$categoryId);
        }

        if ($status !== '') {
            if (strtolower($status) === 'active') $q->where('products.is_active', true);
            if (strtolower($status) === 'disabled') $q->where('products.is_active', false);
        }

        if ($stock !== '') {
            if (strtolower($stock) === 'low') {
                $q->whereColumn('products.stock_qty', '<=', 'products.min_qty');
            }
            if (strtolower($stock) === 'ok') {
                $q->whereColumn('products.stock_qty', '>', 'products.min_qty');
            }
        }

        $sortKey = strtolower($sort);
        if ($sortKey === 'oldest') $q->orderBy('products.created_at', 'asc');
        elseif ($sortKey === 'a-z') $q->orderBy('products.name', 'asc');
        elseif ($sortKey === 'z-a') $q->orderBy('products.name', 'desc');
        elseif ($sortKey === 'stock-low') $q->orderBy('products.stock_qty', 'asc');
        elseif ($sortKey === 'stock-high') $q->orderBy('products.stock_qty', 'desc');
        else $q->orderBy('products.created_at', 'desc'); // newest

        return $q->paginate($perPage);
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
