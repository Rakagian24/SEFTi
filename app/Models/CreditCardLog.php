<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCardLog extends Model
{
    protected $fillable = [
        'credit_card_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}