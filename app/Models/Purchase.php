<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id','purchase_no','status',
        'currency_id','total','received_at','created_by'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
