<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code','name','symbol','is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
