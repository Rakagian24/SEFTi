<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasActiveStatus;

class Perihal extends Model
{
    use SoftDeletes;
    use HasActiveStatus;

    protected $fillable = [
        'nama',
        'deskripsi',
        'status',
    ];

    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }
}
