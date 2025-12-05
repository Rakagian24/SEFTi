<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class PelunasanAp extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_pl',
        'tanggal',
        'tipe_pelunasan',
        'bank_keluar_id',
        'bank_mutasi_id',
        'supplier_id',
        'nilai_dokumen_referensi',
        'keterangan',
        'status',
        'department_id',
        'creator_id',
        'verified_by',
        'verified_at',
        'verification_notes',
        'validated_by',
        'validated_at',
        'validation_notes',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'canceled_by',
        'canceled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nilai_dokumen_referensi' => 'decimal:5',
        'verified_at' => 'datetime',
        'validated_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function bankKeluar()
    {
        return $this->belongsTo(BankKeluar::class, 'bank_keluar_id');
    }

    public function bankMutasi()
    {
        return $this->belongsTo(BankMutasi::class, 'bank_mutasi_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PelunasanApItem::class, 'pelunasan_ap_id');
    }

    public function logs()
    {
        return $this->hasMany(PelunasanApLog::class, 'pelunasan_ap_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    // Accessors
    public function getTotalPvAttribute()
    {
        return $this->items->sum('nilai_pv');
    }

    public function getTotalAlokasisAttribute()
    {
        return $this->items->sum('nilai_pelunasan');
    }

    public function getTotalSisaAttribute()
    {
        return $this->items->sum('sisa');
    }
}
