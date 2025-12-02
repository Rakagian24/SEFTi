<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class Bpb extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'no_bpb',
        'department_id',
        'purchase_order_id',
        'payment_voucher_id',
        'tanggal',
        'status',
        'keterangan',
        'surat_jalan_no',
        'surat_jalan_file',
        'supplier_id',
        'created_by',
        'updated_by',
        'canceled_by',
        'canceled_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'subtotal',
        'diskon',
        'dpp',
        'ppn',
        'pph',
        'grand_total',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'canceled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
        'pph' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(BpbItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}


