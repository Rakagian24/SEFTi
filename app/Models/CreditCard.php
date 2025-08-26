<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class CreditCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
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
}

