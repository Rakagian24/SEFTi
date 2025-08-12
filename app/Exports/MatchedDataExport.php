<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class MatchedDataExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
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
            $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '-',
            $item->invoice_customer_name ?? '-',
            $item->department_name ?? '-',
            $item->sj_no ?? '-',
            $item->sj_tanggal ? \Carbon\Carbon::parse($item->sj_tanggal)->format('d/m/Y') : '-',
            $item->sj_nilai ?? 0,
            $item->bm_no ?? '-',
            $item->bm_tanggal ? \Carbon\Carbon::parse($item->bm_tanggal)->format('d/m/Y') : '-',
            $item->bm_nilai ?? 0,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal Dibuat',
            'Customer',
            'Departemen',
            'No Invoice',
            'Tanggal Invoice',
            'Nilai Invoice',
            'No Bank Masuk',
            'Tanggal Match',
            'Nilai Bank Masuk',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0.00', // Format kolom Nilai Invoice dengan 2 digit desimal
            'I' => '#,##0.00', // Format kolom Nilai Bank Masuk dengan 2 digit desimal
        ];
    }
}
