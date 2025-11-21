<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockCardExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return new Collection($this->rows);
    }

    public function headings(): array
    {
        return [
            'Referensi',
            'Tanggal',
            'Masuk',
            'Keluar',
            'Saldo',
        ];
    }

    public function map($row): array
    {
        return [
            $row['referensi'] ?? '',
            $row['tanggal'] ?? '',
            (float)($row['masuk'] ?? 0),
            (float)($row['keluar'] ?? 0),
            (float)($row['saldo'] ?? 0),
        ];
    }
}
