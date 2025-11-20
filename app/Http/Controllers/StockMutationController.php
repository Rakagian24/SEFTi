<?php

namespace App\Http\Controllers;

use App\Models\BpbItem;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockMutationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('mutasi-stock/Index', [
            'departments' => DepartmentService::getOptionsForFilter(),
        ]);
    }

    public function data(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required','exists:departments,id'],
            'tanggal_start' => ['nullable','date'],
            'tanggal_end' => ['nullable','date'],
        ]);

        $deptId = (int) $validated['department_id'];
        $start = $validated['tanggal_start'] ?? null;
        $end = $validated['tanggal_end'] ?? null;

        // Base query: all approved BPB items in the department
        $baseQuery = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId);

        // Saldo awal per item (sebelum periode)
        $saldoAwalMap = [];
        if ($start) {
            $awalRows = (clone $baseQuery)
                ->where('bpbs.tanggal', '<', $start)
                ->select([
                    'bpb_items.nama_barang',
                    'bpb_items.satuan',
                    DB::raw('SUM(bpb_items.qty) as saldo_awal'),
                ])
                ->groupBy('bpb_items.nama_barang', 'bpb_items.satuan')
                ->get();

            foreach ($awalRows as $row) {
                $saldoAwalMap[$row->nama_barang.'|||'.$row->satuan] = (float) $row->saldo_awal;
            }
        }

        // Mutasi dalam periode (masuk)
        $periodQuery = (clone $baseQuery);
        if ($start && $end) {
            $periodQuery->whereBetween('bpbs.tanggal', [$start, $end]);
        } elseif ($start) {
            $periodQuery->where('bpbs.tanggal', '>=', $start);
        } elseif ($end) {
            $periodQuery->where('bpbs.tanggal', '<=', $end);
        }

        $mutasiRows = $periodQuery
            ->select([
                'bpb_items.nama_barang',
                'bpb_items.satuan',
                DB::raw('SUM(bpb_items.qty) as qty_masuk'),
            ])
            ->groupBy('bpb_items.nama_barang', 'bpb_items.satuan')
            ->orderBy('bpb_items.nama_barang')
            ->get();

        $data = [];
        foreach ($mutasiRows as $row) {
            $key = $row->nama_barang.'|||'.$row->satuan;
            $saldoAwal = $saldoAwalMap[$key] ?? 0.0;
            $masuk = (float) $row->qty_masuk;
            $keluar = 0.0; // placeholder, will be populated from Pengeluaran module in future
            $saldoAkhir = $saldoAwal + $masuk - $keluar;

            $data[] = [
                'nama_barang' => $row->nama_barang,
                'jenis' => null, // future enhancement: map from Jenis Barang/Barang
                'satuan' => $row->satuan,
                'saldo_awal' => $saldoAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldo_akhir' => $saldoAkhir,
            ];
        }

        return response()->json([
            'data' => $data,
        ]);
    }
}
