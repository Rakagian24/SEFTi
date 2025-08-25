<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankSupplierAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'bank_id',
        'nama_rekening',
        'no_rekening',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
