<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'sale_id','method_id','currency_id',
        'amount','status','provider_ref','qr_payload'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'method_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
