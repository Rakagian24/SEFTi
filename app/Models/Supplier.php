<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'nama_supplier',
        'alamat',
        'email',
        'no_telepon',
        'bank_1',
        'nama_rekening_1',
        'no_rekening_1',
        'bank_2',
        'nama_rekening_2',
        'no_rekening_2',
        'bank_3',
        'nama_rekening_3',
        'no_rekening_3',
        'terms_of_payment',
    ];

    // Accessor to get the primary bank account (first one)
    public function getPrimaryBankAttribute()
    {
        return [
            'bank' => $this->bank_1,
            'nama_rekening' => $this->nama_rekening_1,
            'no_rekening' => $this->no_rekening_1,
        ];
    }

    // Accessor to get all bank accounts as array
    public function getBankAccountsAttribute()
    {
        $accounts = [];

        if ($this->bank_1 && $this->nama_rekening_1 && $this->no_rekening_1) {
            $accounts[] = [
                'bank' => $this->bank_1,
                'nama_rekening' => $this->nama_rekening_1,
                'no_rekening' => $this->no_rekening_1,
            ];
        }

        if ($this->bank_2 && $this->nama_rekening_2 && $this->no_rekening_2) {
            $accounts[] = [
                'bank' => $this->bank_2,
                'nama_rekening' => $this->nama_rekening_2,
                'no_rekening' => $this->no_rekening_2,
            ];
        }

        if ($this->bank_3 && $this->nama_rekening_3 && $this->no_rekening_3) {
            $accounts[] = [
                'bank' => $this->bank_3,
                'nama_rekening' => $this->nama_rekening_3,
                'no_rekening' => $this->no_rekening_3,
            ];
        }

        return $accounts;
    }

    public function banks()
    {
        return $this->belongsToMany(Bank::class, 'bank_supplier_accounts')
            ->withPivot('nama_rekening', 'no_rekening')
            ->withTimestamps();
    }
}
