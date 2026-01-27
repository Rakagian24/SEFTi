<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengeluaranBarangItem extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran_barang_items';

    protected $fillable = [
        'pengeluaran_barang_id',
        'barang_id',
        'qty',
        'keterangan',
    ];

    public function pengeluaranBarang()
    {
        return $this->belongsTo(PengeluaranBarang::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
