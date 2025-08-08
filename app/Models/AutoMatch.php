<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoMatch extends Model
{
    protected $fillable = [
        'bank_masuk_id',
        'sj_no',
        'sj_tanggal',
        'sj_nilai',
        'invoice_customer_name',
        'invoice_department',
        'bm_no',
        'bm_tanggal',
        'bm_nilai',
        'bank_masuk_customer_name',
        'bank_masuk_department',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sj_tanggal' => 'date',
        'bm_tanggal' => 'date',
        'sj_nilai' => 'decimal:5',
        'bm_nilai' => 'decimal:5',
    ];

    public function bankMasuk()
    {
        return $this->belongsTo(BankMasuk::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
