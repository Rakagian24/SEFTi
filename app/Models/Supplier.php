<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;
use App\Models\Concerns\HasActiveStatus;

class Supplier extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'nama_supplier',
        'alamat',
        'email',
        'no_telepon',
        'department_id',
        'terms_of_payment',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function banks()
    {
        return $this->belongsToMany(Bank::class, 'bank_supplier_accounts')
            ->withPivot('nama_rekening', 'no_rekening')
            ->withTimestamps();
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankSupplierAccount::class);
    }

    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
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
