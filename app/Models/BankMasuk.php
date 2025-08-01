<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BankMasuk extends Model
{
    protected $fillable = [
        'no_bm',
        'tanggal',
        'tipe_po',
        'terima_dari',
        'nilai',
        'bank_account_id',
        'note',
        'purchase_order_id',
        'input_lainnya',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nilai' => 'decimal:5',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
        return $query->where('status', 'aktif');
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
            $q->where('no_bm', 'like', "%$search%")
              ->orWhere('purchase_order_id', 'like', "%$search%")
              ->orWhere('tanggal', 'like', "%$search%")
              ->orWhere('note', 'like', "%$search%")
              ->orWhere('nilai', 'like', "%$search%")
              ->orWhereHas('bankAccount', function($q2) use ($search) {
                  $q2->where('nama_pemilik', 'like', "%$search%")
                     ->orWhere('no_rekening', 'like', "%$search%");
              });
        });
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
