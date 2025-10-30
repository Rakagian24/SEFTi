<?php

namespace App\Exports;

use App\Models\PaymentVoucher;
use App\Scopes\DepartmentScope;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentVoucherExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;
    protected array $columns;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $cols = [];
        if (!empty($filters['export_columns']) && is_string($filters['export_columns'])) {
            $parts = array_filter(array_map('trim', explode(',', $filters['export_columns'])));
            $cols = array_values($parts);
        }
        if (empty($cols)) {
            $cols = ['no_pv','reference_number','no_bk','tanggal','status','supplier','department'];
        }
        $this->columns = $cols;
    }

    public function collection()
    {
        $query = $this->buildQuery($this->filters);
        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        $map = [
            'no_pv' => 'No. PV',
            'reference_number' => 'Nomor Referensi Dokumen',
            'no_bk' => 'No. BK',
            'tanggal' => 'Tanggal',
            'status' => 'Status',
            'supplier' => 'Supplier',
            'department' => 'Departemen',
            'perihal' => 'Perihal',
            'metode_pembayaran' => 'Metode Pembayaran',
            'kelengkapan_dokumen' => 'Kelengkapan Dokumen',
            'nama_rekening' => 'Nama Rekening',
            'no_rekening' => 'No. Rekening',
            'no_kartu_kredit' => 'No. Kartu Kredit',
            'keterangan' => 'Keterangan',
            'total' => 'Total',
            'grand_total' => 'Grand Total',
            'created_by' => 'Dibuat Oleh',
            'created_at' => 'Tanggal Dibuat',
        ];
        $heads = [];
        foreach ($this->columns as $c) {
            if (isset($map[$c])) { $heads[] = $map[$c]; }
        }
        return $heads;
    }

    public function map($pv): array
    {
        $metode = $pv->metode_bayar
            ?? ($pv->purchaseOrder?->metode_pembayaran
                ?? ($pv->memoPembayaran?->metode_pembayaran ?? ''));
        $supplierName = $pv->supplier?->nama_supplier
            ?? $pv->purchaseOrder?->supplier?->nama_supplier
            ?? $pv->memoPembayaran?->supplier?->nama_supplier;
        $departmentName = $pv->department?->name
            ?? $pv->purchaseOrder?->department?->name
            ?? $pv->memoPembayaran?->department?->name;
        $perihalName = $pv->perihal?->nama
            ?? $pv->purchaseOrder?->perihal?->nama;
        $namaRek = $pv->nama_rekening
            ?? $pv->purchaseOrder?->bankSupplierAccount?->nama_rekening
            ?? $pv->memoPembayaran?->bankSupplierAccount?->nama_rekening
            ?? $pv->manual_nama_pemilik_rekening;
        $noRek = $pv->no_rekening
            ?? $pv->purchaseOrder?->bankSupplierAccount?->no_rekening
            ?? $pv->memoPembayaran?->bankSupplierAccount?->no_rekening
            ?? $pv->manual_no_rekening;
        $noKartu = $pv->no_kartu_kredit
            ?? $pv->creditCard?->no_kartu_kredit
            ?? $pv->purchaseOrder?->creditCard?->no_kartu_kredit;
        $referenceNumber = ($pv->tipe_pv === 'Lainnya') ? ($pv->memoPembayaran?->no_mb) : ($pv->purchaseOrder?->no_po);

        $row = [];
        foreach ($this->columns as $c) {
            switch ($c) {
                case 'no_pv': $row[] = $pv->no_pv; break;
                case 'reference_number': $row[] = $referenceNumber; break;
                case 'no_bk': $row[] = $pv->no_bk; break;
                case 'tanggal': $row[] = $pv->tanggal ? \Carbon\Carbon::parse($pv->tanggal)->format('d/m/Y') : ''; break;
                case 'status': $row[] = $pv->status; break;
                case 'supplier': $row[] = $supplierName; break;
                case 'department': $row[] = $departmentName; break;
                case 'perihal': $row[] = $perihalName; break;
                case 'metode_pembayaran': $row[] = $metode; break;
                case 'kelengkapan_dokumen': $row[] = ($pv->kelengkapan_dokumen ? 'Lengkap' : 'Tidak Lengkap'); break;
                case 'nama_rekening': $row[] = $namaRek; break;
                case 'no_rekening': $row[] = $noRek; break;
                case 'no_kartu_kredit': $row[] = $noKartu; break;
                case 'keterangan': $row[] = ($pv->keterangan ?? $pv->note); break;
                case 'total': $row[] = (float) ($pv->total ?? $pv->purchaseOrder?->total ?? 0); break;
                case 'grand_total': $row[] = (float) ($pv->grand_total ?? $pv->purchaseOrder?->grand_total ?? 0); break;
                case 'created_by': $row[] = ($pv->creator?->name ?? ''); break;
                case 'created_at': $row[] = optional($pv->created_at)->format('Y-m-d'); break;
                default: break;
            }
        }
        return $row;
    }

    protected function buildQuery(array $filters)
    {
        $user = Auth::user();
        $userRole = $user->role->name ?? '';

        $with = [
            'department', 'perihal', 'supplier', 'creator', 'creditCard',
            'purchaseOrder' => function ($q) {
                $q->with(['department', 'perihal', 'supplier', 'pph', 'termin', 'creditCard.bank', 'bankSupplierAccount.bank']);
            },
            'memoPembayaran' => function ($q) {
                $q->with(['department', 'supplier', 'bankSupplierAccount.bank']);
            },
        ];

        if ($userRole === 'Admin') {
            $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)->with($with);
        } else {
            $query = PaymentVoucher::query()->with($with);
        }

        $roleLower = strtolower($userRole);
        if (in_array($roleLower, ['staff toko','staff digital marketing'], true)) {
            $query->where('creator_id', $user->id);
        }

        // Mirror index behavior for dates: default current month when no explicit dates,
        // and when dates are provided, also include Draft/null tanggal.
        if (empty($filters['tanggal_start']) && empty($filters['tanggal_end'])) {
            $start = now()->startOfMonth()->toDateString();
            $end = now()->endOfMonth()->toDateString();
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal', [$start, $end])
                  ->orWhere('status', 'Draft')
                  ->orWhereNull('tanggal');
            });
        } else {
            $start = $filters['tanggal_start'] ?? null;
            $end = $filters['tanggal_end'] ?? null;
            $query->where(function ($q) use ($start, $end) {
                if ($start && $end) {
                    $q->whereBetween('tanggal', [$start, $end]);
                } elseif ($start) {
                    $q->whereDate('tanggal', '>=', $start);
                } elseif ($end) {
                    $q->whereDate('tanggal', '<=', $end);
                }
                // include Drafts and null tanggal similar to index behavior when date filters applied
                $q->orWhere('status', 'Draft')
                  ->orWhereNull('tanggal');
            });
        }

        if (!empty($filters['no_pv'])) {
            $query->where('no_pv', 'like', '%'.$filters['no_pv'].'%');
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['metode_bayar'])) {
            $method = $filters['metode_bayar'];
            if ($method === 'Kredit') { $method = 'Kartu Kredit'; }
            $query->where(function($q) use ($method) {
                $q->where('metode_bayar', $method)
                  ->orWhereHas('purchaseOrder', function($po) use ($method) {
                      $po->where('metode_pembayaran', $method);
                  })
                  ->orWhereHas('memoPembayaran', function($mp) use ($method) {
                      $mp->where('metode_pembayaran', $method);
                  });
            });
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // Explicitly ignore 'search' and 'per_page' for export

        return $query;
    }
}
