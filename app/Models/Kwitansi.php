<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kwitansi extends Model
{
    protected $connection = 'gjtrading3';
    protected $table = 'tpkwitansi';
    protected $primaryKey = 'KWITANSI_ID';

    protected $fillable = [
        'KWITANSI_ID',
        'TANGGAL',
        'KONSUMEN_ID',
        'PENERIMA',
        'KETERANGAN',
        'VALUTA',
        'NILAI',
        'DIVISI',
        'KWITANSI_STAT',
    ];

    protected $casts = [
        'TANGGAL' => 'date',
        'NILAI' => 'double',
    ];

    // Disable timestamps karena tabel tidak memiliki created_at dan updated_at
    public $timestamps = false;

    // Accessor untuk no_kwitansi (menggunakan KWITANSI_ID)
    public function getNoKwitansiAttribute()
    {
        return $this->attributes['KWITANSI_ID'] ?? null;
    }

    // Accessor untuk tanggal
    public function getTanggalAttribute()
    {
        return $this->attributes['TANGGAL'] ?? null;
    }

    // Accessor untuk nilai
    public function getNilaiAttribute()
    {
        return $this->attributes['NILAI'] ?? null;
    }

    // Accessor untuk keterangan
    public function getKeteranganAttribute()
    {
        return $this->attributes['KETERANGAN'] ?? null;
    }

    // Accessor untuk status
    public function getStatusAttribute()
    {
        return $this->attributes['KWITANSI_STAT'] ?? null;
    }

    // Mutator untuk tanggal
    public function setTanggalAttribute($value)
    {
        $this->attributes['TANGGAL'] = $value;
    }

    // Mutator untuk nilai
    public function setNilaiAttribute($value)
    {
        $this->attributes['NILAI'] = $value;
    }

    // Mutator untuk keterangan
    public function setKeteranganAttribute($value)
    {
        $this->attributes['KETERANGAN'] = $value;
    }

    // Mutator untuk status
    public function setStatusAttribute($value)
    {
        $this->attributes['KWITANSI_STAT'] = $value;
    }

    /**
     * Scope untuk mencari kwitansi berdasarkan tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('TANGGAL', [$startDate, $endDate]);
    }

    /**
     * Scope untuk mencari kwitansi berdasarkan nilai
     */
    public function scopeByValue($query, $value)
    {
        return $query->where('NILAI', $value);
    }

    /**
     * Scope untuk kwitansi yang belum dimatch
     */
    public function scopeUnmatched($query)
    {
        return $query->where('KWITANSI_STAT', '!=', '1');
    }

    /**
     * Get the value of KWITANSI_ID
     */
    public function getKwitansiId()
    {
        return $this->attributes['KWITANSI_ID'] ?? null;
    }

    /**
     * Get the value of TANGGAL
     */
    public function getTanggalValue()
    {
        return $this->attributes['TANGGAL'] ?? null;
    }

    /**
     * Get the value of NILAI
     */
    public function getNilaiValue()
    {
        return $this->attributes['NILAI'] ?? null;
    }

    /**
     * Get the value of KETERANGAN
     */
    public function getKeteranganValue()
    {
        return $this->attributes['KETERANGAN'] ?? null;
    }
}
