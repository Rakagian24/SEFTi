<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BpbItem;
use App\Models\PengeluaranBarangItem;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockCardExport;
use Carbon\Carbon;

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
            'search' => ['nullable','string'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = Barang::active();

        if ($search !== '') {
            $query->where('nama_barang', 'like', "%{$search}%");
        }

        $items = $query
            ->orderBy('nama_barang')
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

        // Base query for approved BPB items of this item & department (masuk)
        $baseIncoming = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId)
            ->where('bpb_items.nama_barang', $itemName);

        // Base query for Pengeluaran Barang items of this item & department (keluar)
        $baseOutgoing = PengeluaranBarangItem::query()
            ->join('pengeluaran_barang', 'pengeluaran_barang.id', '=', 'pengeluaran_barang_items.pengeluaran_barang_id')
            ->join('barangs', 'barangs.id', '=', 'pengeluaran_barang_items.barang_id')
            ->where('pengeluaran_barang.department_id', $deptId)
            ->where('barangs.nama_barang', $itemName);

        // Saldo awal: total masuk - total keluar sebelum tanggal_start (jika ada start)
        $saldoAwal = 0.0;
        if ($start) {
            $qtyMasukAwal = (float) $baseIncoming
                ->clone()
                ->where('bpbs.tanggal', '<', $start)
                ->sum('bpb_items.qty');

            $qtyKeluarAwal = (float) $baseOutgoing
                ->clone()
                ->where('pengeluaran_barang.tanggal', '<', $start)
                ->sum('pengeluaran_barang_items.qty');

            $saldoAwal = $qtyMasukAwal - $qtyKeluarAwal;
        }

        // Mutasi dalam periode: gabungkan transaksi masuk (BPB) dan keluar (Pengeluaran Barang)
        $mutasi = [];

        $periodIncoming = $baseIncoming->clone();
        if ($start && $end) {
            $periodIncoming->whereBetween('bpbs.tanggal', [$start, $end]);
        } elseif ($start) {
            $periodIncoming->where('bpbs.tanggal', '>=', $start);
        } elseif ($end) {
            $periodIncoming->where('bpbs.tanggal', '<=', $end);
        }

        $incomingRows = $periodIncoming
            ->select([
                'bpbs.tanggal',
                'bpbs.no_bpb as referensi',
                DB::raw('SUM(bpb_items.qty) as qty_masuk'),
            ])
            ->groupBy('bpbs.tanggal', 'bpbs.no_bpb')
            ->get();

        foreach ($incomingRows as $row) {
            $mutasi[] = [
                'tanggal' => $row->tanggal,
                'referensi' => $row->referensi,
                'masuk' => (float) $row->qty_masuk,
                'keluar' => 0.0,
            ];
        }

        $periodOutgoing = $baseOutgoing->clone();
        if ($start && $end) {
            $periodOutgoing->whereBetween('pengeluaran_barang.tanggal', [$start, $end]);
        } elseif ($start) {
            $periodOutgoing->where('pengeluaran_barang.tanggal', '>=', $start);
        } elseif ($end) {
            $periodOutgoing->where('pengeluaran_barang.tanggal', '<=', $end);
        }

        $outgoingRows = $periodOutgoing
            ->select([
                'pengeluaran_barang.tanggal',
                'pengeluaran_barang.no_pengeluaran as referensi',
                DB::raw('SUM(pengeluaran_barang_items.qty) as qty_keluar'),
            ])
            ->groupBy('pengeluaran_barang.tanggal', 'pengeluaran_barang.no_pengeluaran')
            ->get();

        foreach ($outgoingRows as $row) {
            $mutasi[] = [
                'tanggal' => $row->tanggal,
                'referensi' => $row->referensi,
                'masuk' => 0.0,
                'keluar' => (float) $row->qty_keluar,
            ];
        }

        // Urutkan mutasi berdasarkan tanggal lalu referensi
        usort($mutasi, function ($a, $b) {
            if ($a['tanggal'] === $b['tanggal']) {
                return strcmp((string) $a['referensi'], (string) $b['referensi']);
            }
            return strcmp((string) $a['tanggal'], (string) $b['tanggal']);
        });

        $rows = [];
        $runningSaldo = $saldoAwal;
        $totalMasuk = 0.0;
        $totalKeluar = 0.0;

        foreach ($mutasi as $row) {
            $masuk = (float) $row['masuk'];
            $keluar = (float) $row['keluar'];
            $runningSaldo += $masuk - $keluar;
            $totalMasuk += $masuk;
            $totalKeluar += $keluar;

            $rows[] = [
                'referensi' => $row['referensi'],
                'tanggal' => $row['tanggal'],
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

    public function exportExcel(Request $request)
    {
        $request->validate([
            'department_id' => ['required','exists:departments,id'],
            'item_name' => ['required','string'],
            'tanggal_start' => ['nullable','date'],
            'tanggal_end' => ['nullable','date'],
        ]);

        $response = $this->data($request);
        $payload = $response->getData(true);
        $rows = $payload['rows'] ?? [];

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

        $fileName = 'kartu_stock-' . $dateLabel . $departmentPart . '.xlsx';

        return Excel::download(new StockCardExport($rows), $fileName);
    }
}
