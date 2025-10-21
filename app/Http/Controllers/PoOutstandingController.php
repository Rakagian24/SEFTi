<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Department;
use App\Models\Perihal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PoOutstandingExport;
use Barryvdh\DomPDF\Facade\Pdf;

class PoOutstandingController extends Controller
{
    public function index(Request $request)
    {
        $wantsJson = $request->wantsJson() || $request->header('Accept') === 'application/json';

        if ($wantsJson) {
            return $this->data($request);
        }

        return Inertia::render('po-outstanding/Index', [
            'filters' => $request->all(),
            'departments' => Department::orderBy('name')->get(['id','name']),
            'perihals' => Perihal::active()->orderBy('nama')->get(['id','nama','status']),
        ]);
    }

    public function data(Request $request)
    {
        $hasAnyFilter = $request->filled('tanggal_start') || $request->filled('tanggal_end') || $request->filled('no_po') || $request->filled('department_id') || $request->filled('perihal_id') || $request->filled('supplier_id') || $request->filled('supplier_name') || $request->filled('search');

        if (!$hasAnyFilter) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => (int) $request->input('per_page', 10),
                'total' => 0,
            ]);
        }

        $user = Auth::user();
        $userRole = $user->role->name ?? '';

        if (in_array($userRole, ['Admin', 'Kabag', 'Direksi'])) {
            $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['department', 'perihal', 'supplier']);
        } else {
            if ($request->filled('department_id')) {
                $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                    ->with(['department', 'perihal', 'supplier']);
            } else {
                $query = PurchaseOrder::query()->with(['department', 'perihal', 'supplier']);
            }
        }

        $query->whereRaw('COALESCE(nominal,0) - COALESCE(grand_total,0) > 0');

        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
        } elseif ($request->filled('tanggal_start')) {
            $query->where('tanggal', '>=', $request->tanggal_start);
        } elseif ($request->filled('tanggal_end')) {
            $query->where('tanggal', '<=', $request->tanggal_end);
        }

        if ($request->filled('no_po')) {
            $query->where('no_po', 'like', '%'.$request->no_po.'%');
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('perihal_id')) {
            $query->where('perihal_id', $request->perihal_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('supplier_name')) {
            $query->whereHas('supplier', function($q) use ($request) {
                $q->where('nama_supplier', 'like', '%'.$request->supplier_name.'%');
            });
        }

        if ($request->filled('search')) {
            $query->searchOptimized($request->input('search'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $data = $query->orderByDesc('created_at')->paginate($perPage)->through(function($po) {
            $outstanding = (float) ($po->nominal ?? 0) - (float) ($po->grand_total ?? 0);
            return [
                'id' => $po->id,
                'no_po' => $po->no_po,
                'department' => $po->department ? $po->department->name : null,
                'tanggal' => $po->tanggal,
                'perihal' => $po->perihal ? $po->perihal->nama : null,
                'supplier' => $po->supplier ? $po->supplier->nama_supplier : null,
                'nominal' => $po->nominal,
                'grand_total' => $po->grand_total,
                'outstanding' => $outstanding,
                'status' => $po->status,
            ];
        })->withQueryString();

        return response()->json($data);
    }

    public function exportExcel(Request $request)
    {
        $ids = $request->input('ids');
        $filters = $request->all();
        return Excel::download(new PoOutstandingExport($ids, $filters), 'po_outstanding.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $ids = $request->input('ids');
        $filters = $request->all();

        $query = $this->buildQuery($filters);
        if (is_array($ids) && count($ids) > 0) {
            $query->whereIn('id', $ids);
        }
        $rows = $query->orderByDesc('created_at')->get();

        $data = $rows->map(function($po) {
            $outstanding = (float) ($po->nominal ?? 0) - (float) ($po->grand_total ?? 0);
            return [
                'no_po' => $po->no_po,
                'department' => $po->department ? $po->department->name : null,
                'tanggal' => $po->tanggal,
                'perihal' => $po->perihal ? $po->perihal->nama : null,
                'supplier' => $po->supplier ? $po->supplier->nama_supplier : null,
                'nominal' => $po->nominal,
                'grand_total' => $po->grand_total,
                'outstanding' => $outstanding,
            ];
        });

        $pdf = Pdf::loadView('po_outstanding_pdf', [
            'rows' => $data,
            'generated_at' => now(),
        ]);

        return $pdf->download('po_outstanding.pdf');
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
