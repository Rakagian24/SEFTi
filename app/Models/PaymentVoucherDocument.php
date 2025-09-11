<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucherDocument extends Model
{
    use HasFactory;

    protected $fillable = ['payment_voucher_id','type','active','path','original_name'];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }
}

