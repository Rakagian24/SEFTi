<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pph extends Model
{
    protected $fillable = [
        'kode_pph',
        'nama_pph',
        'tarif_pph',
        'deskripsi',
        'status',
    ];
}
