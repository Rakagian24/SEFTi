<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccountLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
