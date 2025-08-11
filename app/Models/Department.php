<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'alias',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
