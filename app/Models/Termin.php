<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Termin extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'no_referensi',
        'jumlah_termin',
        'keterangan',
        'status',
        'department_id',
    ];

    protected $casts = [
        'jumlah_termin' => 'integer',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function memoPembayarans()
    {
        return $this->hasManyThrough(
            MemoPembayaran::class,
            PurchaseOrder::class,
            'termin_id', // Foreign key on purchase_orders table
            'purchase_order_id', // Foreign key on memo_pembayarans table
            'id', // Local key on termins table
            'id' // Local key on purchase_orders table
        );
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function getTotalCicilanAttribute()
    {
        // Hitung total cicilan dari Memo Pembayaran yang bukan Draft
        return \App\Models\MemoPembayaran::whereHas('purchaseOrder', function($query) {
                $query->where('termin_id', $this->id);
            })
            ->where('status', '!=', 'Draft')
            ->sum('cicilan');
    }

    public function getSisaPembayaranAttribute()
    {
        // Ambil grand total dari PO pertama yang menggunakan termin ini
        $firstPO = $this->purchaseOrders()->with('items')->first();
        if (!$firstPO) return 0;

        // Hitung grand total dari barang-barang PO pertama
        $grandTotal = $firstPO->grand_total ?? 0;
        return max(0, $grandTotal - $this->total_cicilan);
    }

    public function getGrandTotalAttribute()
    {
        // Grand total Termin diambil dari grand_total PO pertama yang memakai termin ini
        $firstPO = $this->purchaseOrders()->with('items')->first();
        return $firstPO ? ($firstPO->grand_total ?? 0) : 0;
    }

    public function getJumlahTerminDibuatAttribute()
    {
        // Hitung jumlah termin dari Memo Pembayaran yang bukan Draft
        return \App\Models\MemoPembayaran::whereHas('purchaseOrder', function($query) {
                $query->where('termin_id', $this->id);
            })
            ->where('status', '!=', 'Draft')
            ->count();
    }

    public function getStatusTerminAttribute()
    {
        $jumlahDibuat = $this->jumlah_termin_dibuat;
        $jumlahTermin = $this->jumlah_termin;

        if ($jumlahDibuat >= $jumlahTermin) {
            return 'completed';
        } elseif ($jumlahDibuat > 0) {
            return 'in_progress';
        } else {
            return 'not_started';
        }
    }
}
