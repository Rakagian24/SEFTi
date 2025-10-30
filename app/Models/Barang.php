<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class Barang extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'nama_barang',
        'jenis_barang_id',
        'satuan',
        'status',
    ];

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }
}
