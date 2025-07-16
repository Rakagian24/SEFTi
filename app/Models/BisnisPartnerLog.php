<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BisnisPartnerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bisnis_partner_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function bisnisPartner()
    {
        return $this->belongsTo(BisnisPartner::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
