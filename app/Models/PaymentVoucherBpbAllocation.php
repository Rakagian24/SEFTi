<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucherBpbAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_voucher_id',
        'bpb_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:5',
    ];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function bpb()
    {
        return $this->belongsTo(Bpb::class);
    }
}
