<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\DepartmentScope;

class PoAnggaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'no_po_anggaran',
        'tanggal',
        'department_id',
        'perihal_id',
        'detail_keperluan',
        'metode_pembayaran',
        'bank_id',
        'bisnis_partner_id',
        'credit_card_id',
        'nama_rekening',
        'no_rekening',
        'no_giro',
        'tanggal_giro',
        'tanggal_cair',
        'nominal',
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
        'tanggal_giro' => 'date',
        'tanggal_cair' => 'date',
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

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function bisnisPartner()
    {
        return $this->belongsTo(BisnisPartner::class);
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function perihal()
    {
        return $this->belongsTo(Perihal::class);
    }

    public function items()
    {
        return $this->hasMany(PoAnggaranItem::class);
    }

    public function logs()
    {
        return $this->hasMany(PoAnggaranLog::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['Draft', 'Rejected']);
    }

    public function canBeDeleted()
    {
        return $this->status === 'Draft';
    }

    public function canBeSent()
    {
        return in_array($this->status, ['Draft', 'Rejected']);
    }
}
