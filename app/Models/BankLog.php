<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
