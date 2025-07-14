<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'otp',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function isExpired()
    {
        return now()->isAfter($this->expires_at);
    }

    public function isVerified()
    {
        return !is_null($this->verified_at);
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
    }
}
