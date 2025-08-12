<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\DepartmentScope;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_po',
        'department_id',
        'perihal',
        'tanggal',
        'status',
        'metode_pembayaran',
        'created_by',
        'updated_by',
        'canceled_by',
        'canceled_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'cicilan',
        'termin',
        'nominal',
        'keterangan',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function logs()
    {
        return $this->hasMany(PurchaseOrderLog::class);
    }
}
