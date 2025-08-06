<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'department_id',
        'no_rekening',
        'bank_id',
        'status',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bankMasuks()
    {
        return $this->hasMany(BankMasuk::class);
    }
}
