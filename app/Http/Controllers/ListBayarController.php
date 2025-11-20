<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PaymentVoucher;
use App\Models\Supplier;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ListBayarController extends Controller
{
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');
        $perPage = (int) ($request->get('per_page', 10));
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $supplierId = $request->get('supplier_id');
        $departmentId = $request->get('department_id');

        $hasDate = $request->filled('tanggal_start') && $request->filled('tanggal_end');

        $query = PaymentVoucher::query()->with(['supplier','department']);

        // Staff Toko & Staff Digital Marketing: only see PVs they created
        if (in_array($userRole, ['staff toko','staff digital marketing'], true)) {
            $query->where('creator_id', optional($user)->id);
        }

        if ($hasDate) {
            $query->whereBetween('tanggal', [$tanggalStart, $tanggalEnd]);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $list = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage)->withQueryString()->through(function ($pv) {
            return [
                'id' => $pv->id,
                'supplier' => $pv->supplier?->nama_supplier,
                'department' => $pv->department?->name,
                'tanggal' => optional($pv->tanggal)->toDateString(),
                'no_pv' => $pv->no_pv,
                'nominal' => $pv->nominal ?? $pv->grand_total ?? $pv->total,
                'keterangan' => $pv->keterangan ?? $pv->note,
            ];
        });

        $supplierOptions = Supplier::query()
            ->select(['id', 'nama_supplier'])
            ->orderBy('nama_supplier')
            ->get()
            ->map(fn ($s) => ['value' => $s->id, 'label' => $s->nama_supplier])
            ->values();

        $departmentOptions = Department::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])
            ->values();

        return Inertia::render('list-bayar/Index', [
            'list' => $list,
            'filters' => [
                'tanggal_start' => $tanggalStart,
                'tanggal_end' => $tanggalEnd,
                'supplier_id' => $supplierId,
                'department_id' => $departmentId,
                'per_page' => $perPage,
            ],
            'supplierOptions' => $supplierOptions,
            'departmentOptions' => $departmentOptions,
            'exportEnabled' => $hasDate,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'tanggal_start' => 'required|date',
            'tanggal_end' => 'required|date',
            'supplier_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $query = PaymentVoucher::query()->with(['supplier','department'])
            ->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $rows = $query->orderBy('tanggal')->orderBy('id')->get()->map(function ($pv) {
            return [
                'supplier' => $pv->supplier?->nama_supplier,
                'department' => $pv->department?->name,
                'tanggal' => optional($pv->tanggal)->toDateString(),
                'no_pv' => $pv->no_pv,
                'nominal' => $pv->nominal ?? $pv->grand_total ?? $pv->total,
                'keterangan' => $pv->keterangan ?? $pv->note,
            ];
        });

        $period = Carbon::parse($request->tanggal_start)->format('d/m/Y') . ' - ' . Carbon::parse($request->tanggal_end)->format('d/m/Y');

        $pdf = Pdf::loadView('list_bayar_pdf', [
            'rows' => $rows,
            'period' => $period,
        ])->setOptions(config('dompdf.options'))
          ->setPaper('a4','portrait');

        $filename = 'List_Bayar_' . Carbon::now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }
}
