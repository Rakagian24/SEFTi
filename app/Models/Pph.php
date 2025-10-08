<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class Pph extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'kode_pph',
        'nama_pph',
        'tarif_pph',
        'deskripsi',
        'status',
    ];
}
