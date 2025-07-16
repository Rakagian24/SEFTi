<?php

// app/Models/ArPartnerLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArPartnerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ar_partner_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function arPartner()
    {
        return $this->belongsTo(ArPartner::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
