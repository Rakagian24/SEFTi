<?php

namespace App\Exports;

use App\Models\AutoMatch;
use App\Models\BankMasuk;
use App\Models\Kwitansi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AutoMatchExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        // Ambil data kwitansi yang belum dimatch
        $kwitansiList = Kwitansi::byDateRange($this->startDate, $this->endDate)
            ->unmatched()
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        // Ambil data bank masuk yang belum dimatch (hanya Penjualan Toko, gunakan match_date)
        $bankMasukList = BankMasuk::where('status', 'aktif')
            ->where('terima_dari', 'Penjualan Toko')
            ->whereBetween('match_date', [$this->startDate, $this->endDate])
            ->orderBy('match_date')
            ->orderBy('created_at')
            ->get();

        // Ambil data yang sudah dimatch (gunakan created_at sebagai tanggal match)
        $existingMatches = AutoMatch::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['bankMasuk', 'creator'])
            ->get();

        $collection = collect();

        // Tambahkan data kwitansi yang belum dimatch
        foreach ($kwitansiList as $kwitansi) {
            $collection->push([
                'type' => 'kwitansi',
                'no_dokumen' => $kwitansi->KWITANSI_ID ?? '-',
                'tanggal' => $kwitansi->TANGGAL ? $kwitansi->TANGGAL->format('d/m/Y') : '-',
                'nilai' => $kwitansi->NILAI ?? 0,
                'keterangan' => $kwitansi->KETERANGAN ?? '-',
                'status' => 'Belum Dimatch',
                'match_with' => '-',
                'match_date' => '-',
                'created_by' => '-',
            ]);
        }

        // Tambahkan data bank masuk yang belum dimatch
        foreach ($bankMasukList as $bankMasuk) {
            $collection->push([
                'type' => 'bank_masuk',
                'no_dokumen' => $bankMasuk->no_bm ?? '-',
                'tanggal' => $bankMasuk->tanggal ? Carbon::parse($bankMasuk->tanggal)->format('d/m/Y') : '-',
                'nilai' => $bankMasuk->nilai ?? 0,
                'keterangan' => $bankMasuk->note ?? '-',
                'status' => 'Belum Dimatch',
                'match_with' => '-',
                'match_date' => '-',
                'created_by' => '-',
            ]);
        }

        // Tambahkan data yang sudah dimatch
        foreach ($existingMatches as $match) {
            $collection->push([
                'type' => 'matched',
                'no_dokumen' => $match->kwitansi_no,
                'tanggal' => Carbon::parse($match->kwitansi_tanggal)->format('d/m/Y'),
                'nilai' => $match->kwitansi_nilai,
                'keterangan' => 'Kwitansi',
                'status' => 'Sudah Dimatch',
                'match_with' => $match->bank_masuk_no,
                'match_date' => Carbon::parse($match->created_at)->format('d/m/Y'),
                'created_by' => $match->creator ? $match->creator->name : '-',
            ]);

            $collection->push([
                'type' => 'matched',
                'no_dokumen' => $match->bank_masuk_no,
                'tanggal' => Carbon::parse($match->bank_masuk_tanggal)->format('d/m/Y'),
                'nilai' => $match->bank_masuk_nilai,
                'keterangan' => 'Bank Masuk',
                'status' => 'Sudah Dimatch',
                'match_with' => $match->kwitansi_no,
                'match_date' => Carbon::parse($match->created_at)->format('d/m/Y'),
                'created_by' => $match->creator ? $match->creator->name : '-',
            ]);
        }

        return $collection;
    }

    public function headings(): array
    {
        return [
            'Jenis Dokumen',
            'No Dokumen',
            'Tanggal',
            'Nilai',
            'Keterangan',
            'Status Matching',
            'Match Dengan',
            'Tanggal Match',
            'Dibuat Oleh',
        ];
    }

    public function map($row): array
    {
        return [
            $row['type'] === 'kwitansi' ? 'Kwitansi' : ($row['type'] === 'bank_masuk' ? 'Bank Masuk' : 'Matched'),
            $row['no_dokumen'],
            $row['tanggal'],
            number_format($row['nilai'], 0, ',', '.'),
            $row['keterangan'],
            $row['status'],
            $row['match_with'],
            $row['match_date'],
            $row['created_by'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
