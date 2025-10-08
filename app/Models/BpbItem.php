<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpbItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bpb_id',
        'nama_barang',
        'qty',
        'satuan',
        'harga',
    ];

    public function bpb()
    {
        return $this->belongsTo(Bpb::class);
    }
}


