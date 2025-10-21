<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoAnggaranItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_anggaran_id',
        'jenis_pengeluaran_id',
        'jenis_pengeluaran_text',
        'keterangan',
        'harga',
        'qty',
        'satuan',
        'subtotal',
    ];

    protected $casts = [
        'harga' => 'decimal:5',
        'qty' => 'decimal:5',
        'subtotal' => 'decimal:5',
    ];

    public function poAnggaran()
    {
        return $this->belongsTo(PoAnggaran::class);
    }
}
