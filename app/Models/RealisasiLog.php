<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RealisasiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'realisasi_id',
        'action',
        'meta',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function realisasi()
    {
        return $this->belongsTo(Realisasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
