<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;
use App\Models\Concerns\HasActiveStatus;

class BankAccount extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

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
