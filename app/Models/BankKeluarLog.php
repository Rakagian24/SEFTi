<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankKeluarLog extends Model
{
    protected $fillable = [
        'bank_keluar_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function bankKeluar()
    {
        return $this->belongsTo(BankKeluar::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
