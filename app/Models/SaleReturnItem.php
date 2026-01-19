<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'qty',
        'price',
        'line_total',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }
}
