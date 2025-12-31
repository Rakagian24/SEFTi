<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PaymentVoucher;
use App\Models\Supplier;
use App\Models\Department;
use App\Models\ListBayarDocument;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ListBayarController extends Controller
{
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');
        $perPage = (int) ($request->get('per_page', 10));
        $documentsPerPage = (int) ($request->get('documents_per_page', 10));
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $supplierId = $request->get('supplier_id');
        $departmentId = $request->get('department_id');
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        $documentsSearch = $request->get('documents_search');

        $hasDate = $request->filled('tanggal_start') && $request->filled('tanggal_end');

        // Base query uses DepartmentScope on PaymentVoucher by default
        $query = PaymentVoucher::query()
            ->with(['supplier','department','creator.role'])
            ->where('status', 'In Progress')
            ->whereDoesntHave('listBayarDocuments');

        // Staff Toko & Kepala Toko: only PVs created by Staff Toko or Kepala Toko
        if (in_array($userRole, ['staff toko','kepala toko'], true)) {
            $query->whereHas('creator.role', function ($q) {
                $q->whereIn('name', ['Staff Toko', 'Kepala Toko']);
            });
        }

        // Staff Digital Marketing: only PVs created by Staff Digital Marketing
        if ($userRole === 'staff digital marketing') {
            $query->whereHas('creator.role', function ($q) {
                $q->where('name', 'Staff Digital Marketing');
            });
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
        if ($tanggalStart && $tanggalEnd) {
            $query->whereBetween('tanggal', [$tanggalStart, $tanggalEnd]);
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

        // Dokumen List Bayar (untuk tab kedua)
        $documentsQuery = ListBayarDocument::query()
            ->withCount('paymentVouchers');

        if ($documentsSearch) {
            $documentsQuery->where('no_list_bayar', 'like', "%{$documentsSearch}%");
        }

        $documents = $documentsQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($documentsPerPage)
            ->withQueryString()
            ->through(function ($doc) {
                return [
                    'id' => $doc->id,
                    'no_list_bayar' => $doc->no_list_bayar,
                    'tanggal' => optional($doc->tanggal)->toDateString(),
                    'jumlah_pv' => $doc->payment_vouchers_count,
                ];
            });

        return Inertia::render('list-bayar/Index', [
            'list' => $list,
            'documents' => $documents,
            'filters' => [
                'tanggal_start' => $tanggalStart,
                'tanggal_end' => $tanggalEnd,
                'supplier_id' => $supplierId,
                'department_id' => $departmentId,
                'per_page' => $perPage,
                'documents_per_page' => $documentsPerPage,
                'documents_search' => $documentsSearch,
            ],
            'supplierOptions' => $supplierOptions,
            'departmentOptions' => $departmentOptions,
            'exportEnabled' => $hasDate,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'tanggal_start' => 'required|date',
            'tanggal_end' => 'required|date',
            'supplier_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'integer',
            'export_label' => 'nullable|string|max:100',
        ]);

        $query = PaymentVoucher::query()
            ->with(['supplier','department'])
            ->where('status', 'In Progress')
            ->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end])
            ->whereDoesntHave('listBayarDocuments');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('selected_ids')) {
            $query->whereIn('id', $request->input('selected_ids', []));
        }

        $paymentVouchers = $query->orderBy('tanggal')->orderBy('id')->get();

        // Jika tidak ada data, langsung hentikan dengan error sederhana
        if ($paymentVouchers->isEmpty()) {
            return back()->with('error', 'Tidak ada Payment Voucher yang dapat di-export.');
        }

        // Tentukan tanggal dokumen dari export_label (jika bisa diparse), jika tidak pakai tanggal_end
        $documentDate = null;
        if (!empty($validated['export_label'] ?? null)) {
            try {
                $documentDate = Carbon::parse($validated['export_label']);
            } catch (\Throwable $e) {
                $documentDate = null;
            }
        }
        if (!$documentDate) {
            $documentDate = Carbon::parse($request->tanggal_end ?? $request->tanggal_start);
        }

        // Tentukan department untuk penomoran dokumen
        $departmentIdForNumber = (int) ($request->department_id ?? 0);
        $departmentAlias = 'All';
        if ($departmentIdForNumber > 0) {
            $dept = Department::select('alias', 'name')->find($departmentIdForNumber);
            if ($dept) {
                $departmentAlias = $dept->alias ?: substr($dept->name ?? 'All', 0, 3);
            }
        }

        // Generate nomor dokumen List Bayar
        $noListBayar = DocumentNumberService::generateNumberForDate('List Bayar', null, $departmentIdForNumber, $departmentAlias, $documentDate);

        // Buat header dokumen List Bayar
        $document = ListBayarDocument::create([
            'no_list_bayar' => $noListBayar,
            'tanggal' => $documentDate->toDateString(),
            'department_id' => $departmentIdForNumber,
            'jumlah_pv' => $paymentVouchers->count(),
            'created_by' => optional(\Illuminate\Support\Facades\Auth::user())->id,
        ]);

        // Simpan pivot PV yang termasuk dalam dokumen ini
        $document->paymentVouchers()->sync($paymentVouchers->pluck('id')->all());

        $rows = $paymentVouchers->map(function ($pv) {
            return [
                'supplier' => $pv->supplier?->nama_supplier,
                'department' => $pv->department?->name,
                'tanggal' => optional($pv->tanggal)->toDateString(),
                'no_pv' => $pv->no_pv,
                'nominal' => $pv->nominal ?? $pv->grand_total ?? $pv->total,
                'keterangan' => $pv->keterangan ?? $pv->note,
            ];
        });

        $startDate = Carbon::parse($request->tanggal_start);
        $endDate = Carbon::parse($request->tanggal_end);

        if ($startDate->isSameDay($endDate)) {
            // Jika hanya 1 hari, tampilkan dalam format panjang Indonesia, mis: "Selasa, 25 Desember 2025"
            $period = $endDate->locale('id')->translatedFormat('l, d F Y');
        } else {
            // Jika rentang tanggal, tetap gunakan format ringkas dd/mm/YYYY - dd/mm/YYYY
            $period = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        }

        $pdf = Pdf::loadView('list_bayar_pdf', [
            'rows' => $rows,
            'period' => $period,
        ])->setOptions(config('dompdf.options'))
          ->setPaper('a4', 'landscape');

        $exportLabel = $request->input('export_label');
        if ($exportLabel) {
            // Pastikan nama file tidak mengandung karakter yang tidak valid seperti '/' atau '\\'
            $safeLabel = str_replace(['/', '\\'], '-', $exportLabel);
            $filename = 'Dokumen List Bayar per Tanggal ' . $safeLabel . '.pdf';
        } else {
            $filename = 'List_Bayar_' . Carbon::now()->format('Ymd_His') . '.pdf';
        }
        return $pdf->download($filename);
    }

    public function editDocument(Request $request, ListBayarDocument $document)
    {
        $perPage = (int) ($request->get('per_page', 10));
        $documentsPerPage = (int) ($request->get('documents_per_page', 10));
        $supplierId = $request->get('supplier_id');
        $departmentId = $request->get('department_id');
        $documentsSearch = $request->get('documents_search');
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');

        // Ambil ID PV yang sudah termasuk dalam dokumen ini
        $documentPvIds = $document->paymentVouchers()->pluck('payment_voucher_id')->all();

        // Base query: PV In Progress
        $query = PaymentVoucher::query()
            ->with(['supplier','department'])
            ->where('status', 'In Progress');

        $hasDateFilter = $tanggalStart && $tanggalEnd;

        // Default (tanpa filter tanggal/supplier/department): hanya tampilkan PV yang sudah termasuk dokumen ini
        if (!$hasDateFilter && !$supplierId && !$departmentId) {
            $query->whereHas('listBayarDocuments', function ($sub) use ($document) {
                $sub->where('list_bayar_document_id', $document->id);
            });
        } else {
            // Jika ada filter (tanggal/supplier/department), tampilkan PV yang sudah termasuk dokumen ini
            // ATAU belum masuk dokumen manapun, supaya user bisa menambah PV baru ke dokumen ini.
            $query->where(function ($q) use ($document) {
                $q->whereHas('listBayarDocuments', function ($sub) use ($document) {
                    $sub->where('list_bayar_document_id', $document->id);
                })->orWhereDoesntHave('listBayarDocuments');
            });
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        if ($tanggalStart && $tanggalEnd) {
            $query->whereBetween('tanggal', [$tanggalStart, $tanggalEnd]);
        }

        $list = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($perPage)->withQueryString()->through(function ($pv) use ($documentPvIds) {
            return [
                'id' => $pv->id,
                'supplier' => $pv->supplier?->nama_supplier,
                'department' => $pv->department?->name,
                'tanggal' => optional($pv->tanggal)->toDateString(),
                'no_pv' => $pv->no_pv,
                'nominal' => $pv->nominal ?? $pv->grand_total ?? $pv->total,
                'keterangan' => $pv->keterangan ?? $pv->note,
                'selected' => in_array($pv->id, $documentPvIds, true),
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

        return Inertia::render('list-bayar/EditDocument', [
            'document' => [
                'id' => $document->id,
                'no_list_bayar' => $document->no_list_bayar,
                'tanggal' => optional($document->tanggal)->toDateString(),
                'department_id' => $document->department_id,
            ],
            'list' => $list,
            'selectedIds' => $documentPvIds,
            'filters' => [
                'tanggal_start' => $tanggalStart,
                'tanggal_end' => $tanggalEnd,
                'supplier_id' => $supplierId,
                'department_id' => $departmentId,
                'per_page' => $perPage,
            ],
            'supplierOptions' => $supplierOptions,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    public function exportDocumentPdf(Request $request, ListBayarDocument $document)
    {
        $validated = $request->validate([
            'selected_ids' => 'required|array',
            'selected_ids.*' => 'integer',
            'export_label' => 'nullable|string|max:100',
        ]);

        $selectedIds = $validated['selected_ids'] ?? [];
        if (empty($selectedIds)) {
            return back()->with('error', 'Silakan pilih minimal satu Payment Voucher.');
        }

        // Base query: hanya PV yang dipilih, dan yang In Progress, serta
        // belum terikat ke dokumen lain, atau sudah terikat ke dokumen ini
        $query = PaymentVoucher::query()
            ->with(['supplier','department'])
            ->where('status', 'In Progress')
            ->whereIn('id', $selectedIds)
            ->where(function ($q) use ($document) {
                $q->whereHas('listBayarDocuments', function ($sub) use ($document) {
                    $sub->where('list_bayar_document_id', $document->id);
                })->orWhereDoesntHave('listBayarDocuments');
            });

        $paymentVouchers = $query->orderBy('tanggal')->orderBy('id')->get();

        if ($paymentVouchers->isEmpty()) {
            return back()->with('error', 'Tidak ada Payment Voucher yang dapat di-export.');
        }

        // Tentukan tanggal dokumen dari export_label (jika bisa diparse), jika tidak pakai tanggal saat ini
        $documentDate = null;
        if (!empty($validated['export_label'] ?? null)) {
            try {
                $documentDate = Carbon::parse($validated['export_label']);
            } catch (\Throwable $e) {
                $documentDate = null;
            }
        }
        if (!$documentDate) {
            $documentDate = Carbon::now();
        }

        // Update header dokumen
        $document->tanggal = $documentDate->toDateString();
        $document->jumlah_pv = $paymentVouchers->count();
        $document->save();

        // Update isi dokumen
        $document->paymentVouchers()->sync($paymentVouchers->pluck('id')->all());

        $rows = $paymentVouchers->map(function ($pv) {
            return [
                'supplier' => $pv->supplier?->nama_supplier,
                'department' => $pv->department?->name,
                'tanggal' => optional($pv->tanggal)->toDateString(),
                'no_pv' => $pv->no_pv,
                'nominal' => $pv->nominal ?? $pv->grand_total ?? $pv->total,
                'keterangan' => $pv->keterangan ?? $pv->note,
            ];
        });

        // Tampilkan tanggal dokumen dalam format panjang Indonesia, mis: "Selasa, 25 Desember 2025"
        $period = $documentDate->locale('id')->translatedFormat('l, d F Y');

        $pdf = Pdf::loadView('list_bayar_pdf', [
            'rows' => $rows,
            'period' => $period,
        ])->setOptions(config('dompdf.options'))
          ->setPaper('a4', 'landscape');

        $exportLabel = $validated['export_label'] ?? null;
        if ($exportLabel) {
            // Pastikan nama file tidak mengandung karakter yang tidak valid seperti '/' atau '\\'
            $safeLabel = str_replace(['/', '\\'], '-', $exportLabel);
            $filename = 'Dokumen List Bayar per Tanggal ' . $safeLabel . '.pdf';
        } else {
            $filename = 'Dokumen List Bayar ' . $document->no_list_bayar . '.pdf';
        }

        return $pdf->download($filename);
    }
}
