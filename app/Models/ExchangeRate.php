<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency_id','quote_currency_id','rate','rate_date'
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'rate_date' => 'date',
    ];

    public function baseCurrency()
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function quoteCurrency()
    {
        return $this->belongsTo(Currency::class, 'quote_currency_id');
    }
}
