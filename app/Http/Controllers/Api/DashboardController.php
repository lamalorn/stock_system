<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /dashboard/income?range=week|month|year
    public function income(Request $request)
    {
        $range = $request->query('range', 'week');

        if ($range === 'week') {
            $rows = DB::select("
                SELECT DATE(created_at) AS label, SUM(total) AS value
                FROM sales
                WHERE status = 'PAID'
                  AND created_at >= NOW() - INTERVAL '6 days'
                GROUP BY DATE(created_at)
                ORDER BY label
            ");
        } elseif ($range === 'month') {
            $rows = DB::select("
                SELECT DATE(created_at) AS label, SUM(total) AS value
                FROM sales
                WHERE status = 'PAID'
                  AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())
                GROUP BY DATE(created_at)
                ORDER BY label
            ");
        } else {
            $rows = DB::select("
                SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') AS label, SUM(total) AS value
                FROM sales
                WHERE status = 'PAID'
                  AND DATE_TRUNC('year', created_at) = DATE_TRUNC('year', NOW())
                GROUP BY DATE_TRUNC('month', created_at)
                ORDER BY DATE_TRUNC('month', created_at)
            ");
        }

        return response()->json($rows);
    }

    // GET /dashboard/products-pie
    public function productsPie()
    {
        $rows = DB::select("
            SELECT p.name AS label, SUM(si.qty) AS value
            FROM sale_items si
            JOIN sales s ON s.id = si.sale_id
            JOIN products p ON p.id = si.product_id
            WHERE s.status = 'PAID'
              AND s.created_at >= NOW() - INTERVAL '30 days'
              AND si.is_bundle_parent = FALSE
            GROUP BY p.name
            ORDER BY value DESC
            LIMIT 10
        ");

        return response()->json($rows);
    }

    // GET /dashboard/summary
    public function summary()
    {
        $today = DB::selectOne("
            SELECT COALESCE(SUM(total),0) AS income_today
            FROM sales
            WHERE status='PAID' AND DATE(created_at)=DATE(NOW())
        ");

        $totalOrders = DB::selectOne("
            SELECT COUNT(*) AS total_orders_24h
            FROM sales
            WHERE created_at >= NOW() - INTERVAL '24 hours'
        ");

        $customersWeek = DB::selectOne("
            SELECT COUNT(*) AS customers_week
            FROM customers
            WHERE created_at >= NOW() - INTERVAL '7 days'
        ");

        $lowStock = DB::table('products')
            ->where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'min_qty')
            ->count();

        return response()->json([
            'income_today' => (float)$today->income_today,
            'total_orders_24h' => (int)$totalOrders->total_orders_24h,
            'customers_week' => (int)$customersWeek->customers_week,
            'low_stock_count' => (int)$lowStock,
        ]);
    }

    // GET /dashboard/recent-sales?limit=10
    public function recentSales(Request $request)
    {
        $limit = (int)$request->query('limit', 10);

        $rows = DB::select("
            SELECT
              sale_no AS id,
              CASE
                WHEN customer_id IS NULL THEN 'Walk-in'
                ELSE CONCAT('Customer #', customer_id)
              END AS customer,
              total AS total,
              status AS status
            FROM sales
            ORDER BY created_at DESC
            LIMIT ?
        ", [$limit]);

        return response()->json($rows);
    }
}
