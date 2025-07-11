<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'nama_pemilik',
        'no_rekening',
        'bank_id',
        'status',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
