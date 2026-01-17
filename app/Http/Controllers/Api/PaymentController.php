<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(PaymentStoreRequest $request)
    {
        $data = $request->validated();

        $method = DB::table('payment_methods')->where('code', $data['method_code'])->first();
        if (!$method) return response()->json(['message' => 'Invalid payment method'], 422);

        $id = DB::table('payments')->insertGetId([
            'sale_id' => $data['sale_id'],
            'method_id' => $method->id,
            'currency_id' => $data['currency_id'],
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'PAID',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Payment created', 'payment_id' => $id], 201);
    }
}
