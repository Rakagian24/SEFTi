<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\BankMasuk;
use App\Models\MemoPembayaran;
use App\Models\PaymentVoucher;
use Carbon\Carbon;

class DocumentNumberService
{
    // Document type mappings
    const DOCUMENT_TYPES = [
        'PO' => 'Purchase Order',
        'MP' => 'Memo Pembayaran',
        'BPB' => 'Bukti Penerimaan Barang',
        'PV' => 'Payment Voucher',
        'RLS' => 'Realisasi',
        'BM' => 'Bank Masuk',
        'BK' => 'Bank Keluar',
        'REF' => 'Termin'
    ];

    // Document type mappings (reverse)
    const DOCUMENT_NAMES = [
        'Purchase Order' => 'PO',
        'Memo Pembayaran' => 'MP',
        'Bukti Penerimaan Barang' => 'BPB',
        'Payment Voucher' => 'PV',
        'Realisasi' => 'RLS',
        'Bank Masuk' => 'BM',
        'Bank Keluar' => 'BK',
        'Termin' => 'REF'
    ];

    // Tipe mappings
    const TIPE_MAPPINGS = [
        'Reguler' => 'REG',
        'Anggaran' => 'AGR',
        'Lainnya' => 'ETC'
    ];

    // Documents that don't have tipe (format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT)
    const DOCUMENTS_WITHOUT_TIPE = ['MP', 'BPB', 'RLS', 'REF'];



    /**
     * Generate unique document number based on format
     * For PO: DOKUMEN/TIPE/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
     * For others: DOKUMEN/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
     */
    public static function generateNumber(string $documentType, ?string $tipe, int $departmentId, string $departmentAlias): string
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number
        $nextSequence = self::getNextSequence($documentType, $tipe, $departmentId, $bulan, $tahun);

        // Format based on document type
        $dokumen = self::getDocumentCode($documentType);

