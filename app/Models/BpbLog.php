<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpbLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bpb_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function bpb()
    {
        return $this->belongsTo(Bpb::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
