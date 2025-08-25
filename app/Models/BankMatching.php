<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class BankMatching extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_name',
        'account_number',
        'account_name',
        'branch',
        'department_id',
        'user_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }
}
