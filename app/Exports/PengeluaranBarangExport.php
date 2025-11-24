<?php

namespace App\Exports;

use App\Models\PengeluaranBarang;
use App\Models\PengeluaranBarangItem;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PengeluaranBarangExport implements FromCollection, WithHeadings, WithMapping
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
        // Build base query for parent records with filters applied
        $parentQuery = $this->buildParentQuery($this->filters);

        if (is_array($this->ids) && count($this->ids) > 0) {
            $parentQuery->whereIn('id', $this->ids);
        }

        $parentIds = $parentQuery->pluck('id');

        if ($parentIds->isEmpty()) {
            return collect();
        }

        // Item-based export: one row per item, include parent + barang info
        return PengeluaranBarangItem::query()
            ->with(['pengeluaranBarang.department', 'pengeluaranBarang.createdBy', 'barang'])
            ->whereIn('pengeluaran_barang_id', $parentIds)
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No. Pengeluaran',
            'Tanggal',
            'Departemen',
            // 'Jenis Pengeluaran',
            // 'Keterangan Header',
            'Dikeluarkan Oleh',
            'Nama Barang',
            'Satuan',
            'Qty Pengeluaran',
            'Keterangan Item',
        ];
    }

    public function map($item): array
    {
        $parent = $item->pengeluaranBarang;

        return [
            $parent ? $parent->no_pengeluaran : '',
            ($parent && $parent->tanggal) ? \Carbon\Carbon::parse($parent->tanggal)->format('d/m/Y') : '',
            $parent && $parent->department ? $parent->department->name : '',
            // $parent ? ($parent->jenis_pengeluaran ?? '') : '',
            // $parent ? ($parent->keterangan ?? '') : '',
            $parent && $parent->createdBy ? $parent->createdBy->name : '',
            $item->barang ? $item->barang->nama_barang : '',
            $item->barang ? $item->barang->satuan : '',
            (float) ($item->qty ?? 0),
            $item->keterangan ?? '',
        ];
    }

    protected function buildParentQuery(array $filters)
    {
        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');
        $isAdmin = $userRole === 'admin';
        $isStaffToko = $userRole === 'staff toko';
        $isBranchManager = $userRole === 'branch manager';
        $isKepalaToko = $userRole === 'kepala toko';

        // Sama seperti index(): hanya role yang diizinkan
        if (!($isAdmin || $isStaffToko || $isBranchManager || $isKepalaToko)) {
            return PengeluaranBarang::query()->whereRaw('1 = 0');
        }

        $query = PengeluaranBarang::with(['department', 'createdBy']);

        // Additional department-based restriction: only Human Greatness and Zi&Glo
        if ($user) {
            $primaryDepartment = $user->department ?: $user->departments->first();
            $departmentName = optional($primaryDepartment)->name;
            $normalizedDept = $departmentName ? strtolower(trim($departmentName)) : '';

            if (!in_array($normalizedDept, ['human greatness', 'zi&glo'], true)) {
                return PengeluaranBarang::query()->whereRaw('1 = 0');
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('no_pengeluaran', 'like', "%$search%")
                    ->orWhereHas('department', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('createdBy', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['jenis_pengeluaran'])) {
            $query->where('jenis_pengeluaran', $filters['jenis_pengeluaran']);
        }

        if (!empty($filters['date']) && is_array($filters['date']) && count($filters['date']) === 2) {
            $query->whereBetween('tanggal', [$filters['date'][0], $filters['date'][1]]);
        }

        return $query;
    }
}
