<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucherDpAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_voucher_id',
        'dp_payment_voucher_id',
        'amount',
    ];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function dpPaymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'dp_payment_voucher_id');
    }
}
