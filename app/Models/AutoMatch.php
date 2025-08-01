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
        'bm_no',
        'bm_tanggal',
        'bm_nilai',
        'match_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sj_tanggal' => 'date',
        'bm_tanggal' => 'date',
        'match_date' => 'date',
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