        if (in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
            if ($tipe === null) {
                // Handle case where tipe is null (e.g., Bank Masuk)
                $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
            }
            $tipeCode = self::getTipeCode($tipe);
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$tipeCode}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        }
    }

    /**
     * Generate unique document number based on a specific document date
     * Month/Year will be derived from the provided $documentDate
     */
    public static function generateNumberForDate(string $documentType, ?string $tipe, int $departmentId, string $departmentAlias, Carbon $documentDate): string
    {
        $bulan = $documentDate->month;
        $tahun = $documentDate->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number
        $nextSequence = self::getNextSequence($documentType, $tipe, $departmentId, $bulan, $tahun);

        // Format based on document type
        $dokumen = self::getDocumentCode($documentType);

        if (in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE)) {
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        } else {
            if ($tipe === null) {
                $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
            }
            $tipeCode = self::getTipeCode($tipe);
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$tipeCode}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        }
    }

    /**
     * Generate preview number for draft documents (exclude draft from sequence)
     * This is used for frontend preview before saving
     */
    public static function generatePreviewNumber(string $documentType, ?string $tipe, int $departmentId, string $departmentAlias): string
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number (exclude draft documents)
        $nextSequence = self::getNextSequenceExcludeDraft($documentType, $tipe, $departmentId, $bulan, $tahun);

        // Format based on document type
        $dokumen = self::getDocumentCode($documentType);

        if (in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
            if ($tipe === null) {
                // Handle case where tipe is null (e.g., Bank Masuk)
                $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
            }
            $tipeCode = self::getTipeCode($tipe);
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$tipeCode}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        }
    }

    /**
     * Generate number for date with exclude ID (for update operations)
     */
    public static function generateNumberForDateWithExclude(string $documentType, ?string $tipe, int $departmentId, string $departmentAlias, Carbon $documentDate, int $excludeId): string
    {
        $bulan = $documentDate->month;
        $tahun = $documentDate->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number excluding specific ID
        $nextSequence = self::getNextSequenceWithExclude($documentType, $tipe, $departmentId, $bulan, $tahun, $excludeId);

        // Format based on document type
        $dokumen = self::getDocumentCode($documentType);

        if (in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE)) {
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        } else {
            if ($tipe === null) {
                $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
            }
            $tipeCode = self::getTipeCode($tipe);
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$tipeCode}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        }
    }

    /**
     * Generate preview number for a specific document date
     */
    public static function generatePreviewNumberForDate(string $documentType, ?string $tipe, int $departmentId, string $departmentAlias, Carbon $documentDate): string
    {
        $bulan = $documentDate->month;
        $tahun = $documentDate->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number (exclude draft documents)
        $nextSequence = self::getNextSequenceExcludeDraft($documentType, $tipe, $departmentId, $bulan, $tahun);

        // Format based on document type
        $dokumen = self::getDocumentCode($documentType);

        if (in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE)) {
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        } else {
            if ($tipe === null) {
                $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
            }
            $tipeCode = self::getTipeCode($tipe);
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$tipeCode}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        }
    }

    /**
     * Generate preview number for draft documents including current draft
     * This is used for form preview to show what number will be assigned
     */
    public static function generateFormPreviewNumber(string $documentType, ?string $tipe, int $departmentId, string $departmentAlias): string
    {
        $now = Carbon::now();
        $bulan = $now->month;
        $tahun = $now->year;

        // Convert month to Roman numeral
        $bulanRomawi = self::numberToRoman($bulan);

        // Get next sequence number including current draft
        $nextSequence = self::getNextSequenceForForm($documentType, $tipe, $departmentId, $bulan, $tahun);

        // Format based on document type
        $dokumen = self::getDocumentCode($documentType);

        // Debug logging
        \Illuminate\Support\Facades\Log::info('DocumentNumberService - generateFormPreviewNumber', [
            'documentType' => $documentType,
            'tipe' => $tipe,
            'departmentId' => $departmentId,
            'departmentAlias' => $departmentAlias,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'nextSequence' => $nextSequence,
            'dokumen' => $dokumen,
            'isWithoutTipe' => in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE),
            'isWithoutDepartment' => ($dokumen === 'PO' && $tipe === 'Lainnya')
        ]);

        if (in_array($dokumen, self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT_ALIAS/BULAN_ROMWI/TAHUN/NOMOR_URUT
            if ($tipe === null) {
                // Handle case where tipe is null (e.g., Bank Masuk)
                $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
                return "{$dokumen}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
            }
            $tipeCode = self::getTipeCode($tipe);
            $nomorUrut = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            return "{$dokumen}/{$tipeCode}/{$departmentAlias}/{$bulanRomawi}/{$tahun}/{$nomorUrut}";
        }
    }

    /**
     * Get document code from document type
     */
    public static function getDocumentCode(string $documentType): string
    {
        return self::DOCUMENT_NAMES[$documentType] ?? $documentType;
    }

    /**
     * Get tipe code from tipe name
     */
    public static function getTipeCode(string $tipe): string
    {
        return self::TIPE_MAPPINGS[$tipe] ?? $tipe;
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
     * Get next sequence number for specific document type, department, month, and year
     * Special handling for PO: Anggaran has separate sequence from Reguler/Lainnya
     */
    private static function getNextSequence(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun): int
    {
        // Get the last document number for this document type, department, month, and year
        $lastDocument = self::getLastDocument($documentType, $tipe, $departmentId, $bulan, $tahun);

        if (!$lastDocument) {
            return 1; // First document for this type/department/month/year
        }

        // Extract sequence number from last document number
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? $lastDocument->no_mb ?? $lastDocument->no_pv ?? null;

        if (!$documentNumber) {
            return 1; // Fallback if no document number
        }

        $parts = explode('/', $documentNumber);

        if (in_array(self::getDocumentCode($documentType), self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (5 parts)
            if (count($parts) === 5) {
                $lastSequence = (int) $parts[4];
                return $lastSequence + 1;
            }
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (6 parts)
            if (count($parts) === 6) {
                $lastSequence = (int) $parts[5];
                return $lastSequence + 1;
            }
        }

        return 1; // Fallback if format is unexpected
    }

    /**
     * Get next sequence number excluding draft documents (for preview)
     * This is used when generating preview numbers
     */
    private static function getNextSequenceExcludeDraft(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun): int
    {
        // Get the last document number for this document type, department, month, and year
        $lastDocument = self::getLastDocumentExcludeDraft($documentType, $tipe, $departmentId, $bulan, $tahun);

        if (!$lastDocument) {
            return 1; // First document for this type/department/month/year
        }

        // Extract sequence number from last document number
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? $lastDocument->no_mb ?? $lastDocument->no_pv ?? null;

        if (!$documentNumber) {
            return 1; // Fallback if no document number
        }

        $parts = explode('/', $documentNumber);

        if (in_array(self::getDocumentCode($documentType), self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (5 parts)
            if (count($parts) === 5) {
                $lastSequence = (int) $parts[4];
                return $lastSequence + 1;
            }
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (6 parts)
            if (count($parts) === 6) {
                $lastSequence = (int) $parts[5];
                return $lastSequence + 1;
            }
        }

        return 1; // Fallback if format is unexpected
    }

    /**
     * Get next sequence number excluding specific ID (for update operations)
     */
    private static function getNextSequenceWithExclude(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun, int $excludeId): int
    {
        // Get the last document number for this document type, department, month, and year (excluding specific ID)
        $lastDocument = self::getLastDocumentWithExclude($documentType, $tipe, $departmentId, $bulan, $tahun, $excludeId);

        if (!$lastDocument) {
            return 1; // First document for this type/department/month/year
        }

        // Extract sequence number from last document number
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? $lastDocument->no_mb ?? $lastDocument->no_pv ?? null;

        if (!$documentNumber) {
            return 1; // Fallback if no document number
        }

        $parts = explode('/', $documentNumber);

        if (in_array(self::getDocumentCode($documentType), self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (5 parts)
            if (count($parts) === 5) {
                $lastSequence = (int) $parts[4];
                return $lastSequence + 1;
            }
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (6 parts)
            if (count($parts) === 6) {
                $lastSequence = (int) $parts[5];
                return $lastSequence + 1;
            }
        }

        return 1; // Fallback if format is unexpected
    }

    /**
     * Get next sequence number including current draft for form preview
     */
    private static function getNextSequenceForForm(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun): int
    {
        // Get the last document number for this document type, department, month, and year
        $lastDocument = self::getLastDocumentExcludeDraft($documentType, $tipe, $departmentId, $bulan, $tahun);

        // Debug logging
        \Illuminate\Support\Facades\Log::info('DocumentNumberService - getNextSequenceForForm', [
            'documentType' => $documentType,
            'tipe' => $tipe,
            'departmentId' => $departmentId,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'lastDocument' => $lastDocument ? $lastDocument->toArray() : null
        ]);

        if (!$lastDocument) {
            \Illuminate\Support\Facades\Log::info('DocumentNumberService - No last document found, returning sequence 1');
            return 1; // First document for this type/department/month/year
        }

        // Extract sequence number from last document number
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? $lastDocument->no_mb ?? $lastDocument->no_referensi ?? $lastDocument->no_pv ?? null;

        if (!$documentNumber) {
            \Illuminate\Support\Facades\Log::info('DocumentNumberService - No document number found, returning sequence 1');
            return 1; // Fallback if no document number
        }

        $parts = explode('/', $documentNumber);
        \Illuminate\Support\Facades\Log::info('DocumentNumberService - Parsed document parts', [
            'documentNumber' => $documentNumber,
            'parts' => $parts,
            'partsCount' => count($parts)
        ]);

        if (in_array(self::getDocumentCode($documentType), self::DOCUMENTS_WITHOUT_TIPE)) {
            // Format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (5 parts)
            if (count($parts) === 5) {
                $lastSequence = (int) $parts[4];
                \Illuminate\Support\Facades\Log::info('DocumentNumberService - Found sequence in parts[4]', ['lastSequence' => $lastSequence]);
                return $lastSequence + 1;
            }
        } else {
            // Format: DOKUMEN/TIPE/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (6 parts)
            if (count($parts) === 6) {
                $lastSequence = (int) $parts[5];
                \Illuminate\Support\Facades\Log::info('DocumentNumberService - Found sequence in parts[5]', ['lastSequence' => $lastSequence]);
                return $lastSequence + 1;
            }
        }

        \Illuminate\Support\Facades\Log::info('DocumentNumberService - Unexpected format, returning sequence 1');

        // Fallback: use timestamp-based sequence for unexpected formats
        $timestamp = time();
        $fallbackSequence = ($timestamp % 9999) + 1; // Generate sequence between 1-9999

        \Illuminate\Support\Facades\Log::info('DocumentNumberService - Using fallback timestamp sequence', ['fallbackSequence' => $fallbackSequence]);

        return $fallbackSequence;
    }

    /**
     * Get last document based on document type
     * Special handling for PO: Anggaran has separate sequence
     */
    private static function getLastDocument(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun)
    {
        switch ($documentType) {
            case 'Purchase Order':
                // For PO, Anggaran has its own sequence. Reguler and Lainnya share the same sequence per department/month/year.
                // Include soft deleted records for sequence generation to avoid number conflicts
                if ($tipe === 'Anggaran') {
                    return PurchaseOrder::withTrashed()
                        ->where('department_id', $departmentId)
                        ->where('tipe_po', 'Anggaran')
                        ->whereNotNull('no_po')
                        ->whereYear('created_at', $tahun)
                        ->whereMonth('created_at', $bulan)
                        // Order by numeric suffix of no_po to get the true highest sequence
                        ->orderByRaw("CAST(SUBSTRING_INDEX(no_po, '/', -1) AS UNSIGNED) DESC")
                        ->first();
                }

                // Shared pool for Reguler and Lainnya
                // Include soft deleted records for sequence generation to avoid number conflicts
                return PurchaseOrder::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereIn('tipe_po', ['Reguler', 'Lainnya'])
                    ->whereNotNull('no_po')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    // Order by numeric suffix of no_po to get the true highest sequence
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_po, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            case 'Bank Masuk':
                // For Bank Masuk, sequence follows the document date (tanggal)
                // Include soft deleted records for sequence generation to avoid number conflicts
                return BankMasuk::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereNotNull('no_bm')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Memo Pembayaran':
            case 'MP':
                // For Memo Pembayaran, sequence follows the document date (tanggal)
                // Include soft deleted records for sequence generation to avoid number conflicts
                return MemoPembayaran::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereNotNull('no_mb')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    // Order by numeric suffix of no_mb
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_mb, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            case 'Termin':
            case 'REF':
                // For Termin, sequence follows the created_at date
                // Include soft deleted records for sequence generation to avoid number conflicts
                return \App\Models\Termin::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereNotNull('no_referensi')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Payment Voucher':
            case 'PV':
                // PV follows document date (tanggal)
                return PaymentVoucher::withTrashed()
                    ->where('department_id', $departmentId)
                    ->when($tipe, function ($q) use ($tipe) {
                        $q->where('tipe_pv', $tipe);
                    })
                    ->whereNotNull('no_pv')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    // Order by numeric suffix of no_pv
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_pv, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            // Add other document types here as they are implemented
            // case 'Bukti Penerimaan Barang':
            // case 'Realisasi':
            // case 'Bank Keluar':

            default:
                return null;
        }
    }

    /**
     * Get last document excluding draft documents (for preview)
     * This is used when generating preview numbers
     */
    private static function getLastDocumentExcludeDraft(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun)
    {
        switch ($documentType) {
            case 'Purchase Order':
                // For PO, Anggaran has its own sequence. Reguler and Lainnya share the same sequence per department/month/year.
                // Include soft deleted records for sequence generation to avoid number conflicts
                if ($tipe === 'Anggaran') {
                    return PurchaseOrder::withTrashed()
                        ->where('department_id', $departmentId)
                        ->where('tipe_po', 'Anggaran')
                        ->where('status', '!=', 'Draft')
                        ->whereYear('created_at', $tahun)
                        ->whereMonth('created_at', $bulan)
                        // Order by numeric suffix of no_po
                        ->orderByRaw("CAST(SUBSTRING_INDEX(no_po, '/', -1) AS UNSIGNED) DESC")
                        ->first();
                }

                // Shared pool for Reguler and Lainnya, excluding drafts
                // Include soft deleted records for sequence generation to avoid number conflicts
                return PurchaseOrder::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereIn('tipe_po', ['Reguler', 'Lainnya'])
                    ->where('status', '!=', 'Draft')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    // Order by numeric suffix of no_po
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_po, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            case 'Bank Masuk':
                // For Bank Masuk preview, also follow document date (tanggal)
                // Include soft deleted records for sequence generation to avoid number conflicts
                return BankMasuk::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereNotNull('no_bm')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Memo Pembayaran':
            case 'MP':
                // Exclude drafts (which typically have null no_mb) and follow document date
                // Include soft deleted records for sequence generation to avoid number conflicts
                return MemoPembayaran::withTrashed()
                    ->where('department_id', $departmentId)
                    ->where('status', '!=', 'Draft')
                    ->whereNotNull('no_mb')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    // Order by numeric suffix of no_mb
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_mb, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            case 'Termin':
            case 'REF':
                // For Termin preview, follow created_at date and exclude inactive status
                // Include soft deleted records for sequence generation to avoid number conflicts
                // But exclude them from active sequence by filtering status
                return \App\Models\Termin::withTrashed()
                    ->where('department_id', $departmentId)
                    ->where('status', 'active')
                    ->whereNotNull('no_referensi')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Payment Voucher':
            case 'PV':
                return PaymentVoucher::withTrashed()
                    ->where('department_id', $departmentId)
                    ->when($tipe, function ($q) use ($tipe) {
                        $q->where('tipe_pv', $tipe);
                    })
                    ->where('status', '!=', 'Draft')
                    ->whereNotNull('no_pv')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    // Order by numeric suffix of no_pv
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_pv, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            // Add other document types here as they are implemented

            default:
                return null;
        }
    }

    /**
     * Get last document excluding specific ID (for update operations)
     */
    private static function getLastDocumentWithExclude(string $documentType, ?string $tipe, int $departmentId, int $bulan, int $tahun, int $excludeId)
    {
        switch ($documentType) {
            case 'Purchase Order':
                if ($tipe === 'Anggaran') {
                    return PurchaseOrder::withTrashed()
                        ->where('department_id', $departmentId)
                        ->where('tipe_po', 'Anggaran')
                        ->where('id', '!=', $excludeId)
                        ->whereNotNull('no_po')
                        ->whereYear('created_at', $tahun)
                        ->whereMonth('created_at', $bulan)
                        // Order by numeric suffix of no_po
                        ->orderByRaw("CAST(SUBSTRING_INDEX(no_po, '/', -1) AS UNSIGNED) DESC")
                        ->first();
                }

                return PurchaseOrder::withTrashed()
                    ->where('department_id', $departmentId)
                    ->whereIn('tipe_po', ['Reguler', 'Lainnya'])
                    ->where('id', '!=', $excludeId)
                    ->whereNotNull('no_po')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    // Order by numeric suffix of no_po
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_po, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            case 'Bank Masuk':
                return BankMasuk::withTrashed()
                    ->where('department_id', $departmentId)
                    ->where('id', '!=', $excludeId)
                    ->whereNotNull('no_bm')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Memo Pembayaran':
            case 'MP':
                return MemoPembayaran::withTrashed()
                    ->where('department_id', $departmentId)
                    ->where('id', '!=', $excludeId)
                    ->whereNotNull('no_mb')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    // Order by numeric suffix of no_mb
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_mb, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            case 'Termin':
            case 'REF':
                return \App\Models\Termin::withTrashed()
                    ->where('department_id', $departmentId)
                    ->where('id', '!=', $excludeId)
                    ->whereNotNull('no_referensi')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Payment Voucher':
            case 'PV':
                return PaymentVoucher::withTrashed()
                    ->where('department_id', $departmentId)
                    ->when($tipe, function ($q) use ($tipe) {
                        $q->where('tipe_pv', $tipe);
                    })
                    ->where('id', '!=', $excludeId)
                    ->whereNotNull('no_pv')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    // Order by numeric suffix of no_pv
                    ->orderByRaw("CAST(SUBSTRING_INDEX(no_pv, '/', -1) AS UNSIGNED) DESC")
                    ->first();

            default:
                return null;
        }
    }

    /**
     * Validate if document number already exists (exclude current document for edit)
     */
    public static function isNumberUnique(string $documentNumber, ?int $excludeId = null, string $documentType = ''): bool
    {
        // Check in all relevant tables
        $parts = explode('/', $documentNumber);
        $documentCode = $parts[0] ?? '';

        switch ($documentCode) {
            case 'PO':
                // Include soft deleted records for uniqueness check to prevent number reuse
                $query = PurchaseOrder::withTrashed()->where('no_po', $documentNumber);
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                return !$query->exists();

            case 'BM':
                // Include soft deleted records for uniqueness check to prevent number reuse
                $query = BankMasuk::withTrashed()->where('no_bm', $documentNumber);
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                return !$query->exists();

            case 'MP':
                // Include soft deleted records for uniqueness check to prevent number reuse
                $query = MemoPembayaran::withTrashed()->where('no_mb', $documentNumber);
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                return !$query->exists();

            case 'REF':
                // For Termin, check uniqueness including soft deleted records
                // This prevents reuse of numbers from soft deleted records
                $query = \App\Models\Termin::withTrashed()->where('no_referensi', $documentNumber);
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                return !$query->exists();

            // Add other document types here as they are implemented

            default:
                return true; // Unknown document type, assume unique
        }
    }

    /**
     * Parse document number to extract components
     */
    public static function parseDocumentNumber(string $documentNumber): array
    {
        $parts = explode('/', $documentNumber);

        if (count($parts) === 5) {
            // Check if this is a PO document without department (PO/TIPE/BULAN/TAHUN/NOMOR_URUT)
            if ($parts[0] === 'PO' && in_array($parts[1], ['REG', 'AGR', 'ETC'])) {
                return [
                    'document_code' => $parts[0],
                    'tipe_code' => $parts[1],
                    'department_alias' => null,
                    'bulan_romawi' => $parts[2],
                    'tahun' => $parts[3],
                    'nomor_urut' => $parts[4]
                ];
            }

            // Format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (no tipe)
            return [
                'document_code' => $parts[0],
                'tipe_code' => null,
                'department_alias' => $parts[1],
                'bulan_romawi' => $parts[2],
                'tahun' => $parts[3],
                'nomor_urut' => $parts[4]
            ];
        } elseif (count($parts) === 6) {
            // Format: DOKUMEN/TIPE/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT (with tipe)
            return [
                'document_code' => $parts[0],
                'tipe_code' => $parts[1],
                'department_alias' => $parts[2],
                'bulan_romawi' => $parts[3],
                'tahun' => $parts[4],
                'nomor_urut' => $parts[5]
            ];
        }

        return [];
    }

    /**
     * Get document type name from document code
     */
    public static function getDocumentTypeName(string $documentCode): string
    {
        return self::DOCUMENT_TYPES[$documentCode] ?? $documentCode;
    }

    /**
     * Get tipe name from tipe code
     */
    public static function getTipeName(string $tipeCode): string
    {
        $reverseMappings = array_flip(self::TIPE_MAPPINGS);
        return $reverseMappings[$tipeCode] ?? $tipeCode;
    }

    /**
     * Check if document type has tipe field
     */
    public static function hasTipe(string $documentType): bool
    {
        $documentCode = self::getDocumentCode($documentType);
        return !in_array($documentCode, self::DOCUMENTS_WITHOUT_TIPE);
    }

    /**
     * Check if document type has department field
     */
    public static function hasDepartment(string $documentType): bool
    {
        $documentCode = self::getDocumentCode($documentType);
        // PO now always includes department (including Lainnya)
        return true;
    }

    /**
     * Get document format description for UI display
     */
    public static function getDocumentFormatDescription(string $documentType, ?string $tipe = null): string
    {
        $documentCode = self::getDocumentCode($documentType);

        if (in_array($documentCode, self::DOCUMENTS_WITHOUT_TIPE)) {
            return "Format: {$documentCode}/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT";
        } elseif ($documentCode === 'PO' && $tipe === 'Lainnya') {
            // PO Lainnya now includes department
            $tipeCode = self::getTipeCode($tipe);
            return "Format: {$documentCode}/{$tipeCode}/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT";
        } else {
            if ($tipe) {
                $tipeCode = self::getTipeCode($tipe);
                return "Format: {$documentCode}/{$tipeCode}/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT";
            }
            return "Format: {$documentCode}/TIPE/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT";
        }
    }
}
