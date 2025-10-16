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
use Illuminate\Support\Facades\DB;

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
                    // expose creator relation minimal for front-end permission checks
                    'creator' => $pv->creator ? [ 'id' => $pv->creator->id, 'name' => $pv->creator->name ] : null,
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
            'currencyOptions' => [
                ['value' => 'IDR', 'label' => 'IDR'],
                ['value' => 'USD', 'label' => 'USD'],
                ['value' => 'EUR', 'label' => 'EUR'],
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Auth::user();

        // Bypass DepartmentScope to allow permitted users (creator/sender/admin) to edit across departments
        $pv = PaymentVoucher::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with([
                'department', 'perihal', 'supplier', 'creator',
                'purchaseOrder' => function ($q) {
                    $q->with(['department', 'perihal']);
                },
                'documents'
            ])->findOrFail($id);

        // Authorization aligned with update()
        $isAdmin = ($user?->role?->name ?? '') === 'Admin';
        $isSender = $pv->logs()->where('action', 'sent')->where('user_id', $user?->id)->exists();
        $isCreatorEquivalent = (Auth::id() === $pv->creator_id) || $isSender;
        if (!$isCreatorEquivalent && !$isAdmin) {
            abort(403);
        }

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

        // Provide UI alias fields for Manual PVs so the form binds correctly
        $pvPayload = $pv->toArray();
        if (($pv->tipe_pv ?? null) === 'Manual') {
            $pvPayload = array_merge($pvPayload, [
                'supplier_name' => $pv->manual_supplier,
                'supplier_phone' => $pv->manual_no_telepon,
                'supplier_address' => $pv->manual_alamat,
                'supplier_bank_name' => $pv->manual_nama_bank,
                'supplier_account_name' => $pv->manual_nama_pemilik_rekening,
                'supplier_account_number' => $pv->manual_no_rekening,
            ]);
        }

        return Inertia::render('payment-voucher/Edit', [
            'id' => $pv->id,
            'paymentVoucher' => $pvPayload,
            'userRole' => $user->role->name ?? '',
            'userPermissions' => $user->role->permissions ?? [],
            'departmentOptions' => $departments,
            'supplierOptions' => $suppliers,
            'perihalOptions' => $perihals,
            'creditCardOptions' => $creditCards,
            'giroOptions' => $giroOptions,
            'pphOptions' => $pphOptions,
            'currencyOptions' => [
                ['value' => 'IDR', 'label' => 'IDR'],
                ['value' => 'USD', 'label' => 'USD'],
                ['value' => 'EUR', 'label' => 'EUR'],
            ],
        ]);
    }

    /**
     * Update the specified Payment Voucher.
     */
    public function update(Request $request, string $id)
    {
        // Bypass DepartmentScope to allow permitted users (creator/sender/admin) to update across departments
        $pv = PaymentVoucher::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->findOrFail($id);
        $user = Auth::user();

        // Optional: restrict which statuses can be edited
        $isAdmin = ($user?->role?->name ?? '') === 'Admin';
        // Fallback: treat the user who performed 'sent' as creator-equivalent for legacy records
        $isSender = $pv->logs()->where('action', 'sent')->where('user_id', $user?->id)->exists();
        $isCreatorEquivalent = (Auth::id() === $pv->creator_id) || $isSender;
        // Basic authorization: only creator-equivalent or admin may attempt update at all
        if (!$isCreatorEquivalent && !$isAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $canEditStatus = in_array($pv->status, ['Draft', 'In Progress']) || ($pv->status === 'Rejected' && ($isCreatorEquivalent || $isAdmin));
        if (!$canEditStatus) {
            return response()->json(['error' => 'Payment Voucher tidak dapat diubah pada status saat ini'], 422);
        }

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            // derived from supplier relation
            'department_id' => 'nullable|integer|exists:departments,id',
            'perihal_id' => 'nullable|integer|exists:perihals,id',
            'nominal' => 'nullable|numeric|decimal:0,5',
            'currency' => 'nullable|string|in:IDR,USD,EUR',
            'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            // derived from supplier bank account / credit card relations
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'memo_pembayaran_id' => 'nullable|integer|exists:memo_pembayarans,id',
            // Manual fields (accept either manual_* or UI aliases for backward-compat)
            'manual_supplier' => 'nullable|string',
            'manual_no_telepon' => 'nullable|string',
            'manual_alamat' => 'nullable|string',
            'manual_nama_bank' => 'nullable|string',
            'manual_nama_pemilik_rekening' => 'nullable|string',
            'manual_no_rekening' => 'nullable|string',
            'supplier_name' => 'nullable|string',
            'supplier_phone' => 'nullable|string',
            'supplier_address' => 'nullable|string',
            'supplier_bank_name' => 'nullable|string',
            'supplier_account_name' => 'nullable|string',
            'supplier_account_number' => 'nullable|string',
        ]);

        // Map UI aliases -> manual_* when tipe_pv = Manual
        if (($data['tipe_pv'] ?? $pv->tipe_pv) === 'Manual') {
            $data['purchase_order_id'] = null;
            $data['memo_pembayaran_id'] = null;
            $data['manual_supplier'] = $data['manual_supplier'] ?? ($data['supplier_name'] ?? null);
            $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($data['supplier_phone'] ?? null);
            $data['manual_alamat'] = $data['manual_alamat'] ?? ($data['supplier_address'] ?? null);
            $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($data['supplier_bank_name'] ?? null);
            $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($data['supplier_account_name'] ?? null);
            $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($data['supplier_account_number'] ?? null);
        }

        $pv->fill($data);
        // If client requests to save as draft (e.g., from Edit on Rejected), revert status
        if ($request->boolean('save_as_draft')) {
            $pv->status = 'Draft';
            // Clear rejection fields to avoid confusion on revived draft
            $pv->rejected_by = null;
            $pv->rejected_at = null;
            $pv->rejection_reason = null;
        }
        $pv->save();

        // No pivot: single purchase_order_id now used

        // Log draft save or update
        $pv->logs()->create([
            'user_id' => $user->id,
            'action' => $pv->status === 'Draft' ? 'saved_draft' : 'updated',
            'note' => $pv->status === 'Draft' ? 'Draft disimpan' : 'Payment Voucher diperbarui',
        ]);

        return response()->json(['success' => true, 'id' => $pv->id]);
    }

    /**
     * Store draft Payment Voucher and return JSON id (for Create page flow).
     */
    public function storeDraft(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'tipe_pv' => 'nullable|string|in:Reguler,Anggaran,Lainnya,Pajak,Manual',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            // derived from supplier relation
            'department_id' => 'nullable|integer|exists:departments,id',
            'perihal_id' => 'nullable|integer|exists:perihals,id',
            'nominal' => 'nullable|numeric|decimal:0,5',
            'currency' => 'nullable|string|in:IDR,USD,EUR',
            'metode_bayar' => 'nullable|string|in:Transfer,Cek/Giro,Kartu Kredit',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            // derived from supplier bank account / credit card relations
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            // Manual fields (accept either manual_* or UI aliases for backward-compat)
            'manual_supplier' => 'nullable|string',
            'manual_no_telepon' => 'nullable|string',
            'manual_alamat' => 'nullable|string',
            'manual_nama_bank' => 'nullable|string',
            'manual_nama_pemilik_rekening' => 'nullable|string',
            'manual_no_rekening' => 'nullable|string',
            'supplier_name' => 'nullable|string',
            'supplier_phone' => 'nullable|string',
            'supplier_address' => 'nullable|string',
            'supplier_bank_name' => 'nullable|string',
            'supplier_account_name' => 'nullable|string',
            'supplier_account_number' => 'nullable|string',
        ]);

        // Default department to current user's first department if not provided
        if (empty($data['department_id'])) {
            $data['department_id'] = $user->departments->first()->id ?? null;
        }

        // Map UI aliases -> manual_* when tipe_pv = Manual
        if (($data['tipe_pv'] ?? null) === 'Manual') {
            $data['purchase_order_id'] = null;
            $data['memo_pembayaran_id'] = null;
            $data['manual_supplier'] = $data['manual_supplier'] ?? ($data['supplier_name'] ?? null);
            $data['manual_no_telepon'] = $data['manual_no_telepon'] ?? ($data['supplier_phone'] ?? null);
            $data['manual_alamat'] = $data['manual_alamat'] ?? ($data['supplier_address'] ?? null);
            $data['manual_nama_bank'] = $data['manual_nama_bank'] ?? ($data['supplier_bank_name'] ?? null);
            $data['manual_nama_pemilik_rekening'] = $data['manual_nama_pemilik_rekening'] ?? ($data['supplier_account_name'] ?? null);
            $data['manual_no_rekening'] = $data['manual_no_rekening'] ?? ($data['supplier_account_number'] ?? null);
        }

        $pv = new PaymentVoucher();
        $pv->fill($data);
        $pv->status = 'Draft';
        $pv->creator_id = $user->id;
        $pv->save();

        // No pivot: single purchase_order_id now used

        // Log draft creation
        $pv->logs()->create([
            'user_id' => $user->id,
            'action' => 'saved_draft',
            'note' => 'Draft dibuat',
        ]);

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
            ->whereIn('status', ['Draft', 'Rejected'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Validate mandatory fields per PV before sending (return JSON for XHR consumers)
        $invalid = [];
        $missingDocs = [];
        foreach ($pvs as $pv) {
            // Define universe of standard types (exclude 'lainnya' which is always optional)
            $standardTypes = ['bukti_transfer_bca','invoice','surat_jalan','efaktur'];
            // Only types that are currently marked active by the user are considered required
            $activeRequiredTypes = $pv->documents()
                ->whereIn('type', $standardTypes)
                ->where('active', true)
                ->pluck('type')
                ->unique()
                ->all();

            // Among the active-required types, which ones have successfully uploaded files?
            $hasTypes = $pv->documents()
                ->whereIn('type', $activeRequiredTypes)
                ->where('active', true)
                ->whereNotNull('path')
                ->pluck('type')
                ->all();

            $missingTypes = array_values(array_diff($activeRequiredTypes, $hasTypes));
            if (!empty($missingTypes)) {
                $missingDocs[] = [ 'id' => $pv->id, 'missing_types' => $missingTypes ];
            }

            $missing = [];
            if (!$pv->department_id) $missing[] = 'department';
            if (!$pv->perihal_id) $missing[] = 'perihal';
            if (!$pv->metode_bayar) $missing[] = 'metode_bayar';
            // For tipe Lainnya, Memo Pembayaran is mandatory; for other non-Manual, PO is mandatory
            if ($pv->tipe_pv === 'Lainnya') {
                if (!$pv->memo_pembayaran_id) $missing[] = 'memo_pembayaran';
            } elseif ($pv->tipe_pv !== 'Manual') {
                if (!$pv->purchase_order_id) $missing[] = 'purchase_order';
            }
            // For Manual type, require nominal and currency
            if ($pv->tipe_pv === 'Manual') {
                if (empty($pv->nominal) || $pv->nominal <= 0) $missing[] = 'nominal';
                if (empty($pv->currency)) $missing[] = 'currency';
            }
            // nominal now derived from Purchase Order total for non-Manual; manual provides it

            // Additional checks for Transfer method only
            if ($pv->metode_bayar === 'Transfer') {
                if ($pv->tipe_pv === 'Manual') {
                    if (!$pv->manual_supplier) $missing[] = 'manual_supplier';
                    if (!$pv->manual_no_telepon) $missing[] = 'manual_no_telepon';
                    if (!$pv->manual_alamat) $missing[] = 'manual_alamat';
                    if (!$pv->manual_nama_bank) $missing[] = 'manual_nama_bank';
                    if (!$pv->manual_nama_pemilik_rekening) $missing[] = 'manual_nama_pemilik_rekening';
                    if (!$pv->manual_no_rekening) $missing[] = 'manual_no_rekening';
                } else {
                    if (!$pv->supplier_id) $missing[] = 'supplier';
                    $supplierPhone = $pv->supplier?->no_telepon;
                    $supplierAddress = $pv->supplier?->alamat;
                    if (!$supplierPhone) $missing[] = 'supplier_phone';
                    if (!$supplierAddress) $missing[] = 'supplier_address';
                }
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
            // Only generate no_pv if not already assigned (resend keeps existing number)
            if (empty($pv->no_pv)) {
                $candidate = DocumentNumberService::generateNumberForDate('Payment Voucher', $pv->tipe_pv, $pv->department_id, $alias, $now);
                if (!DocumentNumberService::isNumberUnique($candidate)) {
                    $candidate = DocumentNumberService::generateNumberForDate('Payment Voucher', $pv->tipe_pv, $pv->department_id, $alias, $now);
                }
                $pv->no_pv = $candidate;
            }
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
            'verifier', 'approver', 'rejecter',
            'purchaseOrder' => function ($q) {
                $q->with([
                    'department', 'perihal', 'supplier', 'pph', 'termin',
                    'creditCard.bank', 'bankSupplierAccount.bank'
                ]);
            },
            'memoPembayaran' => function ($q) {
                $q->with(['perihal', 'department']);
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

        // Return an Inertia-friendly redirect instead of plain JSON
        return back(303);
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
        $departmentId = $request->input('department_id');
        $giroId = $request->input('giro_id');
        $creditCardId = $request->input('credit_card_id');
        $tipePv = $request->input('tipe_pv');
        $perPage = (int) $request->input('per_page', 20);
        $currentPvId = $request->input('current_pv_id');

        $query = \App\Models\PurchaseOrder::query()
            ->with(['perihal', 'supplier', 'department', 'bankSupplierAccount.bank', 'bank', 'creditCard.bank'])
            ->where('status', 'Approved');

        // Exclude POs already used by existing Payment Vouchers (allow current PV when editing)
        $query->whereNotExists(function($q) use ($currentPvId) {
            $q->select(DB::raw(1))
              ->from('payment_vouchers as pv')
              ->whereColumn('pv.purchase_order_id', 'purchase_orders.id')
              // Allow currently edited PV to keep its PO in the list
              ->when($currentPvId, function($qq) use ($currentPvId) {
                  $qq->where('pv.id', '!=', $currentPvId);
              })
              // Exclude POs used by ANY PV that is not canceled
              // Only when PV is 'Canceled' the PO becomes available again
              ->where('pv.status', '!=', 'Canceled');
        });

        // Filter by tipe_pv -> map to purchase_orders.tipe_po
        if (in_array($tipePv, ['Reguler','Anggaran','Lainnya'], true)) {
            $query->where('tipe_po', $tipePv);
        }

        // Always filter by department if provided
        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        // Always filter by supplier if provided
        if (!empty($supplierId)) {
            $query->where('supplier_id', $supplierId);
        }

        // Metode-based filters
        if ($metode === 'Cek/Giro' && $giroId) {
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
                'no_invoice' => $po->no_invoice,
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
                'credit_card' => $po->creditCard ? [
                    'id' => $po->creditCard->id,
                    'no_kartu_kredit' => $po->creditCard->no_kartu_kredit,
                    'nama_pemilik' => $po->creditCard->nama_pemilik,
                    'bank_name' => $po->creditCard->bank?->nama_bank,
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

    /**
     * Search Approved Memo Pembayaran for Payment Voucher (tipe Lainnya)
     */
    public function searchMemos(Request $request)
    {
        $search = $request->input('search');
        $metode = $request->input('metode_bayar') ?: $request->input('metode_pembayaran');
        $supplierId = $request->input('supplier_id');
        $departmentId = $request->input('department_id');
        $perPage = (int) $request->input('per_page', 20);

        $query = \App\Models\MemoPembayaran::query()
            ->with(['department', 'supplier', 'purchaseOrder.perihal', 'termin'])
            ->where('status', 'Approved');

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }
        if (!empty($supplierId)) {
            $query->where(function($q) use ($supplierId){
                $q->where('supplier_id', $supplierId)
                  ->orWhereHas('purchaseOrder', function($qp) use ($supplierId){
                      $qp->where('supplier_id', $supplierId);
                  });
            });
        }
        if (!empty($metode)) {
            $query->where('metode_pembayaran', $metode);
        }
        if (!empty($search)) {
            $query->where(function($q) use ($search){
                $q->where('no_mb', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($qp) use ($search){
                      $qp->where('no_po', 'like', "%{$search}%");
                  })
                  ->orWhereHas('supplier', function($qs) use ($search){
                      $qs->where('nama_supplier', 'like', "%{$search}%");
                  })
                  ->orWhereHas('department', function($qd) use ($search){
                      $qd->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $memos = $query->orderByDesc('created_at')->paginate($perPage);

        $data = collect($memos->items())->map(function($m){
            $po = $m->purchaseOrder;
            // Compute which installment number this memo represents within the same termin
            $terminKe = null;
            if ($m->termin_id) {
                try {
                    $terminKe = \App\Models\MemoPembayaran::where('termin_id', $m->termin_id)
                        ->where('created_at', '<=', $m->created_at)
                        ->orderBy('created_at')
                        ->count();
                } catch (\Throwable $e) {}
            }
            return [
                'id' => $m->id,
                'no_memo' => $m->no_mb,
                'tanggal' => $m->tanggal,
                'status' => $m->status,
                'total' => $m->total,
                'nominal' => $m->total,
                'keterangan' => $m->keterangan,
                'metode_pembayaran' => $m->metode_pembayaran,
                'department' => $m->department ? [ 'id' => $m->department->id, 'name' => $m->department->name ] : null,
                'supplier' => $m->supplier ? [
                    'id' => $m->supplier->id,
                    'nama_supplier' => $m->supplier->nama_supplier,
                    'alamat' => $m->supplier->alamat,
                    'no_telepon' => $m->supplier->no_telepon,
                ] : null,
                'perihal' => $po && $po->perihal ? [ 'id' => $po->perihal->id, 'nama' => $po->perihal->nama ] : null,
                'purchase_order' => $po ? [ 'id' => $po->id, 'no_po' => $po->no_po ] : null,
                'termin' => $m->termin ? [
                    'jumlah_termin' => $m->termin->jumlah_termin,
                    'jumlah_termin_dibuat' => $m->termin->jumlah_termin_dibuat,
                    'total_cicilan' => $m->termin->total_cicilan,
                    'sisa_pembayaran' => $m->termin->sisa_pembayaran,
                    'no_referensi' => $m->termin->no_referensi ?? null,
                    'termin_ke' => $terminKe,
                ] : ($terminKe ? ['termin_ke' => $terminKe] : null),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $memos->currentPage(),
            'last_page' => $memos->lastPage(),
            'total_count' => $memos->total(),
        ]);
    }
}

