<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id','name','sku','barcode',
        'is_bundle','is_recipe',
        'cost','price','currency_id',
        'stock_qty','min_qty','unit','is_active',
        'created_by','updated_by',
    ];

    protected $casts = [
        'is_bundle' => 'boolean',
        'is_recipe' => 'boolean',
        'is_active' => 'boolean',
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
        'stock_qty' => 'decimal:2',
        'min_qty' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    // bundle contents (what inside this bundle)
    public function bundleItems()
    {
        return $this->hasMany(ProductBundle::class, 'bundle_product_id');
    }

    // recipe ingredients
    public function recipeItems()
    {
        return $this->hasMany(ProductRecipe::class, 'product_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
