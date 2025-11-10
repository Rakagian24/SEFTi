<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PvTempActive extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'user_id',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
