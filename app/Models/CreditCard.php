<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;
use App\Models\Concerns\HasActiveStatus;

class CreditCard extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'department_id',
        'bank_id',
        'no_kartu_kredit',
        'nama_pemilik',
        'status',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}

