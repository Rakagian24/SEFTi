<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\DepartmentScope;

class BankAccount extends Model
{
    protected $fillable = [
        'department_id',
        'no_rekening',
        'bank_id',
        'status',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

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
