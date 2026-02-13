<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    /**
     * GET /pos/products?query=xxx
     * Search product by name or SKU (barcode)
     */
    public function searchProducts(Request $request)
    {
        $query = trim((string) $request->query('query', ''));
        $limit = min(20, max(1, (int) $request->query('limit', 10)));

        if ($query === '') {
            return response()->json([]);
        }

        $q = mb_strtolower($query);

        $products = DB::table('products as p')
            ->leftJoin('currencies as cur', 'cur.id', '=', 'p.currency_id')
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                'p.price',
                'p.stock_qty',
                'p.unit',
                'p.is_active',
                DB::raw("COALESCE(cur.symbol,'') as currency_symbol"),
            ])
            ->where('p.is_active', true)
            ->where(function ($qq) use ($q) {
                $qq->whereRaw('LOWER(p.name) LIKE ?', ["%{$q}%"])
                   ->orWhereRaw('LOWER(COALESCE(p.sku, \'\')) LIKE ?', ["%{$q}%"]);
            })
            ->orderBy('p.name')
            ->limit($limit)
            ->get();

        return response()->json($products);
    }
}
