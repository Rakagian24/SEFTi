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
    ];

    protected $casts = [
        'jumlah_termin' => 'integer',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function getTotalCicilanAttribute()
    {
        // Hanya hitung cicilan dari PO yang bukan Draft
        return $this->purchaseOrders()
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

    public function getJumlahTerminDibuatAttribute()
    {
        // Hanya hitung jumlah termin dari PO yang bukan Draft
        return $this->purchaseOrders()
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
