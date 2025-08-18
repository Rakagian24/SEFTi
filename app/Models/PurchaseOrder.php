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
        'tipe_po',
        'department_id',
        'perihal_id',
        'no_invoice',
        'harga',
        'total',
        'detail_keperluan',
        'tanggal',
        'status',
        'metode_pembayaran',
        'bank_id',
        'nama_rekening',
        'no_rekening',
        'no_kartu_kredit',
        'no_giro',
        'tanggal_giro',
        'tanggal_cair',
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
        'diskon',
        'ppn',
        'ppn_nominal',
        'pph_id',
        'pph_nominal',
        'grand_total',
        'dokumen',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
        'canceled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'ppn' => 'boolean',
        'total' => 'decimal:2',
        'diskon' => 'decimal:2',
        'ppn_nominal' => 'decimal:2',
        'pph_nominal' => 'decimal:2',
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

    public function perihal()
    {
        return $this->belongsTo(Perihal::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function pph()
    {
        return $this->belongsTo(Pph::class);
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
