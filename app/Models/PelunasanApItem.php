<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PelunasanApItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pelunasan_ap_id',
        'payment_voucher_id',
        'nilai_pv',
        'outstanding',
        'nilai_pelunasan',
        'sisa',
    ];

    protected $casts = [
        'nilai_pv' => 'decimal:5',
        'outstanding' => 'decimal:5',
        'nilai_pelunasan' => 'decimal:5',
        'sisa' => 'decimal:5',
    ];

    public function pelunasanAp()
    {
        return $this->belongsTo(PelunasanAp::class, 'pelunasan_ap_id');
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }
}
