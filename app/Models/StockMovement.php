<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id','movement_type','qty',
        'reference_type','reference_id',
        'note','created_by','created_at'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
