<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id','product_id','qty','price','discount','line_total',
        'is_bundle_parent','parent_bundle_item_id'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'is_bundle_parent' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function parentBundle()
    {
        return $this->belongsTo(SaleItem::class, 'parent_bundle_item_id');
    }

    public function children()
    {
        return $this->hasMany(SaleItem::class, 'parent_bundle_item_id');
    }
}
