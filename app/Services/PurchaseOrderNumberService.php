<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Carbon\Carbon;

class PurchaseOrderNumberService
{
    /**
     * Generate unique PO number based on format: PO/TIPE/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
     */
    public static function generateNumber(string $tipePo, int $departmentId, string $departmentAlias): string
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number for this department, month, and year
        $nextSequence = self::getNextSequence($departmentId, $bulan, $tahun);

        // Format: PO/TIPE/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
        $tipe = $tipePo === 'Reguler' ? 'REG' : 'ETC';
        $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "PO/{$tipe}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
    }

    /**
     * Convert number to Roman numeral
     */
    private static function numberToRoman(int $number): string
    {
        $romans = [
            1000 => 'M',
            900 => 'CM',
            500 => 'D',
            400 => 'CD',
            100 => 'C',
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I'
        ];

        $result = '';
        foreach ($romans as $value => $roman) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }

    /**
     * Get next sequence number for specific department, month, and year
     */
    private static function getNextSequence(int $departmentId, int $bulan, int $tahun): int
    {
        // Get the last PO number for this department, month, and year
        $lastPo = PurchaseOrder::where('department_id', $departmentId)
            ->whereYear('created_at', $tahun)
            ->whereMonth('created_at', $bulan)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastPo) {
            return 1; // First PO for this department/month/year
        }

        // Extract sequence number from last PO number
        $parts = explode('/', $lastPo->no_po);
        if (count($parts) === 6) {
            $lastSequence = (int) $parts[5];
            return $lastSequence + 1;
        }

        return 1; // Fallback if format is unexpected
    }

    /**
     * Validate if PO number already exists
     */
    public static function isNumberUnique(string $poNumber): bool
    {
        return !PurchaseOrder::where('no_po', $poNumber)->exists();
    }
}
