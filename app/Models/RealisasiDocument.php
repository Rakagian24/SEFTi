<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RealisasiDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'realisasi_id',
        'type',
        'active',
        'path',
        'original_name',
    ];

    public function realisasi()
    {
        return $this->belongsTo(Realisasi::class);
    }
}
