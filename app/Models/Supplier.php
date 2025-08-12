<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\DepartmentScope;

class Supplier extends Model
{
    protected $fillable = [
        'nama_supplier',
        'alamat',
        'email',
        'no_telepon',
        'department_id',
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

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

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

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Scope untuk search yang dioptimasi
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('id', 'like', "%$search%")
              ->orWhere('nama_supplier', 'like', "%$search%")
              ->orWhere('alamat', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhere('no_telepon', 'like', "%$search%")
              ->orWhere('terms_of_payment', 'like', "%$search%")
              ->orWhere('created_at', 'like', "%$search%")
              ->orWhere('updated_at', 'like', "%$search%")
              ->orWhereHas('banks', function($b) use ($search) {
                  $b->where('nama_bank', 'like', "%$search%")
                    ->orWhere('singkatan', 'like', "%$search%");
              });
        });
    }

    /**
     * Scope untuk filter berdasarkan terms_of_payment
     */
    public function scopeByTermsOfPayment($query, $termsOfPayment)
    {
        return $query->where('terms_of_payment', $termsOfPayment);
    }

    /**
     * Scope untuk filter berdasarkan department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope untuk filter berdasarkan bank
     */
    public function scopeByBank($query, $bankId)
    {
        return $query->whereHas('banks', function($q) use ($bankId) {
            $q->where('banks.id', $bankId);
        });
    }
}
