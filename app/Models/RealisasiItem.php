<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RealisasiItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'realisasi_id',
        'po_anggaran_item_id',
        'jenis_pengeluaran_id',
        'jenis_pengeluaran_text',
        'keterangan',
        // 'keterangan_realisasi',
        'harga',
        'qty',
        'satuan',
        'subtotal',
        'realisasi',
    ];

    protected $casts = [
        'harga' => 'decimal:5',
        'qty' => 'decimal:5',
        'subtotal' => 'decimal:5',
        'realisasi' => 'decimal:5',
    ];

    public function realisasi()
    {
        return $this->belongsTo(Realisasi::class);
    }
}
