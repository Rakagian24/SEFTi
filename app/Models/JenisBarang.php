<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class JenisBarang extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'nama_jenis_barang',
        'singkatan',
        'status',
    ];

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'jenis_barang_id');
    }
}
