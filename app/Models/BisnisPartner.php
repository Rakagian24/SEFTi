<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class BisnisPartner extends Model
{
    use SoftDeletes;

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

    /**
     * Scope untuk search yang dioptimasi
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama_bp', 'like', "%$search%")
              ->orWhere('jenis_bp', 'like', "%$search%")
              ->orWhere('alamat', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhere('no_telepon', 'like', "%$search%")
              ->orWhere('nama_rekening', 'like', "%$search%")
              ->orWhere('no_rekening_va', 'like', "%$search%")
              ->orWhere('terms_of_payment', 'like', "%$search%")
              ->orWhereHas('bank', function($b) use ($search) {
                  $b->where('nama_bank', 'like', "%$search%")
                    ->orWhere('singkatan', 'like', "%$search%");
              });
        });
    }

    /**
     * Scope untuk filter berdasarkan jenis_bp
     */
    public function scopeByJenisBp($query, $jenisBp)
    {
        return $query->where('jenis_bp', $jenisBp);
    }

    /**
     * Scope untuk filter berdasarkan terms_of_payment
     */
    public function scopeByTermsOfPayment($query, $termsOfPayment)
    {
        return $query->where('terms_of_payment', $termsOfPayment);
    }

    /**
     * Scope untuk filter berdasarkan bank
     */
    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }
}
