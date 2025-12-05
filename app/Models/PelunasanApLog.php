<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PelunasanApLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pelunasan_ap_id',
        'user_id',
        'action',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'json',
    ];

    public function pelunasanAp()
    {
        return $this->belongsTo(PelunasanAp::class, 'pelunasan_ap_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
