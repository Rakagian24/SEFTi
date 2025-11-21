<?php

namespace App\Http\Controllers;

use App\Models\BpbItem;
use App\Models\PengeluaranBarang;
use App\Models\PengeluaranBarangItem;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockReportExport;
use Carbon\Carbon;

class StockReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            return $this->data($request);
        }

        return Inertia::render('stock/Index', [
            'filters' => $request->all(),
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

        if (empty($validated['department_id'])) {
            return response()->json(['data' => []]);
        }

        $deptId = (int) $validated['department_id'];
        $start = $validated['tanggal_start'] ?? null;
        $end = $validated['tanggal_end'] ?? null;

        // Base query untuk barang masuk (BPB Approved)
        $incomingQuery = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->leftJoin('purchase_order_items as poi', 'poi.id', '=', 'bpb_items.purchase_order_item_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId);

        if ($start && $end) {
            $incomingQuery->whereBetween('bpbs.tanggal', [$start, $end]);
        } elseif ($start) {
            $incomingQuery->where('bpbs.tanggal', '>=', $start);
        } elseif ($end) {
            $incomingQuery->where('bpbs.tanggal', '<=', $end);
        }

        $incomingRows = $incomingQuery
            ->select([
                'bpb_items.nama_barang',
                'bpb_items.satuan',
                DB::raw('COALESCE(poi.tipe, "") as jenis'),
                DB::raw('SUM(bpb_items.qty) as total_masuk'),
            ])
            ->groupBy('bpb_items.nama_barang', 'bpb_items.satuan', 'jenis')
            ->get();

        // Base query untuk barang keluar (Pengeluaran Barang)
        $outgoingQuery = PengeluaranBarangItem::query()
            ->join('pengeluaran_barang', 'pengeluaran_barang.id', '=', 'pengeluaran_barang_items.pengeluaran_barang_id')
            ->join('barangs', 'barangs.id', '=', 'pengeluaran_barang_items.barang_id')
            ->where('pengeluaran_barang.department_id', $deptId);

        if ($start && $end) {
            $outgoingQuery->whereBetween('pengeluaran_barang.tanggal', [$start, $end]);
        } elseif ($start) {
            $outgoingQuery->where('pengeluaran_barang.tanggal', '>=', $start);
        } elseif ($end) {
            $outgoingQuery->where('pengeluaran_barang.tanggal', '<=', $end);
        }

        $outgoingRows = $outgoingQuery
            ->select([
                'barangs.nama_barang',
                DB::raw('SUM(pengeluaran_barang_items.qty) as total_keluar'),
            ])
            ->groupBy('barangs.nama_barang')
            ->get();

        // Map keluar per nama_barang untuk memudahkan pengurangan stok
        $outgoingMap = [];
        foreach ($outgoingRows as $row) {
            $outgoingMap[$row->nama_barang] = (float) $row->total_keluar;
        }

        $rows = $incomingRows
            ->map(function ($row) use ($outgoingMap) {
                $namaBarang = $row->nama_barang;
                $keluar = $outgoingMap[$namaBarang] ?? 0.0;
                $masuk = (float) $row->total_masuk;
                $stock = $masuk - $keluar;

                return [
                    'nama_barang' => $namaBarang,
                    'jenis' => $row->jenis !== '' ? $row->jenis : null,
                    'satuan' => $row->satuan,
                    'stock' => $stock,
                ];
            })
            ->sortBy('nama_barang')
            ->values();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function exportExcel(Request $request)
    {
        // Reuse validation and query logic from data() to ensure consistency
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

        $fileName = 'laporan_stock-' . $dateLabel . $departmentPart . '.xlsx';

        return Excel::download(new StockReportExport($rows), $fileName);
    }
}
