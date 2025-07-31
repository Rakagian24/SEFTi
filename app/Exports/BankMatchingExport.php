<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BankMatchingExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function map($item): array
    {
        return [
            $item['no_invoice'] ?? '-',
            $item['tanggal_invoice'] ?? '-',
            $item['nilai_invoice'] ?? 0,
            $item['customer_name'] ?? '-',
            $item['kontrabon'] ?? '-',
            $item['currency'] ?? '-',
            $item['status_match'] ?? 'Belum Dimatch'
        ];
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Tanggal Invoice',
            'Nilai Invoice',
            'Customer Name',
            'Kontrabon',
            'Currency',
            'Status Match'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0.00000', // Format kolom Nilai Invoice sebagai currency IDR dengan maksimal 5 digit desimal
        ];
    }
}
