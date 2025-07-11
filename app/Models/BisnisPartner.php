<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BisnisPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bp', 'jenis_bp', 'alamat', 'email', 'no_telepon',
        'bank_id', 'nama_rekening', 'no_rekening_va', 'terms_of_payment', 'status'
    ];

    // Relasi dengan Bank
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    // Accessor untuk nama_bank (dari relasi)
    public function getNamaBankAttribute()
    {
        return $this->bank ? $this->bank->nama_bank : null;
    }
}
