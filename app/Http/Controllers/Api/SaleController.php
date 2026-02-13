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

     // GET /sales/summary
    public function summary()
    {
        $today = DB::selectOne("
            SELECT COALESCE(SUM(total),0) AS total
            FROM sales
            WHERE status='PAID' AND DATE(created_at)=DATE(NOW())
        ");

        $orders24h = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM sales
            WHERE created_at >= NOW() - INTERVAL '24 hours'
        ");

        $dueCount = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM sales
            WHERE status='DUE'
        ");

        $returns = DB::selectOne("
            SELECT COALESCE(SUM(total_refund),0) AS total
            FROM sale_returns
            WHERE DATE(created_at)=DATE(NOW())
        ");

        return response()->json([
            'today_sales' => (float)$today->total,
            'orders_24h' => (int)$orders24h->c,
            'due_count' => (int)$dueCount->c,
            'today_refunds' => (float)$returns->total,
        ]);
    }

    // GET /sales?search=&status=&type=&date_from=&date_to=&sort=&page=&per_page=
    public function index(Request $request)
    {
        $search = trim((string)$request->query('search', ''));      // sale_no
        $status = trim((string)$request->query('status', ''));      // PAID|DUE|CANCELLED
        $type = trim((string)$request->query('type', ''));          // RETAIL|WHOLESALE
        $dateFrom = trim((string)$request->query('date_from', '')); // YYYY-MM-DD
        $dateTo = trim((string)$request->query('date_to', ''));     // YYYY-MM-DD
        $sort = trim((string)$request->query('sort', 'newest'));    // newest|oldest|total-high|total-low

        $perPage = (int)$request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $q = DB::table('sales as s')
            ->leftJoin('users as u', 'u.id', '=', 's.sold_by')
            ->leftJoin('currencies as cur', 'cur.id', '=', 's.currency_id')
            ->select([
                's.id',
                's.sale_no',
                's.customer_id',
                's.status',
                's.sale_type',
                's.total',
                's.paid_amount',
                's.change_amount',
                's.created_at',
                'u.name as sold_by_name',
                DB::raw("COALESCE(cur.symbol,'') as currency_symbol"),
            ]);

        if ($search !== '') {
            $s = mb_strtolower($search);
            $q->whereRaw('LOWER(s.sale_no) LIKE ?', ["%{$s}%"]);
        }

        if ($status !== '' && strtolower($status) !== 'all') {
            $q->where('s.status', strtoupper($status));
        }

        if ($type !== '' && strtolower($type) !== 'all') {
            $q->where('s.sale_type', strtoupper($type));
        }

        if ($dateFrom !== '') {
            $q->whereDate('s.created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $q->whereDate('s.created_at', '<=', $dateTo);
        }

        $sortKey = strtolower($sort);
        if ($sortKey === 'oldest') $q->orderBy('s.created_at', 'asc');
        elseif ($sortKey === 'total-high') $q->orderBy('s.total', 'desc');
        elseif ($sortKey === 'total-low') $q->orderBy('s.total', 'asc');
        else $q->orderBy('s.created_at', 'desc'); // newest

        // Add computed customer label
        // (Laravel query builder can't do CASE alias easily in select list without raw)
        $paginator = $q->paginate($perPage);

        $paginator->getCollection()->transform(function ($row) {
            $row->customer = $row->customer_id ? ('Customer #' . $row->customer_id) : 'Walk-in';
            return $row;
        });

        return $paginator;
    }
    // POST /sales  (POS create)
    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_type' => 'required|string', // RETAIL
            'status' => 'required|string',    // PAID or DUE
            'currency_id' => 'required|integer',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $data) {
            $discount = (float)($data['discount'] ?? 0);

            $subtotal = 0;
            foreach ($data['items'] as $it) {
                $qty = (float)$it['qty'];
                $price = (float)$it['price'];
                $lineDisc = (float)($it['discount'] ?? 0);
                $lineTotal = max(0, ($qty * $price) - $lineDisc);
                $subtotal += $lineTotal;
            }

            $total = max(0, $subtotal - $discount);

            $paidAmount = (float)($data['paid_amount'] ?? 0);
            $changeAmount = max(0, $paidAmount - $total);

            // generate sale_no (simple)
            $nextId = (int) (DB::table('sales')->max('id') ?? 0) + 1;
            $saleNo = 'S-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);

            $saleId = DB::table('sales')->insertGetId([
                'sale_no' => $saleNo,
                'customer_id' => $data['customer_id'] ?? null,
                'status' => strtoupper($data['status']),
                'sale_type' => strtoupper($data['sale_type']),
                'currency_id' => (int)$data['currency_id'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'sold_by' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $it) {
                $qty = (float)$it['qty'];
                $price = (float)$it['price'];
                $lineDisc = (float)($it['discount'] ?? 0);
                $lineTotal = max(0, ($qty * $price) - $lineDisc);

                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => (int)$it['product_id'],
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $lineDisc,
                    'line_total' => $lineTotal,
                    'is_bundle_parent' => false,
                    'parent_bundle_item_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // stock movement (optional)
                DB::table('products')->where('id', (int)$it['product_id'])
                    ->decrement('stock_qty', $qty);

                DB::table('stock_movements')->insert([
                    'product_id' => (int)$it['product_id'],
                    'movement_type' => 'SALE',
                    'qty' => $qty * -1,
                    'reference_type' => 'SALE',
                    'reference_id' => $saleId,
                    'note' => 'POS sale',
                    'created_by' => $request->user()?->id,
                    'created_at' => now(),
                ]);
            }

            return response()->json([
                'message' => 'Created',
                'sale_id' => $saleId,
                'sale_no' => $saleNo,
            ], 201);
        });
    }

    // PUT Status (Edit Status)
    public function updateStatus(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'status' => 'required|in:PAID,DRAFT,UNPAID,REFUND,CANCELLED',
        ]);

        $sale->status = $data['status'];
        $sale->save();

        $sale->refresh(); // reload from DB

        return response()->json([
            'message' => 'Sale status updated successfully.',
            'changed' => $sale->wasChanged('status'),
            'data' => $sale,
        ]);
    }

    public function show($saleId)
    {
        $sale = DB::table('sales as s')
            ->leftJoin('users as u', 'u.id', '=', 's.sold_by')
            ->leftJoin('currencies as cur', 'cur.id', '=', 's.currency_id')
            ->select([
                's.id',
                's.sale_no',
                's.customer_id',
                's.status',
                's.sale_type',
                's.currency_id',
                's.subtotal',
                's.discount',
                's.total',
                's.paid_amount',
                's.change_amount',
                's.created_at',
                'u.name as sold_by_name',
                DB::raw("COALESCE(cur.symbol,'') as currency_symbol"),
                DB::raw("COALESCE(cur.code,'') as currency_code"),
            ])
            ->where('s.id', (int)$saleId)
            ->first();

        if (!$sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        $items = DB::table('sale_items as si')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->select([
                'si.id',
                'si.product_id',
                'p.name as product_name',
                'p.sku as sku',
                'si.qty',
                'si.price',
                'si.discount',
                'si.line_total',
                'si.is_bundle_parent',
                'si.parent_bundle_item_id',
            ])
            ->where('si.sale_id', (int)$saleId)
            ->orderBy('si.id')
            ->get();

        $customerLabel = $sale->customer_id ? ('Customer #' . $sale->customer_id) : 'Walk-in';

        return response()->json([
            'sale' => $sale,
            'customer' => $customerLabel,
            'items' => $items,
        ]);
    }

    // GET /sales/{sale}/print  (same data as show)
    public function print($saleId)
    {
        return $this->show($saleId);
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