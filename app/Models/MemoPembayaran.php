<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;
use Carbon\Carbon;

class MemoPembayaran extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'no_mb',
        'department_id',
        'perihal_id',
        'purchase_order_id',
        'detail_keperluan',
        'total',
        'metode_pembayaran',
        'supplier_id',
        'bank_id',
        'nama_rekening',
        'no_rekening',
        'no_kartu_kredit',
        'no_giro',
        'tanggal_giro',
        'tanggal_cair',
        'keterangan',
        'diskon',
        'ppn',
        'ppn_nominal',
        'pph_id',
        'pph_nominal',
        'grand_total',
        'tanggal',
        'status',
        'created_by',
        'updated_by',
        'canceled_by',
        'canceled_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
        'canceled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'ppn' => 'boolean',
        'total' => 'decimal:5',
        'diskon' => 'decimal:5',
        'ppn_nominal' => 'decimal:5',
        'pph_nominal' => 'decimal:5',
        'grand_total' => 'decimal:5',
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

    public function purchaseOrders()
    {
        return $this->belongsToMany(PurchaseOrder::class, 'memo_pembayaran_purchase_orders');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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

    public function canceler()
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

    public function logs()
    {
        return $this->hasMany(MemoPembayaranLog::class);
    }

    /**
     * Scope untuk data aktif (tidak dibatalkan)
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'Canceled');
    }

    /**
     * Scope untuk data draft
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    /**
     * Scope untuk data in progress
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'In Progress');
    }

    /**
     * Scope untuk data approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    /**
     * Scope untuk data rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
    }

    /**
     * Scope untuk data canceled
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'Canceled');
    }

    /**
     * Check if memo can be edited
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['Draft']);
    }

    /**
     * Check if memo can be deleted
     */
    public function canBeDeleted()
    {
        return in_array($this->status, ['Draft']);
    }

    /**
     * Check if memo can be sent
     */
    public function canBeSent()
    {
        return $this->status === 'Draft';
    }

    /**
     * Check if memo can be downloaded
     */
    public function canBeDownloaded()
    {
        return in_array($this->status, ['In Progress', 'Approved']);
    }
}
