<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoAnggaranLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'po_anggaran_id',
        'action',
        'meta',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function poAnggaran()
    {
        return $this->belongsTo(PoAnggaran::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
