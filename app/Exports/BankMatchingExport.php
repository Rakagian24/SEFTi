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
            $item['cabang'] ?? '-',
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
            'Cabang',
            'Status Match'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0.00', // Format kolom Nilai Invoice dengan 2 digit desimal
        ];
    }
}
