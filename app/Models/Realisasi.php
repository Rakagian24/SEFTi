<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class Realisasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_realisasi',
        'tanggal',
        'department_id',
        'po_anggaran_id',
        'metode_pembayaran',
        'bank_id',
        'nama_rekening',
        'no_rekening',
        'total_anggaran',
        'total_realisasi',
        'note',
        'status',
        'created_by',
        'updated_by',
        'canceled_by',
        'approved_by',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_anggaran' => 'decimal:5',
        'total_realisasi' => 'decimal:5',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new DepartmentScope);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function poAnggaran()
    {
        return $this->belongsTo(PoAnggaran::class, 'po_anggaran_id');
    }

    public function items()
    {
        return $this->hasMany(RealisasiItem::class);
    }

    public function logs()
    {
        return $this->hasMany(RealisasiLog::class);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['Draft', 'Rejected']);
    }

    public function canBeDeleted(): bool
    {
        return $this->status === 'Draft';
    }

    public function canBeSent(): bool
    {
        return $this->status === 'Draft';
    }
}
