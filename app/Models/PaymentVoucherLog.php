<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucherLog extends Model
{
    use HasFactory;

    protected $fillable = ['payment_voucher_id','user_id','action','note'];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

