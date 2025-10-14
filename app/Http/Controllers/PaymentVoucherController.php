<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\PaymentVoucher;
use App\Scopes\DepartmentScope;
use App\Services\DocumentNumberService;
use App\Models\Department;
use App\Models\Supplier;
use App\Models\PaymentVoucherDocument;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = $user->role->name ?? '';

        // Base query with eager loads
        $with = ['department', 'perihal', 'supplier', 'creator', 'purchaseOrder'];
        if (in_array($userRole, ['Admin', 'Kabag', 'Direksi'])) {
            $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                ->with($with);
        } else {
            $statusFilterInit = $request->get('status');
            // For Draft, include user's created drafts as well as those in user's departments
            if ($statusFilterInit === 'Draft') {
                $userDeptIds = $user->departments->pluck('id')->all();
                $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                    ->with($with)
                    ->where(function($q) use ($userDeptIds, $user) {
                        if (!empty($userDeptIds)) {
                            $q->whereIn('department_id', $userDeptIds);
                        }
                        $q->orWhere('creator_id', $user->id);
                    });
            } elseif ($request->filled('department_id')) {
                $query = PaymentVoucher::withoutGlobalScope(DepartmentScope::class)
                    ->with($with);
            } else {
                $query = PaymentVoucher::query()->with($with);
            }
        }

        // Default current month: include Drafts (and records with null tanggal) alongside date range results
        $statusFilter = $request->get('status');
        if (!$request->filled('tanggal_start') && !$request->filled('tanggal_end')) {
            $start = now()->startOfMonth()->toDateString();
            $end = now()->endOfMonth()->toDateString();
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal', [$start, $end])
                  ->orWhere('status', 'Draft')
                  ->orWhereNull('tanggal');
            });
        }

        // Filters
        if ($request->filled('tanggal_start') || $request->filled('tanggal_end')) {
            $start = $request->filled('tanggal_start') ? $request->tanggal_start : null;
            $end = $request->filled('tanggal_end') ? $request->tanggal_end : null;
            $query->where(function ($q) use ($start, $end) {
                if ($start && $end) {
                    $q->whereBetween('tanggal', [$start, $end]);
                } elseif ($start) {
                    $q->whereDate('tanggal', '>=', $start);
                } elseif ($end) {
                    $q->whereDate('tanggal', '<=', $end);
                }
                // Always include Drafts (and null tanggal) regardless of date range
                $q->orWhere('status', 'Draft')
                  ->orWhereNull('tanggal');
            });
        }

        if ($request->filled('no_pv')) {
            $query->where('no_pv', 'like', '%' . $request->no_pv . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Search across key fields
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('no_pv', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('no_bk', 'like', "%$search%")
                  ->orWhere('tanggal', 'like', "%$search%");
            })
            ->orWhereHas('supplier', function ($qs) use ($search) {
                $qs->where('nama_supplier', 'like', "%$search%");
            })
            ->orWhereHas('department', function ($qd) use ($search) {
                $qd->where('name', 'like', "%$search%");
            });
        }

        $perPage = (int) ($request->get('per_page', 10));
        $paymentVouchers = $query
            ->orderBy('id', 'DESC')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($pv) {
                return [
                    'id' => $pv->id,
                    'no_pv' => $pv->no_pv,
                    'no_po' => $pv->purchaseOrder?->no_po,
                    'no_bk' => $pv->no_bk,
                    'tanggal' => $pv->tanggal,
                    'status' => $pv->status,
                    'supplier_name' => $pv->supplier?->nama_supplier,
                    'department_name' => $pv->department?->name,
                ];
            });

        return Inertia::render('payment-voucher/Index', [
            'userRole' => $userRole,
            'userPermissions' => $user->role->permissions ?? [],
            'paymentVouchers' => $paymentVouchers,
            'departmentOptions' => Department::query()->active()->select(['id','name'])->orderBy('name')->get()->map(fn($d)=>['value'=>$d->id,'label'=>$d->name])->values(),
            'supplierOptions' => Supplier::query()->active()->select(['id','nama_supplier','department_id'])->orderBy('nama_supplier')->get()->map(fn($s)=>['value'=>$s->id,'label'=>$s->nama_supplier,'department_id'=>$s->department_id])->values(),
            'filters' => [
                'tanggal_start' => $request->get('tanggal_start'),
                'tanggal_end' => $request->get('tanggal_end'),
                'no_pv' => $request->get('no_pv'),
                'department_id' => $request->get('department_id'),
                'status' => $request->get('status'),
                'supplier_id' => $request->get('supplier_id'),
                'search' => $request->get('search'),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $departments = Department::query()->active()->select(['id','name','alias'])->orderBy('name')->get()
            ->map(fn($d)=>[
                'value'=>$d->id,
                'label'=> $d->name,
                'name'=> $d->name,
                'alias'=>$d->alias,
            ])->values();

        $suppliers = Supplier::active()->with(['bankAccounts.bank'])
            ->select(['id','nama_supplier','no_telepon','alamat','department_id'])
            ->orderBy('nama_supplier')->get()
            ->map(function($s){
                return [
                    'value' => $s->id,
                    'label' => $s->nama_supplier,
                    'phone' => $s->no_telepon,
                    'address' => $s->alamat,
                    'department_id' => $s->department_id,
                    'bank_accounts' => $s->bankAccounts->map(fn($ba)=>[
                        'bank_name' => $ba->bank?->nama_bank,
                        'account_name' => $ba->nama_rekening,
                        'account_number' => $ba->no_rekening,
                    ])->values(),
                ];
            })->values();

        $perihals = \App\Models\Perihal::query()->active()->select(['id','nama'])->orderBy('nama')->get()
            ->map(fn($p)=>['value'=>$p->id,'label'=>$p->nama])->values();

        // Credit cards for Kartu Kredit metode
        $creditCards = \App\Models\CreditCard::active()->with('bank')
            ->select(['id','bank_id','no_kartu_kredit','nama_pemilik','department_id'])
            ->orderBy('no_kartu_kredit')
            ->get()
            ->map(function($c){
                return [
                    'value' => $c->id,
                    'label' => $c->no_kartu_kredit,
                    'card_number' => $c->no_kartu_kredit,
                    'owner_name' => $c->nama_pemilik,
                    'bank_name' => $c->bank?->nama_bank,
                    'department_id' => $c->department_id,
                ];
            })->values();

        // Giro options from Purchase Orders with metode_pembayaran 'Cek/Giro' and Approved only
        $giroOptions = \App\Models\PurchaseOrder::with(['supplier'])
            ->where('metode_pembayaran', 'Cek/Giro')
            ->where('status', 'Approved')
            ->select(['id','no_po','supplier_id','tanggal_giro','tanggal_cair','department_id','no_giro'])
            ->orderBy('no_po')
            ->get()
            ->map(function($po){
                return [
                    'value' => $po->id,
                    'label' => $po->no_po,
                    'name' => $po->no_po,
                    'no_giro' => $po->no_giro,
                    'tanggal_giro' => $po->tanggal_giro,
                    'tanggal_cair' => $po->tanggal_cair,
                    'department_id' => $po->department_id,
                    'supplier_name' => $po->supplier?->nama_supplier,
                ];
            })->values();

        // PPH options for dropdown
        $pphOptions = \App\Models\Pph::query()->active()
            ->select(['id', 'kode_pph', 'nama_pph', 'tarif_pph'])
            ->orderBy('kode_pph')
            ->get()
            ->map(function($pph) {
                return [
                    'value' => $pph->id,
                    'label' => $pph->nama_pph . ' (' . $pph->tarif_pph . '%)',
                    'kode_pph' => $pph->kode_pph,
                    'nama_pph' => $pph->nama_pph,
                    'tarif_pph' => $pph->tarif_pph,
                ];
            })->values();

        return Inertia::render('payment-voucher/Create', [
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? [],
            'departmentOptions' => $departments,
            'supplierOptions' => $suppliers,
            'perihalOptions' => $perihals,
            'creditCardOptions' => $creditCards,
            'giroOptions' => $giroOptions,
            'pphOptions' => $pphOptions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Auth::user();

        $pv = PaymentVoucher::with([
            'department', 'perihal', 'supplier', 'creator',
            'purchaseOrder' => function ($q) {
                $q->with(['department', 'perihal']);
            },
            'documents'
        ])->findOrFail($id);

        $departments = Department::query()->active()->select(['id','name','alias'])->orderBy('name')->get()
            ->map(fn($d)=>[
                'value'=>$d->id,
                'label'=> $d->name,
                'name'=> $d->name,
                'alias'=>$d->alias,
            ])->values();

        $suppliers = Supplier::active()->with(['bankAccounts.bank'])
            ->select(['id','nama_supplier','no_telepon','alamat','department_id'])
            ->orderBy('nama_supplier')->get()
            ->map(function($s){
                return [
                    'value' => $s->id,
                    'label' => $s->nama_supplier,
                    'phone' => $s->no_telepon,
                    'address' => $s->alamat,
                    'department_id' => $s->department_id,
                    'bank_accounts' => $s->bankAccounts->map(fn($ba)=>[
                        'bank_name' => $ba->bank?->nama_bank,
                        'account_name' => $ba->nama_rekening,
                        'account_number' => $ba->no_rekening,
                    ])->values(),
                ];
            })->values();

        $perihals = \App\Models\Perihal::query()->active()->select(['id','nama'])->orderBy('nama')->get()
            ->map(fn($p)=>['value'=>$p->id,'label'=>$p->nama])->values();

        // Credit cards for Kartu Kredit metode
        $creditCards = \App\Models\CreditCard::active()->with('bank')
            ->select(['id','bank_id','no_kartu_kredit','nama_pemilik','department_id'])
            ->orderBy('no_kartu_kredit')
            ->get()
            ->map(function($c){
                return [
                    'value' => $c->id,
                    'label' => $c->no_kartu_kredit,
                    'card_number' => $c->no_kartu_kredit,
                    'owner_name' => $c->nama_pemilik,
                    'bank_name' => $c->bank?->nama_bank,
                    'department_id' => $c->department_id,
                ];
            })->values();

        // Giro options from Purchase Orders with metode_pembayaran 'Cek/Giro' and Approved only
        $giroOptions = \App\Models\PurchaseOrder::with(['supplier'])
            ->where('metode_pembayaran', 'Cek/Giro')
            ->where('status', 'Approved')
            ->select(['id','no_po','supplier_id','tanggal_giro','tanggal_cair','department_id','no_giro'])
            ->orderBy('no_po')
            ->get()
            ->map(function($po){
                return [
                    'value' => $po->id,
                    'label' => $po->no_po,
                    'name' => $po->no_po,
                    'no_giro' => $po->no_giro,
                    'tanggal_giro' => $po->tanggal_giro,
                    'tanggal_cair' => $po->tanggal_cair,
                    'department_id' => $po->department_id,
                    'supplier_name' => $po->supplier?->nama_supplier,
                ];
            })->values();

        // PPH options for dropdown
        $pphOptions = \App\Models\Pph::query()->active()
            ->select(['id', 'kode_pph', 'nama_pph', 'tarif_pph'])
            ->orderBy('kode_pph')
            ->get()
            ->map(function($pph) {
                return [
                    'value' => $pph->id,
                    'label' => $pph->nama_pph . ' (' . $pph->tarif_pph . '%)',
                    'kode_pph' => $pph->kode_pph,
                    'nama_pph' => $pph->nama_pph,
                    'tarif_pph' => $pph->tarif_pph,
                ];
            })->values();

        return Inertia::render('payment-voucher/Edit', [
            'id' => $pv->id,
            'paymentVoucher' => $pv,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? [],
            'departmentOptions' => $departments,
            'supplierOptions' => $suppliers,
            'perihalOptions' => $perihals,
            'creditCardOptions' => $creditCards,
            'giroOptions' => $giroOptions,
            'pphOptions' => $pphOptions,
        ]);
    }

    /**
     * Update the specified Payment Voucher.
     */
    public function update(Request $request, string $id)
    {
        $pv = PaymentVoucher::findOrFail($id);

        // Optional: restrict which statuses can be edited
        $canEditStatus = in_array($pv->status, ['Draft', 'In Progress']) || ($pv->status === 'Rejected' && Auth::id() === $pv->creator_id);
        if (!$canEditStatus) {
            return response()->json(['error' => 'Payment Voucher tidak dapat diubah pada status saat ini'], 422);
        }

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            // derived from supplier relation
            'department_id' => 'nullable|integer|exists:departments,id',
            'perihal_id' => 'nullable|integer|exists:perihals,id',
            'nominal' => 'nullable|numeric',
            'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            // derived from supplier bank account / credit card relations
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
        ]);

        $pv->fill($data);
        $pv->save();

        // No pivot: single purchase_order_id now used

        return response()->json(['success' => true, 'id' => $pv->id]);
    }

    /**
     * Store draft Payment Voucher and return JSON id (for Create page flow).
     */
    public function storeDraft(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            // derived from supplier relation
            'department_id' => 'nullable|integer|exists:departments,id',
            'perihal_id' => 'nullable|integer|exists:perihals,id',
            'nominal' => 'nullable|numeric',
            'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            // derived from supplier bank account / credit card relations
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
        ]);

        // Default department to current user's first department if not provided
        if (empty($data['department_id'])) {
            $data['department_id'] = $user->departments->first()->id ?? null;
        }

        $pv = new PaymentVoucher();
        $pv->fill($data);
        $pv->status = 'Draft';
        $pv->creator_id = $user->id;
        $pv->save();

        // No pivot: single purchase_order_id now used

        return response()->json(['id' => $pv->id]);
    }

    /**
     * Send selected drafts -> assign no_pv and tanggal, set status to In Progress
     */
    public function send(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $user = Auth::user();
        $now = Carbon::now();

        $pvs = PaymentVoucher::whereIn('id', $request->ids)
            ->where('status', 'Draft')
            ->orderBy('created_at', 'asc')
            ->get();

        // Validate mandatory fields per PV before sending (return JSON for XHR consumers)
        $invalid = [];
        $missingDocs = [];
        foreach ($pvs as $pv) {
            $requiredTypes = ['bukti_transfer_bca','invoice','surat_jalan','efaktur'];
            // Consider a required doc present only if it's active AND has a stored file path
            $hasTypes = $pv->documents()
                ->whereIn('type', $requiredTypes)
                ->where('active', true)
                ->whereNotNull('path')
                ->pluck('type')
                ->all();
            $missingTypes = array_values(array_diff($requiredTypes, $hasTypes));
            if (!empty($missingTypes)) {
                $missingDocs[] = [ 'id' => $pv->id, 'missing_types' => $missingTypes ];
            }

            $missing = [];
            if (!$pv->department_id) $missing[] = 'department';
            if (!$pv->perihal_id) $missing[] = 'perihal';
            if (!$pv->metode_bayar) $missing[] = 'metode_bayar';
            // PO is mandatory in the current flow
            if (!$pv->purchase_order_id) $missing[] = 'purchase_order';
            // nominal now derived from Purchase Order total; no user input required

            // Additional checks for Transfer method only
            if ($pv->metode_bayar === 'Transfer') {
                if (!$pv->supplier_id) $missing[] = 'supplier';
                $supplierPhone = $pv->supplier?->no_telepon;
                $supplierAddress = $pv->supplier?->alamat;
                if (!$supplierPhone) $missing[] = 'supplier_phone';
                if (!$supplierAddress) $missing[] = 'supplier_address';
            }
            if (!empty($missing)) {
                $invalid[] = [ 'id' => $pv->id, 'missing' => $missing ];
            }
        }

        if (!empty($missingDocs) || !empty($invalid)) {
            return response()->json([
                'success' => false,
                'message' => 'Form belum lengkap',
                'missing_documents' => $missingDocs,
                'invalid_fields' => $invalid,
            ], 422);
        }

        foreach ($pvs as $pv) {
            $department = Department::find($pv->department_id);
            $alias = $department?->alias ?? 'DEPT';
            $pv->tanggal = $now->toDateString();
            $candidate = DocumentNumberService::generateNumberForDate('Payment Voucher', $pv->tipe_pv, $pv->department_id, $alias, $now);
            if (!DocumentNumberService::isNumberUnique($candidate)) {
                $candidate = DocumentNumberService::generateNumberForDate('Payment Voucher', $pv->tipe_pv, $pv->department_id, $alias, $now);
            }
            $pv->no_pv = $candidate;
            $pv->status = 'In Progress';
            $pv->save();

            // log
            $pv->logs()->create([
                'user_id' => $user->id,
                'action' => 'sent',
                'note' => 'Payment Voucher dikirim',
            ]);
        }

        return response()->json(['success' => true, 'sent' => $pvs->pluck('id')->all()]);
    }

    /**
     * Display the specified resource (detail/read-only for approved states).
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $pv = PaymentVoucher::with([
            'department', 'perihal', 'supplier', 'creator',
            'purchaseOrders' => function ($q) {
                $q->with(['department', 'perihal']);
            },
            'documents'
        ])->findOrFail($id);

        return Inertia::render('payment-voucher/Detail', [
            'paymentVoucher' => $pv,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? []
        ]);
    }

    /**
     * Display activity log for a PV.
     */
    public function log(string $id)
    {
        $user = Auth::user();
        $logs = \App\Models\PaymentVoucherLog::with('user')
            ->where('payment_voucher_id', $id)
            ->latest()
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'at' => $log->created_at?->toDateTimeString(),
                    'user' => $log->user?->name,
                    'action' => $log->action,
                    'note' => $log->note,
                ];
            });

        return Inertia::render('payment-voucher/Log', [
            'id' => $id,
            'logs' => $logs,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? []
        ]);
    }

    /**
     * Upload a supporting document for PV
     */
    public function uploadDocument(Request $request, string $id)
    {
        $pv = PaymentVoucher::findOrFail($id);
        $request->validate([
            'type' => 'required|in:bukti_transfer_bca,invoice,surat_jalan,efaktur,lainnya',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('file')->store('pv-documents');

        $doc = new PaymentVoucherDocument();
        $doc->payment_voucher_id = $pv->id;
        $doc->type = $request->type;
        $doc->active = true;
        $doc->path = $path;
        $doc->original_name = $request->file('file')->getClientOriginalName();
        $doc->save();

        return back()->with('success', 'Dokumen berhasil diunggah');
    }

    /** Toggle active state of a document */
    public function toggleDocument(string $id, PaymentVoucherDocument $document)
    {
        // Optional: authorize that doc belongs to pv id
        if ((string)$document->payment_voucher_id !== (string)$id) abort(404);
        $document->active = !$document->active;
        $document->save();
        return back();
    }

    /** Explicitly set active state for a document type on a PV (create placeholder if missing) */
    public function setDocumentActive(Request $request, string $id)
    {
        $pv = PaymentVoucher::findOrFail($id);
        $data = $request->validate([
            'type' => 'required|in:bukti_transfer_bca,invoice,surat_jalan,efaktur,lainnya',
            'active' => 'required|boolean',
        ]);

        $doc = PaymentVoucherDocument::where('payment_voucher_id', $pv->id)
            ->where('type', $data['type'])
            ->first();

        if (!$doc) {
            $doc = new PaymentVoucherDocument();
            $doc->payment_voucher_id = $pv->id;
            $doc->type = $data['type'];
        }

        $doc->active = (bool)$data['active'];
        $doc->save();

        return response()->json(['success' => true]);
    }

    /** Download a document */
    public function downloadDocument(PaymentVoucherDocument $document)
    {
        return Storage::download($document->path, $document->original_name ?: 'document.pdf');
    }

    /** Delete a document */
    public function deleteDocument(PaymentVoucherDocument $document)
    {
        if ($document->path) Storage::delete($document->path);
        $document->delete();
        return back();
    }

    /** Download Payment Voucher PDF */
    public function download(string $id)
    {
        try {
            $pv = PaymentVoucher::with(['department','perihal','supplier','purchaseOrder'])
                ->findOrFail($id);

            $total = $pv->purchaseOrder?->total ?? 0;
            $diskon = 0; // taken from form grid if any; adjust when stored
            $dpp = max($total - $diskon, 0);
            $ppn = 0; // computed client-side; set when stored
            $pph = 0; // computed client-side; set when stored
            $grandTotal = $dpp + $ppn + $pph;

            $tanggal = $pv->tanggal
                ? Carbon::parse($pv->tanggal)->locale('id')->translatedFormat('d F Y')
                : Carbon::now()->locale('id')->translatedFormat('d F Y');

            $logoSrc = $this->getBase64Image('images/company-logo.png');
            $signatureSrc = $this->getBase64Image('images/signature.png');
            $approvedSrc = $this->getBase64Image('images/approved.png');

            $pdf = Pdf::loadView('payment_voucher_pdf', [
                'pv' => $pv,
                'tanggal' => $tanggal,
                'total' => $total,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'pph' => $pph,
                'grandTotal' => $grandTotal,
                'logoSrc' => $logoSrc,
                'signatureSrc' => $signatureSrc,
                'approvedSrc' => $approvedSrc,
            ])->setOptions(config('dompdf.options'))
              ->setPaper('a4','portrait');

            $filename = 'PaymentVoucher_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $pv->no_pv ?? 'Draft') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PaymentVoucher Download error', ['pv_id'=>$id,'error'=>$e->getMessage()]);
            return response()->json(['error' => 'Failed to generate PDF: '.$e->getMessage()], 500);
        }
    }

    private function getBase64Image($imagePath)
    {
        $fullPath = public_path($imagePath);
        if (file_exists($fullPath)) {
            $imageData = file_get_contents($fullPath);
            $imageInfo = getimagesizefromstring($imageData);
            $mimeType = $imageInfo['mime'] ?? 'image/png';
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
        return '';
    }

    /**
     * Cancel a draft Payment Voucher.
     */
    public function cancel(string $id)
    {
        $user = Auth::user();
        $pv = PaymentVoucher::where('id', $id)->where('status', 'Draft')->firstOrFail();
        $pv->status = 'Canceled';
        $pv->save();

        $pv->logs()->create([
            'user_id' => $user->id,
            'action' => 'canceled',
            'note' => 'Payment Voucher dibatalkan',
        ]);

        return back()->with('success', 'Payment Voucher berhasil dibatalkan');
    }

    /**
     * Search Approved Purchase Orders for Payment Voucher selection
     */
    public function searchPurchaseOrders(Request $request)
    {
        $search = $request->input('search');
        $metode = $request->input('metode_bayar') ?: $request->input('metode_pembayaran');
        $supplierId = $request->input('supplier_id');
        $giroId = $request->input('giro_id');
        $creditCardId = $request->input('credit_card_id');
        $perPage = (int) $request->input('per_page', 20);

        $query = \App\Models\PurchaseOrder::query()
            ->with(['perihal', 'supplier', 'department', 'bankSupplierAccount.bank', 'bank'])
            ->where('status', 'Approved');

        // Metode-based filters
        if ($metode === 'Transfer' && $supplierId) {
            $query->where('supplier_id', $supplierId);
        } elseif ($metode === 'Cek/Giro' && $giroId) {
            // In PV, giro selection points to a PO id
            $query->where('id', $giroId);
        } elseif ($metode === 'Kartu Kredit' && $creditCardId) {
            // Only POs tied to a specific credit card
            $query->where('credit_card_id', $creditCardId);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($qs) use ($search){
                      $qs->where('nama_supplier', 'like', "%{$search}%");
                  })
                  ->orWhereHas('department', function($qd) use ($search){
                      $qd->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('perihal', function($qp) use ($search){
                      $qp->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        $purchaseOrders = $query->orderByDesc('created_at')->paginate($perPage);

        $data = collect($purchaseOrders->items())->map(function($po){
            return [
                'id' => $po->id,
                'no_po' => $po->no_po,
                'tanggal' => $po->tanggal,
                'supplier_id' => $po->supplier_id,
                'supplier' => [
                    'id' => $po->supplier?->id,
                    'nama_supplier' => $po->supplier?->nama_supplier,
                    'alamat' => $po->supplier?->alamat,
                    'no_telepon' => $po->supplier?->no_telepon,
                    'email' => $po->supplier?->email,
                ],
                'department' => [ 'id' => $po->department?->id, 'name' => $po->department?->name ],
                'perihal' => [ 'id' => $po->perihal?->id, 'nama' => $po->perihal?->nama ],
                'total' => $po->total ?? 0,
                'keterangan' => $po->keterangan,
                'status' => $po->status,
                'metode_pembayaran' => $po->metode_pembayaran,
                'nama_rekening' => $po->nama_rekening,
                'no_rekening' => $po->no_rekening,
                'bankSupplierAccount' => $po->bankSupplierAccount ? [
                    'id' => $po->bankSupplierAccount->id,
                    'nama_rekening' => $po->bankSupplierAccount->nama_rekening,
                    'no_rekening' => $po->bankSupplierAccount->no_rekening,
                    'bank' => $po->bankSupplierAccount->bank ? [
                        'id' => $po->bankSupplierAccount->bank->id,
                        'nama_bank' => $po->bankSupplierAccount->bank->nama_bank,
                    ] : null,
                ] : null,
                'bank' => $po->bank ? [
                    'id' => $po->bank->id,
                    'nama_bank' => $po->bank->nama_bank,
                ] : null,
                // Helpers for client filtering
                'giro_id' => $po->metode_pembayaran === 'Cek/Giro' ? $po->id : null,
                'no_giro' => $po->no_giro,
                'credit_card_id' => $po->credit_card_id,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $purchaseOrders->currentPage(),
            'last_page' => $purchaseOrders->lastPage(),
            'total_count' => $purchaseOrders->total(),
            'filter_info' => [
                'metode_bayar' => $metode,
                'supplier_id' => $supplierId,
                'giro_id' => $giroId,
                'credit_card_id' => $creditCardId,
            ],
        ]);
    }
}

