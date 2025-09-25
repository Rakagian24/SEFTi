<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankMutasi extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_masuk_id',
        'no_mutasi',
        'tanggal',
        'tujuan_department_id',
        'ar_partner_id',
        'unrealized',
        'nominal',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'unrealized' => 'boolean',
        'nominal' => 'decimal:2',
    ];

    public function bankMasuk()
    {
        return $this->belongsTo(BankMasuk::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'tujuan_department_id');
    }

    public function arPartner()
    {
        return $this->belongsTo(ArPartner::class);
    }
}


