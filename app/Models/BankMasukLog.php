<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankMasukLog extends Model
{
    protected $fillable = [
        'bank_masuk_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function bankMasuk()
    {
        return $this->belongsTo(BankMasuk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
