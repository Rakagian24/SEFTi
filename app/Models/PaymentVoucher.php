<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class PaymentVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_pv',
        'purchase_order_id',
        'tanggal',
        'tipe_pv',
        'supplier_id',
        'department_id',
        'perihal_id',
        'nominal',
        'metode_bayar',
        'no_giro',
        'tanggal_giro',
        'tanggal_cair',
        'note',
        'keterangan',
        'no_bk',
        'status',
        'creator_id',
        // redundant presentation fields removed; derive via relations instead
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
        'nominal' => 'decimal:5',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function perihal()
    {
        return $this->belongsTo(Perihal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function documents()
    {
        return $this->hasMany(PaymentVoucherDocument::class);
    }

    public function logs()
    {
        return $this->hasMany(PaymentVoucherLog::class);
    }
}

