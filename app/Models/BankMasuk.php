<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankMasuk extends Model
{
    protected $fillable = [
        'no_bm',
        'tanggal',
        'match_date',
        'tipe_po',
        'terima_dari',
        'nilai',
        'bank_account_id',
        'department_id',
        'note',
        'purchase_order_id',
        'input_lainnya',
        'ar_partner_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'match_date' => 'date',
        'nilai' => 'decimal:5',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function arPartner()
    {
        return $this->belongsTo(ArPartner::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relasi ke model PurchaseOrder jika sudah ada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function purchaseOrder()
    {
        // Future-proofing, relasi ke model PurchaseOrder jika sudah ada
        return $this->belongsTo('App\\Models\\PurchaseOrder', 'purchase_order_id');
    }

    /**
     * Scope untuk data aktif
     */
    public function scopeActive($query)
    {
        return $query->where('bank_masuks.status', 'aktif');
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
        return $query->where('nilai', $value);
    }

    /**
     * Scope untuk filter berdasarkan tipe PO
     */
    public function scopeByTipePo($query, $tipePo)
    {
        return $query->where('tipe_po', $tipePo);
    }

    /**
     * Scope untuk filter berdasarkan terima dari
     */
    public function scopeByTerimaDari($query, $terimaDari)
    {
        return $query->where('terima_dari', $terimaDari);
    }

    /**
     * Scope untuk search yang dioptimasi
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('bank_masuks.no_bm', 'like', "%$search%")
              ->orWhere('bank_masuks.purchase_order_id', 'like', "%$search%")
              ->orWhere('bank_masuks.tanggal', 'like', "%$search%")
              ->orWhere('bank_masuks.note', 'like', "%$search%")
              ->orWhere('bank_masuks.nilai', 'like', "%$search%")
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('bank_accounts')
                          ->whereColumn('bank_accounts.id', 'bank_masuks.bank_account_id')
                          ->where('bank_accounts.no_rekening', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('bank_accounts')
                          ->join('departments', 'bank_accounts.department_id', '=', 'departments.id')
                          ->whereColumn('bank_accounts.id', 'bank_masuks.bank_account_id')
                          ->where('departments.name', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('ar_partners')
                          ->whereColumn('ar_partners.id', 'bank_masuks.ar_partner_id')
                          ->where('ar_partners.nama_ap', 'like', "%$search%");
              });
        });
    }

    /**
     * Scope untuk search yang dioptimasi dengan joins
     */
    public function scopeSearchOptimized($query, $search)
    {
        return $query->leftJoin('bank_accounts', 'bank_masuks.bank_account_id', '=', 'bank_accounts.id')
                    ->leftJoin('departments', 'bank_accounts.department_id', '=', 'departments.id')
                    ->leftJoin('ar_partners', 'bank_masuks.ar_partner_id', '=', 'ar_partners.id')
                    ->where(function($q) use ($search) {
                        $q->where('bank_masuks.no_bm', 'like', "%$search%")
                          ->orWhere('bank_masuks.purchase_order_id', 'like', "%$search%")
                          ->orWhere('bank_masuks.tanggal', 'like', "%$search%")
                          ->orWhere('bank_masuks.note', 'like', "%$search%")
                          ->orWhere('bank_masuks.nilai', 'like', "%$search%")
                          ->orWhere('bank_accounts.no_rekening', 'like', "%$search%")
                          ->orWhere('departments.name', 'like', "%$search%")
                          ->orWhere('ar_partners.nama_ap', 'like', "%$search%");
                    })
                    ->select('bank_masuks.*');
    }

    /**
     * Get nilai attribute without trailing zeros
     */
    public function getNilaiAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        // Return the raw value to preserve exact decimal places
        return $value;
    }
}
