<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemoPembayaranLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'memo_pembayaran_id',
        'action',
        'description',
        'user_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function memoPembayaran()
    {
        return $this->belongsTo(MemoPembayaran::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
