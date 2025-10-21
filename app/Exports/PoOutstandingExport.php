<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class PoOutstandingExport implements FromCollection, WithHeadings, WithMapping
{
    protected $ids;
    protected $filters;

    public function __construct($ids = null, array $filters = [])
    {
        $this->ids = $ids;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = $this->buildQuery($this->filters);
        if (is_array($this->ids) && count($this->ids) > 0) {
            $query->whereIn('id', $this->ids);
        }
        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'No. PO',
            'Departemen',
            'Tanggal',
            'Perihal',
            'Supplier',
            'Nominal',
            'Grand Total',
            'Outstanding',
        ];
    }

    public function map($po): array
    {
        $outstanding = (float) ($po->nominal ?? 0) - (float) ($po->grand_total ?? 0);
        return [
            $po->no_po,
            $po->department ? $po->department->name : '',
            $po->tanggal ? \Carbon\Carbon::parse($po->tanggal)->format('d/m/Y') : '',
            $po->perihal ? $po->perihal->nama : '',
            $po->supplier ? $po->supplier->nama_supplier : '',
            (float) $po->nominal,
            (float) $po->grand_total,
            $outstanding,
        ];
    }

    protected function buildQuery(array $filters)
    {
        $user = Auth::user();
        $userRole = $user->role->name ?? '';

        if (in_array($userRole, ['Admin', 'Kabag', 'Direksi'])) {
            $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['department', 'perihal', 'supplier']);
        } else {
            if (!empty($filters['department_id'])) {
                $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                    ->with(['department', 'perihal', 'supplier']);
            } else {
                $query = PurchaseOrder::query()->with(['department', 'perihal', 'supplier']);
            }
        }

        $query->whereRaw('COALESCE(nominal,0) - COALESCE(grand_total,0) > 0');

        if (!empty($filters['tanggal_start']) && !empty($filters['tanggal_end'])) {
            $query->whereBetween('tanggal', [$filters['tanggal_start'], $filters['tanggal_end']]);
        } elseif (!empty($filters['tanggal_start'])) {
            $query->where('tanggal', '>=', $filters['tanggal_start']);
        } elseif (!empty($filters['tanggal_end'])) {
            $query->where('tanggal', '<=', $filters['tanggal_end']);
        }

        if (!empty($filters['no_po'])) {
            $query->where('no_po', 'like', '%'.$filters['no_po'].'%');
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['perihal_id'])) {
            $query->where('perihal_id', $filters['perihal_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['supplier_name'])) {
            $supplierName = $filters['supplier_name'];
            $query->whereHas('supplier', function($q) use ($supplierName) {
                $q->where('nama_supplier', 'like', '%'.$supplierName.'%');
            });
        }

        if (!empty($filters['search'])) {
            $query->searchOptimized($filters['search']);
        }

        return $query;
    }
}
