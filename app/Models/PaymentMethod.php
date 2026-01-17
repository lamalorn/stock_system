<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['code','name'];

    public function payments()
    {
        return $this->hasMany(Payment::class, 'method_id');
    }
}
