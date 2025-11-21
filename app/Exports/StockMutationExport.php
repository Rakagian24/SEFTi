<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockMutationExport implements FromCollection, WithHeadings, WithMapping
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
            'Saldo Awal',
            'Masuk',
            'Keluar',
            'Saldo Akhir',
        ];
    }

    public function map($row): array
    {
        return [
            $row['nama_barang'] ?? '',
            $row['jenis'] ?? '',
            (float)($row['saldo_awal'] ?? 0),
            (float)($row['masuk'] ?? 0),
            (float)($row['keluar'] ?? 0),
            (float)($row['saldo_akhir'] ?? 0),
        ];
    }
}
