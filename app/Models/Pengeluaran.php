<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class Pengeluaran extends Model
{
    // use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'nama',
        'satuan',
        'deskripsi',
        'perihal_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function perihal()
    {
        return $this->belongsTo(Perihal::class);
    }
}
