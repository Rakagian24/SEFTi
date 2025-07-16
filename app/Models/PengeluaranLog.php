<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pengeluaran_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function pengeluaran()
    {
        return $this->belongsTo(Pengeluaran::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
