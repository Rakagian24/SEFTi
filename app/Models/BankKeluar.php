<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Scopes\DepartmentScope;

class BankKeluar extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'no_bk',
        'tanggal',
        'tipe_bk',
        'department_id',
        'nominal',
        'metode_bayar',
        'supplier_id',
        'bisnis_partner_id',
        'bank_id',
        'bank_supplier_account_id',
        'credit_card_id',
        'nama_pemilik_rekening',
        'no_rekening',
        'note',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:5',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bisnisPartner()
    {
        return $this->belongsTo(BisnisPartner::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankSupplierAccount()
    {
        return $this->belongsTo(BankSupplierAccount::class);
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs()
    {
        return $this->hasMany(BankKeluarLog::class);
    }

    /**
     * Scope untuk data aktif
     */
    public function scopeActive($query)
    {
        return $query->where('bank_keluars.status', 'aktif');
    }

    /**
     * Scope untuk filter berdasarkan rentang tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope untuk filter berdasarkan nilai
     */
    public function scopeByValue($query, $value)
    {
        return $query->where('nominal', $value);
    }

    // Scope lama byTipePv tidak lagi dipakai karena kolom diganti menjadi tipe_bk.

    /**
     * Scope untuk search yang dioptimasi
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('bank_keluars.no_bk', 'like', "%$search%")
              ->orWhere('bank_keluars.tanggal', 'like', "%$search%")
              ->orWhere('bank_keluars.note', 'like', "%$search%")
              ->orWhere('bank_keluars.nominal', 'like', "%$search%")
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('departments')
                          ->whereColumn('departments.id', 'bank_keluars.department_id')
                          ->where('departments.name', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('suppliers')
                          ->whereColumn('suppliers.id', 'bank_keluars.supplier_id')
                          ->where('suppliers.nama', 'like', "%$search%");
              });
        });
    }

    /**
     * Scope untuk search yang dioptimasi dengan joins
     */
    public function scopeSearchOptimized($query, $search)
    {
        return $query->leftJoin('departments', 'bank_keluars.department_id', '=', 'departments.id')
                    ->leftJoin('suppliers', 'bank_keluars.supplier_id', '=', 'suppliers.id')
                    ->where(function($q) use ($search) {
                        $q->where('bank_keluars.no_bk', 'like', "%$search%")
                          ->orWhere('bank_keluars.tanggal', 'like', "%$search%")
                          ->orWhere('bank_keluars.note', 'like', "%$search%")
                          ->orWhere('bank_keluars.nominal', 'like', "%$search%")
                          ->orWhere('departments.name', 'like', "%$search%")
                          ->orWhere('suppliers.nama', 'like', "%$search%");
                    })
                    ->select('bank_keluars.*');
    }

    /**
     * Get nominal attribute without trailing zeros
     */
    public function getNominalAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        // Return the raw value to preserve exact decimal places
        return $value;
    }
}
