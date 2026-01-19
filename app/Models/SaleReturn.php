<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id',
        'reason',
        'total_refund',
        'created_by',
    ];

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
