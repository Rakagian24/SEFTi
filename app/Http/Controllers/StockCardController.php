<?php

namespace App\Http\Controllers;

use App\Models\BpbItem;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('kartu-stock/Index', [
            'departments' => DepartmentService::getOptionsForFilter(),
        ]);
    }

    /**
     * Get distinct item names for a department to populate the Barang dropdown.
     */
    public function items(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required','exists:departments,id'],
            'search' => ['nullable','string'],
        ]);

        $deptId = (int) $validated['department_id'];
        $search = trim((string) ($validated['search'] ?? ''));

        $query = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId);

        if ($search !== '') {
            $query->where('bpb_items.nama_barang', 'like', "%{$search}%");
        }

        $items = $query
            ->select('bpb_items.nama_barang')
            ->groupBy('bpb_items.nama_barang')
            ->orderBy('bpb_items.nama_barang')
            ->limit(100)
            ->pluck('nama_barang')
            ->map(function ($name) {
                return [
                    'label' => $name,
                    'value' => $name,
                ];
            })
            ->values();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Get stock card data for a single item in a department and date range.
     */
    public function data(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required','exists:departments,id'],
            'item_name' => ['required','string'],
            'tanggal_start' => ['nullable','date'],
            'tanggal_end' => ['nullable','date'],
        ]);

        $deptId = (int) $validated['department_id'];
        $itemName = $validated['item_name'];
        $start = $validated['tanggal_start'] ?? null;
        $end = $validated['tanggal_end'] ?? null;

        if (!$start && !$end) {
            // default: if no date supplied, use all time but still compute saldo awal = 0 and saldo from first transaction
            $start = null;
        }

        // Base query for approved BPB items of this item & department
        $baseQuery = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId)
            ->where('bpb_items.nama_barang', $itemName);

        // Saldo awal: total qty before periode awal
        $saldoAwal = 0.0;
        if ($start) {
            $saldoAwal = (float) $baseQuery
                ->clone()
                ->where('bpbs.tanggal', '<', $start)
                ->sum('bpb_items.qty');
        }

        // Mutasi dalam periode
        $periodQuery = $baseQuery->clone();
        if ($start && $end) {
            $periodQuery->whereBetween('bpbs.tanggal', [$start, $end]);
        } elseif ($start) {
            $periodQuery->where('bpbs.tanggal', '>=', $start);
        } elseif ($end) {
            $periodQuery->where('bpbs.tanggal', '<=', $end);
        }

        $mutasiRows = $periodQuery
            ->select([
                'bpbs.id as bpb_id',
                'bpbs.no_bpb as referensi',
                'bpbs.tanggal',
                DB::raw('SUM(bpb_items.qty) as qty_masuk'),
            ])
            ->groupBy('bpbs.id', 'bpbs.no_bpb', 'bpbs.tanggal')
            ->orderBy('bpbs.tanggal')
            ->orderBy('bpbs.no_bpb')
            ->get();

        $rows = [];
        $runningSaldo = $saldoAwal;
        $totalMasuk = 0.0;
        $totalKeluar = 0.0; // future: from Pengeluaran

        foreach ($mutasiRows as $row) {
            $masuk = (float) $row->qty_masuk;
            $keluar = 0.0; // placeholder until Pengeluaran implemented
            $runningSaldo += $masuk - $keluar;
            $totalMasuk += $masuk;

            $rows[] = [
                'referensi' => $row->referensi,
                'tanggal' => $row->tanggal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldo' => $runningSaldo,
            ];
        }

        $saldoAkhir = $runningSaldo;

        return response()->json([
            'saldo_awal' => $saldoAwal,
            'saldo_akhir' => $saldoAkhir,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'rows' => $rows,
        ]);
    }
}
