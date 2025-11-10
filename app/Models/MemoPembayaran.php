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

    // Flag to prevent double logging in observer
    public $skip_observer_log = false;

    protected $fillable = [
        'no_mb',
        'department_id',
        'purchase_order_id',
        'detail_keperluan',
        'total',
        'cicilan',
        'metode_pembayaran',
        'supplier_id',
        'bank_supplier_account_id',
        'credit_card_id',
        'no_giro',
        'tanggal_giro',
        'tanggal_cair',
        'keterangan',
        'tanggal',
        'status',
        'created_by',
        'updated_by',
        'canceled_by',
        'canceled_at',
        'verified_by',
        'verified_at',
        'validated_by',
        'validated_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'approval_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
        'canceled_at' => 'datetime',
        'verified_at' => 'datetime',
        'validated_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'total' => 'decimal:5',
        'cicilan' => 'decimal:5',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // perihal relation removed: Memo Pembayaran derives perihal information from related Purchase Orders

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

    public function bankSupplierAccount()
    {
        return $this->belongsTo(BankSupplierAccount::class);
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function termin()
    {
        return $this->hasOneThrough(Termin::class, PurchaseOrder::class, 'id', 'id', 'purchase_order_id', 'termin_id');
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

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function logs()
    {
        return $this->hasMany(MemoPembayaranLog::class);
    }

    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class, 'memo_pembayaran_id');
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
     * Scope untuk data verified
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'Verified');
    }

    /**
     * Scope untuk data validated
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'Validated');
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
        return in_array($this->status, ['Draft', 'Rejected']);
    }

    /**
     * Check if user can edit this memo based on role and status
     */
    public function canBeEditedByUser($user)
    {
        // Admin can edit any draft or rejected memo
        if ($user->role->name === 'Admin') {
            return in_array($this->status, ['Draft', 'Rejected']);
        }

        // For draft status: only creator can edit
        if ($this->status === 'Draft') {
            return $this->created_by === $user->id;
        }

        // For rejected status: only creator or admin can edit
        if ($this->status === 'Rejected') {
            return $this->created_by === $user->id || $user->role->name === 'Admin';
        }

        return false;
    }

    /**
     * Check if user can send this memo based on role and status
     */
    public function canBeSentByUser($user)
    {
        // Allow sending when status is Draft or Rejected
        if (!in_array($this->status, ['Draft', 'Rejected'])) {
            return false;
        }

        // Admin can send any Draft/Rejected memo
        if (($user->role->name ?? '') === 'Admin') {
            return true;
        }

        // Only the creator can send their own Draft/Rejected memo
        return (int)$this->created_by === (int)$user->id;
    }

    /**
     * Check if memo can be deleted
     */
    public function canBeDeleted()
    {
        return in_array($this->status, ['Draft']);
    }

    /**
     * Check if user can delete this memo based on role and status
     */
    public function canBeDeletedByUser($user)
    {
        // Only draft memos can be deleted
        if ($this->status !== 'Draft') {
            return false;
        }

        // Admin can delete any draft memo
        if ($user->role->name === 'Admin') {
            return true;
        }

        // Only creator can delete their own draft memo
        return $this->created_by === $user->id;
    }

    /**
     * Check if memo can be sent
     */
    public function canBeSent()
    {
        return in_array($this->status, ['Draft', 'Rejected']);
    }

    /**
     * Check if memo can be downloaded
     */
    public function canBeDownloaded()
    {
        return in_array($this->status, ['In Progress', 'Verified', 'Validated', 'Approved']);
    }

    /**
     * Check if the related PO's termin is completed
     */
    public function isTerminCompleted()
    {
        if (!$this->purchaseOrder || $this->purchaseOrder->tipe_po !== 'Lainnya' || !$this->purchaseOrder->termin_id) {
            return false;
        }

        $termin = $this->purchaseOrder->termin;
        if (!$termin) {
            return false;
        }

        $jumlahDibuat = $termin->jumlah_termin_dibuat;
        $jumlahTotal = $termin->jumlah_termin;
        $statusTermin = $termin->status_termin;

        return ($statusTermin === 'completed') || ($jumlahTotal > 0 && $jumlahDibuat >= $jumlahTotal);
    }

    /**
     * Get termin info for display
     */
    public function getTerminInfo()
    {
        if (!$this->purchaseOrder || $this->purchaseOrder->tipe_po !== 'Lainnya' || !$this->purchaseOrder->termin_id) {
            return null;
        }

        $termin = $this->purchaseOrder->termin;
        if (!$termin) {
            return null;
        }

        return [
            'id' => $termin->id,
            'no_referensi' => $termin->no_referensi,
            'jumlah_termin' => $termin->jumlah_termin,
            'jumlah_termin_dibuat' => $termin->jumlah_termin_dibuat,
            'status' => $termin->status_termin,
            'total_cicilan' => $termin->total_cicilan,
            'sisa_pembayaran' => $termin->sisa_pembayaran,
        ];
    }

    /**
     * Accessor methods untuk backward compatibility
     */
    public function getEffectiveNamaRekeningAttribute()
    {
        return $this->bankSupplierAccount?->nama_rekening ?? null;
    }

    public function getEffectiveNoRekeningAttribute()
    {
        return $this->bankSupplierAccount?->no_rekening ?? null;
    }

    public function getEffectiveBankIdAttribute()
    {
        return $this->bankSupplierAccount?->bank_id ?? null;
    }

    public function getEffectiveNoKartuKreditAttribute()
    {
        return $this->creditCard?->no_kartu_kredit ?? null;
    }
}
