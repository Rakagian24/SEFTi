<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucherMemoAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_voucher_id',
        'memo_pembayaran_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:5',
    ];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function memo()
    {
        return $this->belongsTo(MemoPembayaran::class, 'memo_pembayaran_id');
    }
}
