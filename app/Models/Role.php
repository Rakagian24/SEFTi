<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class Role extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'name',
        'description',
        'permissions',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
        'status' => 'string',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
