<?php

namespace App\Http\Controllers;

use App\Models\BpbItem;
use App\Models\PengeluaranBarangItem;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockMutationExport;
use Carbon\Carbon;

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

        // Base query: all approved BPB items in the department (masuk)
        $baseIncoming = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->leftJoin('purchase_order_items as poi', 'poi.id', '=', 'bpb_items.purchase_order_item_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId);

        // Base query: all Pengeluaran Barang items in the department (keluar)
        $baseOutgoing = PengeluaranBarangItem::query()
            ->join('pengeluaran_barang', 'pengeluaran_barang.id', '=', 'pengeluaran_barang_items.pengeluaran_barang_id')
            ->join('barangs', 'barangs.id', '=', 'pengeluaran_barang_items.barang_id')
            ->where('pengeluaran_barang.department_id', $deptId);

        // Saldo awal per item (sebelum periode): total masuk - total keluar sebelum tanggal_start
        $saldoAwalMap = [];
        if ($start) {
            $awalMasukRows = (clone $baseIncoming)
                ->where('bpbs.tanggal', '<', $start)
                ->select([
                    'bpb_items.nama_barang',
                    'bpb_items.satuan',
                    DB::raw('COALESCE(poi.tipe, "") as jenis'),
                    DB::raw('SUM(bpb_items.qty) as qty_masuk_awal'),
                ])
                ->groupBy('bpb_items.nama_barang', 'bpb_items.satuan', 'jenis')
                ->get();

            $awalKeluarRows = (clone $baseOutgoing)
                ->where('pengeluaran_barang.tanggal', '<', $start)
                ->select([
                    'barangs.nama_barang',
                    DB::raw('SUM(pengeluaran_barang_items.qty) as qty_keluar_awal'),
                ])
                ->groupBy('barangs.nama_barang')
                ->get();

            $awalKeluarMap = [];
            foreach ($awalKeluarRows as $row) {
                $awalKeluarMap[$row->nama_barang] = (float) $row->qty_keluar_awal;
            }

            foreach ($awalMasukRows as $row) {
                $key = $row->nama_barang.'|||'.$row->satuan.'|||'.$row->jenis;
                $masukAwal = (float) $row->qty_masuk_awal;
                $keluarAwal = $awalKeluarMap[$row->nama_barang] ?? 0.0;
                $saldoAwalMap[$key] = $masukAwal - $keluarAwal;
            }
        }

        // Mutasi dalam periode (masuk)
        $periodIncoming = (clone $baseIncoming);
        if ($start && $end) {
            $periodIncoming->whereBetween('bpbs.tanggal', [$start, $end]);
        } elseif ($start) {
            $periodIncoming->where('bpbs.tanggal', '>=', $start);
        } elseif ($end) {
            $periodIncoming->where('bpbs.tanggal', '<=', $end);
        }

        $mutasiMasukRows = $periodIncoming
            ->select([
                'bpb_items.nama_barang',
                'bpb_items.satuan',
                DB::raw('COALESCE(poi.tipe, "") as jenis'),
                DB::raw('SUM(bpb_items.qty) as qty_masuk'),
            ])
            ->groupBy('bpb_items.nama_barang', 'bpb_items.satuan', 'jenis')
            ->get();

        // Mutasi dalam periode (keluar)
        $periodOutgoing = (clone $baseOutgoing);
        if ($start && $end) {
            $periodOutgoing->whereBetween('pengeluaran_barang.tanggal', [$start, $end]);
        } elseif ($start) {
            $periodOutgoing->where('pengeluaran_barang.tanggal', '>=', $start);
        } elseif ($end) {
            $periodOutgoing->where('pengeluaran_barang.tanggal', '<=', $end);
        }

        $mutasiKeluarRows = $periodOutgoing
            ->select([
                'barangs.nama_barang',
                DB::raw('SUM(pengeluaran_barang_items.qty) as qty_keluar'),
            ])
            ->groupBy('barangs.nama_barang')
            ->get();

        $keluarMap = [];
        foreach ($mutasiKeluarRows as $row) {
            $keluarMap[$row->nama_barang] = (float) $row->qty_keluar;
        }

        $data = [];
        foreach ($mutasiMasukRows as $row) {
            $key = $row->nama_barang.'|||'.$row->satuan.'|||'.$row->jenis;
            $saldoAwal = $saldoAwalMap[$key] ?? 0.0;
            $masuk = (float) $row->qty_masuk;
            $keluar = $keluarMap[$row->nama_barang] ?? 0.0;
            $saldoAkhir = $saldoAwal + $masuk - $keluar;

            $data[] = [
                'nama_barang' => $row->nama_barang,
                'jenis' => $row->jenis !== '' ? $row->jenis : null,
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

    public function exportExcel(Request $request)
    {
        $request->validate([
            'department_id' => ['required','exists:departments,id'],
            'tanggal_start' => ['nullable','date'],
            'tanggal_end' => ['nullable','date'],
        ]);

        $response = $this->data($request);
        $payload = $response->getData(true);
        $rows = $payload['data'] ?? [];

        $start = $request->input('tanggal_start');
        $end = $request->input('tanggal_end');
        if ($start && $end) {
            $dateLabel = $start . '_to_' . $end;
        } elseif ($start) {
            $dateLabel = $start;
        } elseif ($end) {
            $dateLabel = $end;
        } else {
            $dateLabel = Carbon::today()->toDateString();
        }

        $department = Department::find($request->input('department_id'));
        $departmentPart = '';
        if ($department) {
            $departmentSlug = str_replace(' ', '_', $department->name);
            $departmentPart = '-' . $departmentSlug;
        }

        $fileName = 'mutasi_stock-' . $dateLabel . $departmentPart . '.xlsx';

        return Excel::download(new StockMutationExport($rows), $fileName);
    }
}
