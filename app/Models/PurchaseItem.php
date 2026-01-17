<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id','product_id','qty','cost','line_total'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
