<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SjNew extends Model
{
    use SoftDeletes;

    protected $connection = 'gjtrading3';
    protected $table = 'v_sj_new';
    protected $primaryKey = 'id';

    protected $fillable = [
        'doc_number',
        'kontrabon',
        'date',
        'name',
        'npwp',
        'total',
        'customer_id',
        'Valuta',
        'type',
        'id',
        'currency',
    ];

    protected $casts = [
        'date' => 'date',
        'total' => 'double',
    ];

    // Disable timestamps karena view tidak memiliki created_at dan updated_at
    public $timestamps = false;

    /**
     * Scope untuk mencari data berdasarkan rentang tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope untuk mencari data berdasarkan nilai
     */
    public function scopeByValue($query, $value)
    {
        return $query->where('total', $value);
    }

    /**
     * Scope untuk data yang belum dimatch
     * Note: Removed cross-database query to avoid connection issues
     * Filtering will be handled in the controller instead
     */
    public function scopeUnmatched($query)
    {
        // For now, return all records since we can't easily check against auto_matches
        // The filtering will be handled in the controller
        return $query;
    }

    /**
     * Get the document number
     */
    public function getDocNumber()
    {
        return $this->attributes['doc_number'] ?? null;
    }

    /**
     * Get the date value
     */
    public function getDateValue()
    {
        return $this->attributes['date'] ?? null;
    }

    /**
     * Get the total value
     */
    public function getTotalValue()
    {
        return $this->attributes['total'] ?? null;
    }

    /**
     * Get the customer name
     */
    public function getCustomerName()
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Get the kontrabon value
     */
    public function getKontrabonValue()
    {
        return $this->attributes['kontrabon'] ?? null;
    }

    /**
     * Get the currency
     */
    public function getCurrency()
    {
        return $this->attributes['currency'] ?? null;
    }
}
