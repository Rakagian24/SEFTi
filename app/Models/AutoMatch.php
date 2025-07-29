<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoMatch extends Model
{
    protected $fillable = [
        'bank_masuk_id',
        'kwitansi_id',
        'kwitansi_no',
        'kwitansi_tanggal',
        'kwitansi_nilai',
        'bank_masuk_no',
        'bank_masuk_tanggal',
        'bank_masuk_nilai',
        'match_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'kwitansi_tanggal' => 'date',
        'bank_masuk_tanggal' => 'date',
        'match_date' => 'date',
        'kwitansi_nilai' => 'double',
        'bank_masuk_nilai' => 'double',
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
