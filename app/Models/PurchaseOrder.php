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
        'termin_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
        'canceled_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function pph()
    {
        return $this->belongsTo(Pph::class);
    }

    public function termin()
    {
        return $this->belongsTo(Termin::class);
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
              ->orWhere('purchase_orders.pph_nominal', 'like', "%$search%")
              ->orWhere('purchase_orders.grand_total', 'like', "%$search%")
              ->orWhere('purchase_orders.status', 'like', "%$search%")
              ->orWhere('purchase_orders.nama_rekening', 'like', "%$search%")
              ->orWhere('purchase_orders.no_rekening', 'like', "%$search%")
              ->orWhere('purchase_orders.no_kartu_kredit', 'like', "%$search%")
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
                          ->from('banks')
                          ->whereColumn('banks.id', 'purchase_orders.bank_id')
                          ->where('banks.nama_bank', 'like', "%$search%");
              })
              ->orWhereExists(function($subQuery) use ($search) {
                  $subQuery->select(DB::raw(1))
                          ->from('pphs')
                          ->whereColumn('pphs.id', 'purchase_orders.pph_id')
                          ->where('pphs.nama_pph', 'like', "%$search%");
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
                    ->leftJoin('banks', 'purchase_orders.bank_id', '=', 'banks.id')
                    ->leftJoin('pphs', 'purchase_orders.pph_id', '=', 'pphs.id')
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
                          ->orWhere('purchase_orders.pph_nominal', 'like', "%$search%")
                          ->orWhere('purchase_orders.grand_total', 'like', "%$search%")
                          ->orWhere('purchase_orders.status', 'like', "%$search%")
                          ->orWhere('purchase_orders.nama_rekening', 'like', "%$search%")
                          ->orWhere('purchase_orders.no_rekening', 'like', "%$search%")
                          ->orWhere('purchase_orders.no_kartu_kredit', 'like', "%$search%")
                          ->orWhere('purchase_orders.no_giro', 'like', "%$search%")
                          ->orWhere('departments.name', 'like', "%$search%")
                          ->orWhere('perihals.nama', 'like', "%$search%")
                          ->orWhere('suppliers.nama_supplier', 'like', "%$search%")
                          ->orWhere('banks.nama_bank', 'like', "%$search%")
                          ->orWhere('pphs.nama_pph', 'like', "%$search%")
                          ->orWhere('termins.no_referensi', 'like', "%$search%")
                          ->orWhere('creators.name', 'like', "%$search%");
                    })
                    ->select('purchase_orders.*');
    }
}
