<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NirwanaInvoice extends Model
{
    protected $connection = 'pgsql_nirwana';
    protected $table = 'tpfaktur';
    protected $primaryKey = 'faktur_id';

    protected $fillable = [
        'faktur_id',
        'cabang_id',
        'tanggal',
        'konsumen_id',
        'amount',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'amount' => 'decimal:2',
    ];

    // Disable timestamps karena tabel tidak memiliki created_at dan updated_at
    public $timestamps = false;

    /**
     * Scope untuk mencari data berdasarkan rentang tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope untuk mencari data berdasarkan nilai
     */
    public function scopeByAmount($query, $amount)
    {
        return $query->where('amount', $amount);
    }

    /**
     * Scope untuk mencari data berdasarkan cabang
     */
    public function scopeByCabang($query, $cabangId)
    {
        return $query->where('cabang_id', $cabangId);
    }

    /**
     * Scope untuk mencari data berdasarkan konsumen
     */
    public function scopeByKonsumen($query, $konsumenId)
    {
        return $query->where('konsumen_id', $konsumenId);
    }

    /**
     * Get invoice data with customer name and payment amount
     * This method executes the complex query to get invoice data with customer details and payment amounts
     */
    public static function getInvoiceData($startDate = null, $endDate = null)
    {
        $query = "
            SELECT
                f.faktur_id,
                p.nama AS nama_customer,
                f.cabang_id AS cabang,
                COALESCE(SUM(pay.amount), 0) AS nominal,
                f.tanggal
            FROM tpfaktur f
            LEFT JOIN trpelanggan p ON f.konsumen_id = p.pel_id
            LEFT JOIN tpfaktur_pay pay ON f.faktur_id = pay.faktur_id
        ";

        $params = [];
        $whereConditions = [];

        if ($startDate && $endDate) {
            $whereConditions[] = "f.tanggal BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $query .= " GROUP BY f.faktur_id, p.nama, f.cabang_id, f.tanggal ORDER BY f.tanggal DESC";

        return DB::connection('pgsql_nirwana')->select($query, $params);
    }

    /**
     * Get the invoice ID
     */
    public function getInvoiceId()
    {
        return $this->attributes['faktur_id'] ?? null;
    }

    /**
     * Get the date value
     */
    public function getDateValue()
    {
        return $this->attributes['tanggal'] ?? null;
    }

    /**
     * Get the amount value
     */
    public function getAmountValue()
    {
        return $this->attributes['nominal'] ?? 0;
    }

    /**
     * Get the customer name
     */
    public function getCustomerName()
    {
        return $this->attributes['nama_customer'] ?? null;
    }

    /**
     * Get the branch ID
     */
    public function getCabangId()
    {
        return $this->attributes['cabang'] ?? null;
    }

    /**
     * Get the customer ID
     */
    public function getKonsumenId()
    {
        return $this->attributes['konsumen_id'] ?? null;
    }
}
