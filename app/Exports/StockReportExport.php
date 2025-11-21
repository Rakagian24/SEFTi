<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return [
            'Nama Barang',
            'Jenis',
            'Satuan',
            'Stock',
        ];
    }

    public function map($row): array
    {
        return [
            $row['nama_barang'] ?? '',
            $row['jenis'] ?? '',
            $row['satuan'] ?? '',
            (float)($row['stock'] ?? 0),
        ];
    }
}
