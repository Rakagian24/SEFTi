<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

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
    'supplier_id',
    'bank_supplier_account_id',
    'credit_card_id',
    'no_giro',
    'tanggal_giro',
    'tanggal_cair',
    'customer_id',
    'customer_bank_id',
    'created_by',
    'updated_by',
    'canceled_by',
    'canceled_at',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'rejection_reason',
    'verified_by',
    'verified_at',
    'verification_notes',
    'validated_by',
    'validated_at',
    'validation_notes',
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
    'termin_id',
    'jenis_barang_id',
];


    protected $casts = [
        'tanggal' => 'date',
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
        'canceled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'verified_at' => 'datetime',
        'validated_at' => 'datetime',
        'ppn' => 'boolean',
        'harga' => 'decimal:5',
        'total' => 'decimal:5',
        'diskon' => 'decimal:5',
        'ppn_nominal' => 'decimal:5',
        'pph_nominal' => 'decimal:5',
        'grand_total' => 'decimal:5',
        'cicilan' => 'decimal:5',
        'nominal' => 'decimal:5',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankSupplierAccount()
    {
        return $this->belongsTo(BankSupplierAccount::class);
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function customer()
    {
        return $this->belongsTo(ArPartner::class, 'customer_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function customerBank()
    {
        return $this->belongsTo(Bank::class, 'customer_bank_id');
    }

    public function pph()
    {
        return $this->belongsTo(Pph::class);
    }

    public function termin()
    {
        return $this->belongsTo(Termin::class);
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class);
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

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function logs()
    {
        return $this->hasMany(PurchaseOrderLog::class);
    }

    public function paymentVouchers()
    {
        return $this->belongsToMany(PaymentVoucher::class, 'payment_voucher_purchase_order')
            ->withTimestamps()
            ->withPivot(['subtotal']);
    }

    public function memoPembayarans()
    {
        return $this->belongsToMany(MemoPembayaran::class, 'memo_pembayaran_purchase_orders');
    }

    public function memoPembayaran()
    {
        return $this->hasOne(MemoPembayaran::class, 'purchase_order_id');
    }

    /**
     * Check if purchase order can be edited
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['Draft', 'Rejected']);
    }

    /**
     * Check if user can edit this purchase order based on role and status
     */
    public function canBeEditedByUser($user)
    {
        // Admin can edit any draft or rejected purchase order
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
     * Check if user can delete this purchase order based on role and status
     */
    public function canBeDeletedByUser($user)
    {
        // Only draft purchase orders can be deleted
        if ($this->status !== 'Draft') {
            return false;
        }

        // Admin can delete any draft purchase order
        if ($user->role->name === 'Admin') {
            return true;
        }

        // Only creator can delete their own draft purchase order
        return $this->created_by === $user->id;
    }

    /**
     * Check if user can send this purchase order based on role and status
     */
    public function canBeSentByUser($user)
    {
        // Only draft purchase orders can be sent
        if ($this->status !== 'Draft') {
            return false;
        }

        // Admin can send any draft purchase order
        if ($user->role->name === 'Admin') {
            return true;
        }

        // Only creator can send their own draft purchase order
        return $this->created_by === $user->id;
    }

    /**
     * Check if purchase order can be deleted
     */
    public function canBeDeleted()
    {
        return $this->status === 'Draft';
    }

    /**
     * Check if purchase order can be sent
     */
    public function canBeSent()
    {
        return $this->status === 'Draft';
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

    public function getEffectiveCustomerNamaRekeningAttribute()
    {
        // Untuk customer, kita perlu implementasi khusus karena tidak ada relasi langsung
        // Ini bisa diambil dari customer bank account atau di-handle di frontend
        return null;
    }

    public function getEffectiveCustomerNoRekeningAttribute()
    {
        // Untuk customer, kita perlu implementasi khusus karena tidak ada relasi langsung
        // Ini bisa diambil dari customer bank account atau di-handle di frontend
        return null;
    }

    /**
     * Scope untuk search yang komprehensif
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('purchase_orders.no_po', 'like', "%$search%")
              ->orWhere('purchase_orders.no_invoice', 'like', "%$search%")
              ->orWhere('purchase_orders.tanggal', 'like', "%$search%")
              ->orWhere('purchase_orders.detail_keperluan', 'like', "%$search%")
              ->orWhere('purchase_orders.keterangan', 'like', "%$search%")
              ->orWhere('purchase_orders.metode_pembayaran', 'like', "%$search%")
              ->orWhere('purchase_orders.total', 'like', "%$search%")
              ->orWhere('purchase_orders.diskon', 'like', "%$search%")
              ->orWhere('purchase_orders.ppn_nominal', 'like', "%$search%")
              ->orWhere('purchase_orders.grand_total', 'like', "%$search%")
              ->orWhere('purchase_orders.status', 'like', "%$search%")
              ->orWhere('purchase_orders.no_giro', 'like', "%$search%")
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('departments')
                          ->whereColumn('departments.id', 'purchase_orders.department_id')
                          ->where('departments.name', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('perihals')
                          ->whereColumn('perihals.id', 'purchase_orders.perihal_id')
                          ->where('perihals.nama', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('suppliers')
                          ->whereColumn('suppliers.id', 'purchase_orders.supplier_id')
                          ->where('suppliers.nama_supplier', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('bank_supplier_accounts')
                          ->join('banks', 'banks.id', '=', 'bank_supplier_accounts.bank_id')
                          ->whereColumn('bank_supplier_accounts.id', 'purchase_orders.bank_supplier_account_id')
                          ->where('banks.nama_bank', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('termins')
                          ->whereColumn('termins.id', 'purchase_orders.termin_id')
                          ->where('termins.no_referensi', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'purchase_orders.created_by')
                          ->where('users.name', 'like', "%$search%");
              });
        });
    }

    /**
     * Scope untuk search yang dioptimasi dengan joins
     */
    public function scopeSearchOptimized($query, $search)
    {
        return $query->leftJoin('departments', 'purchase_orders.department_id', '=', 'departments.id')
                    ->leftJoin('perihals', 'purchase_orders.perihal_id', '=', 'perihals.id')
                    ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                    ->leftJoin('bank_supplier_accounts', 'purchase_orders.bank_supplier_account_id', '=', 'bank_supplier_accounts.id')
                    ->leftJoin('banks', 'bank_supplier_accounts.bank_id', '=', 'banks.id')
                    ->leftJoin('termins', 'purchase_orders.termin_id', '=', 'termins.id')
                    ->leftJoin('users as creators', 'purchase_orders.created_by', '=', 'creators.id')
                    ->where(function($q) use ($search) {
                        $q->where('purchase_orders.no_po', 'like', "%$search%")
                          ->orWhere('purchase_orders.no_invoice', 'like', "%$search%")
                          ->orWhere('purchase_orders.tanggal', 'like', "%$search%")
                          ->orWhere('purchase_orders.detail_keperluan', 'like', "%$search%")
                          ->orWhere('purchase_orders.keterangan', 'like', "%$search%")
                          ->orWhere('purchase_orders.metode_pembayaran', 'like', "%$search%")
                          ->orWhere('purchase_orders.total', 'like', "%$search%")
                          ->orWhere('purchase_orders.diskon', 'like', "%$search%")
                          ->orWhere('purchase_orders.ppn_nominal', 'like', "%$search%")
                          ->orWhere('purchase_orders.grand_total', 'like', "%$search%")
                          ->orWhere('purchase_orders.status', 'like', "%$search%")
                          ->orWhere('purchase_orders.no_giro', 'like', "%$search%")
                          ->orWhere('departments.name', 'like', "%$search%")
                          ->orWhere('perihals.nama', 'like', "%$search%")
                          ->orWhere('suppliers.nama_supplier', 'like', "%$search%")
                          ->orWhere('banks.nama_bank', 'like', "%$search%")
                          ->orWhere('termins.no_referensi', 'like', "%$search%")
                          ->orWhere('creators.name', 'like', "%$search%");
                    })
                    ->select('purchase_orders.*');
    }
}
