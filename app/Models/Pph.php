<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pph extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'kode_pph',
        'nama_pph',
        'tarif_pph',
        'deskripsi',
        'status',
    ];
}
