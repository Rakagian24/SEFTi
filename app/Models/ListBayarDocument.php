<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListBayarDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_list_bayar',
        'tanggal',
        'department_id',
        'jumlah_pv',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentVouchers()
    {
        return $this->belongsToMany(PaymentVoucher::class, 'list_bayar_document_payment_voucher');
    }
}
