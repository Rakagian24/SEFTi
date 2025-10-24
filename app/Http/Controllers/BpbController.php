<?php

namespace App\Http\Controllers;

use App\Models\Bpb;
use App\Models\BpbLog;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\BpbItem;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BpbController extends Controller
{
    public function create()
    {
        // Fetch latest 5 Approved POs with allowed perihal for dropdown
        $latestPOs = PurchaseOrder::with(['perihal:id,nama'])
            ->where('status', 'Approved')
            ->whereHas('perihal', function($q){
                $q->whereIn(DB::raw('LOWER(nama)'), [
                    'permintaan pembayaran barang',
                    'permintaan pembayaran barang/jasa',
                ]);
            })
            ->orderBy('id','desc')
            ->take(5)
            ->get(['id','no_po','status','perihal_id']);
        $suppliers = Supplier::active()->orderBy('nama_supplier')->get(['id','nama_supplier','alamat','no_telepon','department_id']);
        // Department options scoped to logged-in user's departments (Admin sees all)
        $user = Auth::user();
        $departmentOptions = (function() use ($user) {
            $q = Department::query()->active()->select(['id','name'])->orderBy('name');
            $roleName = strtolower(optional($user->role)->name ?? '');
            if ($roleName !== 'admin') {
                $q->whereHas('users', function($uq) use ($user) {
                    $uq->where('users.id', $user->id);
                });
            }
            return $q->get()->map(fn($d)=>['value'=>$d->id,'label'=>$d->name])->values();
        })();
        return Inertia::render('bpb/Create', [
            'latestPOs' => $latestPOs,
            'suppliers' => $suppliers,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    // Return Approved POs with allowed perihal that still have remaining qty (>0) for given supplier/department
    public function eligiblePurchaseOrders(Request $request)
    {
        $request->validate([
            'supplier_id' => ['required','exists:suppliers,id'],
            'department_id' => ['nullable','exists:departments,id'],
            'search' => ['nullable','string'],
            'per_page' => ['nullable','integer','min:1','max:100'],
        ]);

        $supplierId = (int) $request->input('supplier_id');
        $deptId = $request->input('department_id');
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) ($request->input('per_page', 20));

        $poQuery = PurchaseOrder::query()
            ->with([
                'items:id,purchase_order_id,qty',
                'perihal:id,nama',
            ])
            ->where('status', 'Approved')
            ->where('supplier_id', $supplierId)
            ->whereHas('perihal', function($q){
                $q->whereIn(DB::raw('LOWER(nama)'), [
                    'permintaan pembayaran barang',
                    'permintaan pembayaran barang/jasa',
                ]);
            });
        // Exclude POs that are already used by any BPB with status other than Canceled
        // Requirement: PO yang sudah dipakai tidak muncul lagi di option Purchase Order kecuali status BPB-nya Canceled
        $poQuery->whereNotExists(function($q){
            $q->select(DB::raw(1))
              ->from('bpbs')
              ->whereColumn('bpbs.purchase_order_id', 'purchase_orders.id')
              ->where('bpbs.status', '<>', 'Canceled');
        });
        if (!empty($deptId)) {
            $poQuery->where('department_id', $deptId);
        }
        if ($search !== '') {
            $poQuery->where('no_po', 'like', "%$search%");
        }

        $pos = $poQuery->orderByDesc('id')->limit(200)->get([
            'id',
            'no_po',
            'status',
            'supplier_id',
            'department_id',
            'perihal_id',
            'tanggal',
            'no_invoice',
            'total',
            'keterangan',
        ]);

        if ($pos->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $poIds = $pos->pluck('id')->all();
        $received = BpbItem::query()
            ->selectRaw('bpbs.purchase_order_id as po_id, bpb_items.purchase_order_item_id as poi_id, COALESCE(SUM(bpb_items.qty),0) as received_qty')
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->whereIn('bpbs.purchase_order_id', $poIds)
            ->where('bpbs.status', 'Approved')
            ->whereNotNull('bpb_items.purchase_order_item_id')
            ->groupBy('bpbs.purchase_order_id', 'bpb_items.purchase_order_item_id')
            ->get();

        $receivedByPoi = [];
        foreach ($received as $row) {
            $receivedByPoi[(int)$row->poi_id] = (float)$row->received_qty;
        }

        $eligible = $pos->filter(function($po) use ($receivedByPoi) {
            foreach ($po->items as $it) {
                $poQty = (float) $it->qty;
                $rcv = (float) ($receivedByPoi[$it->id] ?? 0);
                if ($poQty - $rcv > 0.000001) {
                    return true;
                }
            }
            return false;
        })->map(function($po){
            return [
                'id' => $po->id,
                'no_po' => $po->no_po,
                'tanggal' => $po->tanggal,
                'no_invoice' => $po->no_invoice,
                'total' => $po->total,
                'keterangan' => $po->keterangan,
                'perihal' => $po->perihal ? [
                    'id' => $po->perihal->id,
                    'nama' => $po->perihal->nama,
                ] : null,
            ];
        })->values();

        // Simple pagination-like trimming
        $data = $eligible->take($perPage)->all();
        return response()->json(['data' => $data]);
    }

    public function edit(Bpb $bpb)
    {
        // Disallow editing non Draft/Rejected, and enforce creator/Admin
        $user = Auth::user();
        $isAdmin = strtolower(optional($user->role)->name ?? '') === 'admin';
        if (!in_array($bpb->status, ['Draft', 'Rejected']) || (!$isAdmin && (int)$bpb->created_by !== (int)$user->id)) {
            return redirect()->route('bpb.index')->with('error', 'Dokumen tidak dapat diubah');
        }

        $latestPOs = PurchaseOrder::with(['perihal:id,nama'])
            ->where('status', 'Approved')
            ->whereHas('perihal', function($q){
                $q->whereIn(DB::raw('LOWER(nama)'), [
                    'permintaan pembayaran barang',
                    'permintaan pembayaran barang/jasa',
                ]);
            })
            ->orderBy('id','desc')
            ->take(5)
            ->get(['id','no_po','status','perihal_id']);
        $suppliers = Supplier::active()->orderBy('nama_supplier')->get(['id','nama_supplier','alamat','no_telepon','department_id']);
        // Department options scoped to logged-in user's departments (Admin sees all)
        $user = Auth::user();
        $departmentOptions = (function() use ($user) {
            $q = Department::query()->active()->select(['id','name'])->orderBy('name');
            $roleName = strtolower(optional($user->role)->name ?? '');
            if ($roleName !== 'admin') {
                $q->whereHas('users', function($uq) use ($user) {
                    $uq->where('users.id', $user->id);
                });
            }
            return $q->get()->map(fn($d)=>['value'=>$d->id,'label'=>$d->name])->values();
        })();
        return Inertia::render('bpb/Edit', [
            'bpb' => $bpb->load(['items','supplier','purchaseOrder','department']),
            'latestPOs' => $latestPOs,
            'suppliers' => $suppliers,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    public function storeDraft(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required','exists:departments,id'],
            'purchase_order_id' => ['required','exists:purchase_orders,id'],
            'supplier_id' => ['required','exists:suppliers,id'],
            'note' => ['nullable','string'],
            'keterangan' => ['nullable','string'],
            'diskon' => ['nullable','numeric'],
            'use_ppn' => ['nullable','boolean'],
            'ppn_rate' => ['nullable','numeric'],
            'use_pph' => ['nullable','boolean'],
            'pph_rate' => ['nullable','numeric'],
            'items' => ['required','array','min:1'],
            'items.*.nama_barang' => ['required','string'],
            'items.*.qty' => ['required','numeric','min:0'],
            'items.*.satuan' => ['required','string'],
            'items.*.harga' => ['required','numeric'],
            'items.*.purchase_order_item_id' => ['required','exists:purchase_order_items,id'],
        ]);

        $userId = Auth::id();

        $bpb = null;

        DB::transaction(function () use (&$bpb, $validated, $userId, $request) {
            $items = $validated['items'];
            // Validate remaining quantities against Approved BPBs only
            $poId = (int) $validated['purchase_order_id'];
            // Lock purchase_order_items rows for this PO
            $poItemRows = DB::table('purchase_order_items')->where('purchase_order_id', $poId)->lockForUpdate()->get(['id','qty']);
            $poQtyById = $poItemRows->pluck('qty','id');
            $receivedRows = DB::table('bpb_items')
                ->join('bpbs','bpbs.id','=','bpb_items.bpb_id')
                ->where('bpbs.purchase_order_id', $poId)
                ->where('bpbs.status','Approved')
                ->whereNotNull('bpb_items.purchase_order_item_id')
                ->selectRaw('bpb_items.purchase_order_item_id as poi_id, COALESCE(SUM(bpb_items.qty),0) as received_qty')
                ->groupBy('bpb_items.purchase_order_item_id')
                ->lockForUpdate()
                ->get();
            $receivedByPoi = $receivedRows->pluck('received_qty','poi_id');

            foreach ($items as $it) {
                $poi = (int) $it['purchase_order_item_id'];
                $poQty = (float) ($poQtyById[$poi] ?? 0);
                $received = (float) ($receivedByPoi[$poi] ?? 0);
                $remaining = max(0, $poQty - $received);
                $qty = (float) $it['qty'];
                if ($qty < 0 || $qty - $remaining > 0.000001) {
                    abort(422, "Qty untuk item PO #$poi melebihi sisa yang diperbolehkan");
                }
            }

            $subtotal = collect($items)->reduce(function ($c, $i) { return $c + ($i['qty'] * $i['harga']); }, 0);
            $diskon = (float)($validated['diskon'] ?? 0);
            $dpp = max(0, $subtotal - $diskon);
            $ppnRate = (float)($validated['use_ppn'] ? ($validated['ppn_rate'] ?? 11) : 0);
            $ppn = $ppnRate > 0 ? $dpp * ($ppnRate/100) : 0;
            $pphRate = (float)($validated['use_pph'] ? ($validated['pph_rate'] ?? 0) : 0);
            $pph = $pphRate > 0 ? $dpp * ($pphRate/100) : 0;
            $grandTotal = $dpp + $ppn + $pph;

            $bpb = Bpb::create([
                'department_id' => $validated['department_id'],
                'purchase_order_id' => $validated['purchase_order_id'],
                'supplier_id' => $validated['supplier_id'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => 'Draft',
                'created_by' => $userId,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'dpp' => $dpp,
                'ppn' => $ppn,
                'pph' => $pph,
                'grand_total' => $grandTotal,
            ]);

            foreach ($items as $it) {
                BpbItem::create([
                    'bpb_id' => $bpb->id,
                    'purchase_order_item_id' => $it['purchase_order_item_id'],
                    'nama_barang' => $it['nama_barang'],
                    'qty' => $it['qty'],
                    'satuan' => $it['satuan'],
                    'harga' => $it['harga'],
                ]);
            }

            // Log draft creation
            BpbLog::create([
                'bpb_id' => $bpb->id,
                'user_id' => $userId,
                'action' => 'draft',
                'description' => 'Menyimpan BPB sebagai draft',
                'ip_address' => $request->ip(),
            ]);
        });

        return response()->json(['message' => 'Berhasil menyimpan draft BPB', 'bpb' => $bpb]);
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');

        // Build base query
        // - Admin/Kabag/Direksi: bypass DepartmentScope, see all
        // - Staff Toko & Staff Digital Marketing: only documents they created (currently disabled below)
        // - Other roles (incl. Staff Akunting & Finance, Kepala Toko): constrained by DepartmentScope
        if (in_array($userRole, ['admin','kabag','direksi'], true)) {
            $query = Bpb::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['department', 'purchaseOrder', 'purchaseOrder.perihal', 'paymentVoucher', 'supplier', 'creator']);
        // elseif (in_array($userRole, ['staff toko','staff digital marketing'], true)) {
        //     // Staff Toko & Staff Digital Marketing: only see documents they created
        //     $query = Bpb::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
        //         ->with(['department', 'purchaseOrder', 'purchaseOrder.perihal', 'paymentVoucher', 'supplier', 'creator'])
        //         ->where('created_by', $user->id);
        } else {
            // Other roles: rely on DepartmentScope (multi-department or All)
            $query = Bpb::query()
                ->with(['department', 'purchaseOrder', 'purchaseOrder.perihal', 'paymentVoucher', 'supplier', 'creator']);
        }

        // Default current month
        if (!$request->filled('tanggal_start') && !$request->filled('tanggal_end')) {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        if ($request->filled('tanggal_start')) {
            $query->whereDate('created_at', '>=', $request->input('tanggal_start'));
        }
        if ($request->filled('tanggal_end')) {
            $query->whereDate('created_at', '<=', $request->input('tanggal_end'));
        }
        if ($request->filled('no_bpb')) {
            $query->where('no_bpb', 'like', '%' . $request->input('no_bpb') . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }
        if ($request->filled('no_po')) {
            $no = trim($request->input('no_po'));
            $query->whereHas('purchaseOrder', function($q) use ($no){
                $q->where('no_po', 'like', "%$no%");
            });
        }
        if ($request->filled('no_pv')) {
            $no = trim($request->input('no_pv'));
            $query->whereHas('paymentVoucher', function($q) use ($no){
                $q->where('no_pv', 'like', "%$no%");
            });
        }
        if ($request->filled('po_perihal')) {
            $text = trim($request->input('po_perihal'));
            $query->whereHas('purchaseOrder.perihal', function($q) use ($text){
                $q->where('nama', 'like', "%$text%");
            });
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('no_bpb', 'like', "%$search%")
                  ->orWhereHas('purchaseOrder', fn($po) => $po->where('no_po', 'like', "%$search%"))
                  ->orWhereHas('paymentVoucher', fn($pv) => $pv->where('no_pv', 'like', "%$search%"))
                  ->orWhereHas('supplier', fn($s) => $s->where('nama_supplier', 'like', "%$search%"));
            });
        }

        $perPage = (int) ($request->input('per_page', 10));
        $bpbs = $query->latest()->paginate($perPage)->withQueryString();

        // Options for filters
        // Department options scoped to the logged-in user's departments (Admin sees all)
        $departmentOptions = (function() use ($user) {
            $q = Department::query()->active()->select(['id','name'])->orderBy('name');
            $roleName = strtolower(optional($user->role)->name ?? '');
            if ($roleName !== 'admin') {
                $q->whereHas('users', function($uq) use ($user) {
                    $uq->where('users.id', $user->id);
                });
            }
            return $q->get()->map(function($d){
                return [
                    'id' => $d->id,
                    'name' => $d->name,
                    'label' => $d->name,
                    'value' => (string)$d->id,
                ];
            })->values();
        })();

        // Supplier options filtered by selected department when present
        $supplierQuery = Supplier::active()->orderBy('nama_supplier');
        if ($request->filled('department_id')) {
            $supplierQuery->where('department_id', $request->input('department_id'));
        }
        $supplierOptions = $supplierQuery->get(['id', 'nama_supplier'])->map(function($s){
            return [
                'id' => $s->id,
                'name' => $s->nama_supplier,
                'label' => $s->nama_supplier,
                'value' => (string)$s->id,
            ];
        })->values();

        return Inertia::render('bpb/Index', [
            'bpbs' => $bpbs,
            'departmentOptions' => $departmentOptions,
            'supplierOptions' => $supplierOptions,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = $user->id;
        $validated['status'] = 'Draft';

        $bpb = Bpb::create($validated);
        return response()->json(['message' => 'BPB dibuat', 'bpb' => $bpb]);
    }

    public function update(Request $request, Bpb $bpb)
    {
        $user = Auth::user();
        $isAdmin = strtolower(optional($user->role)->name ?? '') === 'admin';
        if (!in_array($bpb->status, ['Draft', 'Rejected']) || (!$isAdmin && (int)$bpb->created_by !== (int)$user->id)) {
            return response()->json(['error' => 'Dokumen tidak dapat diubah'], 422);
        }

        $validated = $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = $user->id;
        $bpb->update($validated);

        // Log update
        BpbLog::create([
            'bpb_id' => $bpb->id,
            'user_id' => $user->id,
            'action' => 'updated',
            'description' => 'Memperbarui BPB',
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'BPB diperbarui', 'bpb' => $bpb->fresh()]);
    }

    public function send(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['error' => 'Tidak ada dokumen dipilih'], 422);
        }

        $user = Auth::user();

        DB::transaction(function () use ($ids, $user, $request) {
            $bpbs = Bpb::whereIn('id', $ids)->lockForUpdate()->get();
            foreach ($bpbs as $bpb) {
                if (!in_array($bpb->status, ['Draft', 'Rejected'])) {
                    continue;
                }
                $isAdmin = strtolower(optional($user->role)->name ?? '') === 'admin';
                if (!$isAdmin && (int)$bpb->created_by !== (int)$user->id) {
                    // skip if not allowed
                    continue;
                }

                // Generate tanggal and number on send (use document date for numbering)
                if (!$bpb->tanggal) {
                    $bpb->tanggal = now();
                }
                if (!$bpb->no_bpb) {
                    $department = Department::find($bpb->department_id);
                    $alias = $department?->alias ?? ($department?->name ?? 'DEPT');
                    $bpb->no_bpb = DocumentNumberService::generateNumberForDate(
                        'Bukti Penerimaan Barang',
                        null,
                        $bpb->department_id,
                        $alias,
                        \Carbon\Carbon::parse($bpb->tanggal)
                    );
                }

                // If Kepala Toko or Kabag sends, auto-approve
                $roleName = strtolower(optional($user->role)->name ?? '');
                if (in_array($roleName, ['kepala toko', 'kabag'], true)) {
                    $bpb->status = 'Approved';
                    $bpb->approved_by = $user->id;
                    $bpb->approved_at = now();
                    $bpb->save();

                    // Log sent
                    BpbLog::create([
                        'bpb_id' => $bpb->id,
                        'user_id' => $user->id,
                        'action' => 'sent',
                        'description' => 'Mengirim BPB',
                        'ip_address' => $request->ip(),
                    ]);
                    // Log auto-approval
                    BpbLog::create([
                        'bpb_id' => $bpb->id,
                        'user_id' => $user->id,
                        'action' => 'approved',
                        'description' => 'BPB auto-approved oleh pengirim (Kepala Toko/Kabag)',
                        'ip_address' => $request->ip(),
                    ]);
                } else {
                    $bpb->status = 'In Progress';
                    $bpb->save();

                    // Log send
                    BpbLog::create([
                        'bpb_id' => $bpb->id,
                        'user_id' => $user->id,
                        'action' => 'sent',
                        'description' => 'Mengirim BPB ke proses selanjutnya',
                        'ip_address' => $request->ip(),
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Dokumen dikirim']);
    }

    public function cancel(Bpb $bpb)
    {
        $user = Auth::user();
        $isAdmin = strtolower(optional($user->role)->name ?? '') === 'admin';
        if (!in_array($bpb->status, ['Draft', 'Rejected']) || (!$isAdmin && (int)$bpb->created_by !== (int)$user->id)) {
            return response()->json(['error' => 'Tidak dapat dibatalkan'], 422);
        }

        $bpb->update([
            'status' => 'Canceled',
            'canceled_by' => $user->id,
            'canceled_at' => now(),
        ]);

        // Log cancel
        BpbLog::create([
            'bpb_id' => $bpb->id,
            'user_id' => $user->id,
            'action' => 'canceled',
            'description' => 'Membatalkan BPB',
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['message' => 'Dokumen dibatalkan']);
    }

    public function show(Bpb $bpb)
    {
        // $user = Auth::user();
        // $userRole = strtolower(optional($user->role)->name ?? '');
        // if (in_array($userRole, ['staff toko','staff digital marketing'], true) && (int)$bpb->created_by !== (int)$user->id) {
        //     abort(403, 'Unauthorized');
        // }
        return response()->json($bpb->load(['department', 'purchaseOrder', 'paymentVoucher', 'supplier', 'creator']));
    }

    public function detail(Bpb $bpb)
    {
        // $user = Auth::user();
        // $userRole = strtolower(optional($user->role)->name ?? '');
        // if (in_array($userRole, ['staff toko','staff digital marketing'], true) && (int)$bpb->created_by !== (int)$user->id) {
        //     abort(403, 'Unauthorized');
        // }
        return Inertia::render('bpb/Detail', [
            'bpb' => $bpb->load(['items','department','purchaseOrder','paymentVoucher','supplier','creator']),
        ]);
    }

    public function downloadPdf(Bpb $bpb)
    {
        if ($bpb->status === 'Canceled') {
            abort(403, 'Dokumen dibatalkan dan tidak dapat diunduh');
        }
        $bpb->load(['department','purchaseOrder.perihal','paymentVoucher','supplier','creator','items']);

        // Build assets as base64 (inline for DomPDF)
        $logoSrc = $this->getBase64Image('images/company-logo.png')
            ?? $this->getBase64Image('images/company-logo.jpg')
            ?? $this->getBase64Image('images/company-logo.jpeg');
        $signatureSrc = $this->getBase64Image('images/signature.png');
        $approvedSrc = $this->getBase64Image('images/approved.png');

        // Totals (fallback to stored fields if present)
        $subtotal = (float) ($bpb->subtotal ?? $bpb->items->reduce(fn($c,$i)=>$c + (($i->qty ?? 0) * ($i->harga ?? 0)), 0));
        $diskon = (float) ($bpb->diskon ?? 0);
        $dpp = max(0, (float) ($bpb->dpp ?? ($subtotal - $diskon)));
        $ppn = (float) ($bpb->ppn ?? 0);
        $pph = (float) ($bpb->pph ?? 0);
        $grandTotal = (float) ($bpb->grand_total ?? ($dpp + $ppn + $pph));

        // Render
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bpb_pdf', [
            'bpb' => $bpb,
            'logoSrc' => $logoSrc,
            'signatureSrc' => $signatureSrc,
            'approvedSrc' => $approvedSrc,
            'subtotal' => $subtotal,
            'diskon' => $diskon,
            'dpp' => $dpp,
            'ppn' => $ppn,
            'pph' => $pph,
            'grandTotal' => $grandTotal,
        ]);
        $filename = ($bpb->no_bpb ?: 'BPB') . '.pdf';
        return $pdf->download($filename);
    }

    private function getBase64Image($imagePath)
    {
        $fullPath = public_path($imagePath);
        if (file_exists($fullPath)) {
            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
            $data = file_get_contents($fullPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return null;
    }

    public function log(Bpb $bpb, Request $request)
    {
        $bpb = \App\Models\Bpb::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->findOrFail($bpb->id);

        // $user = Auth::user();
        // $userRole = strtolower(optional($user->role)->name ?? '');
        // if (in_array($userRole, ['staff toko','staff digital marketing'], true) && (int)$bpb->created_by !== (int)$user->id) {
        //     abort(403, 'Unauthorized');
        // }

        $logs = BpbLog::with(['user.department','user.role'])
            ->where('bpb_id', $bpb->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        return Inertia::render('bpb/Log', [
            'bpb' => $bpb,
            'logs' => $logs,
            'filters' => $request->only(['search','action','date','per_page']),
        ]);
    }
}


