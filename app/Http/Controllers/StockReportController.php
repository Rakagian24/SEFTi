<?php

namespace App\Http\Controllers;

use App\Models\BpbItem;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        $query = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->leftJoin('purchase_order_items as poi', 'poi.id', '=', 'bpb_items.purchase_order_item_id')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $deptId);

        if (!empty($validated['tanggal_start']) && !empty($validated['tanggal_end'])) {
            $query->whereBetween('bpbs.tanggal', [$validated['tanggal_start'], $validated['tanggal_end']]);
        } elseif (!empty($validated['tanggal_start'])) {
            $query->where('bpbs.tanggal', '>=', $validated['tanggal_start']);
        } elseif (!empty($validated['tanggal_end'])) {
            $query->where('bpbs.tanggal', '<=', $validated['tanggal_end']);
        }

        $rows = $query
            ->selectRaw('bpb_items.nama_barang, bpb_items.satuan, poi.tipe as jenis, SUM(bpb_items.qty) as stock_qty')
            ->groupBy('bpb_items.nama_barang', 'bpb_items.satuan', 'jenis')
            ->orderBy('bpb_items.nama_barang')
            ->get()
            ->map(function ($row) {
                return [
                    'nama_barang' => $row->nama_barang,
                    'jenis' => $row->jenis,
                    'satuan' => $row->satuan,
                    'stock' => (float) $row->stock_qty,
                ];
            })
            ->values();

        return response()->json([
            'data' => $rows,
        ]);
    }
}
