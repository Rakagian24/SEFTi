<?php

namespace App\Exports;

use App\Models\BankMasuk;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BankMasukExport implements FromCollection, WithHeadings, WithMapping
{
    protected $ids;
    protected $fields;

    public function __construct($ids, $fields)
    {
        $this->ids = $ids;
        $this->fields = $fields;
    }

    public function collection()
    {
        return BankMasuk::with(['bankAccount', 'creator', 'updater', 'arPartner'])
            ->whereIn('id', $this->ids)
            ->get();
    }

    public function headings(): array
    {
        return $this->fields;
    }

    public function map($row): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            switch ($field) {
                case 'bank_account':
                    $data[] = $row->bankAccount ? $row->bankAccount->nama_pemilik : '';
                    break;
                case 'no_rekening':
                    $data[] = $row->bankAccount ? $row->bankAccount->no_rekening : '';
                    break;
                case 'customer':
                    $data[] = $row->arPartner ? $row->arPartner->nama_ap : '';
                    break;
                case 'created_by':
                    $data[] = $row->creator ? $row->creator->name : '';
                    break;
                case 'updated_by':
                    $data[] = $row->updater ? $row->updater->name : '';
                    break;
                case 'tanggal':
                    $data[] = $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '';
                    break;
                case 'nilai':
                    $data[] = number_format($row->nilai, 0, ',', '.');
                    break;
                default:
                    $data[] = $row->{$field} ?? '';
            }
        }
        return $data;
    }
}
