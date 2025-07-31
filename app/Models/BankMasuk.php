<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
