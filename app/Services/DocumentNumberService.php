<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\BankMasuk;
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
        'BK' => 'Bank Keluar'
    ];

    // Document type mappings (reverse)
    const DOCUMENT_NAMES = [
        'Purchase Order' => 'PO',
        'Memo Pembayaran' => 'MP',
        'Bukti Penerimaan Barang' => 'BPB',
        'Payment Voucher' => 'PV',
        'Realisasi' => 'RLS',
        'Bank Masuk' => 'BM',
        'Bank Keluar' => 'BK'
    ];

    // Tipe mappings
    const TIPE_MAPPINGS = [
        'Reguler' => 'REG',
        'Anggaran' => 'AGR',
        'Lainnya' => 'ETC'
    ];

    // Documents that don't have tipe (format: DOKUMEN/DEPARTMENT/BULAN/TAHUN/NOMOR_URUT)
    const DOCUMENTS_WITHOUT_TIPE = ['MP', 'BPB', 'RLS'];



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
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? null;

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
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? null;

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
        $documentNumber = $lastDocument->no_po ?? $lastDocument->no_bm ?? null;

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
                if ($tipe === 'Anggaran') {
                    return PurchaseOrder::where('department_id', $departmentId)
                        ->where('tipe_po', 'Anggaran')
                        ->whereNotNull('no_po')
                        ->whereYear('created_at', $tahun)
                        ->whereMonth('created_at', $bulan)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // Shared pool for Reguler and Lainnya
                return PurchaseOrder::where('department_id', $departmentId)
                    ->whereIn('tipe_po', ['Reguler', 'Lainnya'])
                    ->whereNotNull('no_po')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Bank Masuk':
                // For Bank Masuk, sequence follows the document date (tanggal)
                return BankMasuk::where('department_id', $departmentId)
                    ->whereNotNull('no_bm')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            // Add other document types here as they are implemented
            // case 'Memo Pembayaran':
            // case 'Bukti Penerimaan Barang':
            // case 'Payment Voucher':
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
                if ($tipe === 'Anggaran') {
                    return PurchaseOrder::where('department_id', $departmentId)
                        ->where('tipe_po', 'Anggaran')
                        ->where('status', '!=', 'Draft')
                        ->whereYear('created_at', $tahun)
                        ->whereMonth('created_at', $bulan)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // Shared pool for Reguler and Lainnya, excluding drafts
                return PurchaseOrder::where('department_id', $departmentId)
                    ->whereIn('tipe_po', ['Reguler', 'Lainnya'])
                    ->where('status', '!=', 'Draft')
                    ->whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            case 'Bank Masuk':
                // For Bank Masuk preview, also follow document date (tanggal)
                return BankMasuk::where('department_id', $departmentId)
                    ->whereNotNull('no_bm')
                    ->whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $bulan)
                    ->orderBy('id', 'desc')
                    ->first();

            // Add other document types here as they are implemented

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
                $query = PurchaseOrder::where('no_po', $documentNumber);
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                return !$query->exists();

            case 'BM':
                $query = BankMasuk::where('no_bm', $documentNumber);
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
