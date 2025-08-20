<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Termin extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_referensi',
        'jumlah_termin',
        'status',
    ];

    protected $casts = [
        'jumlah_termin' => 'integer',
    ];
}
