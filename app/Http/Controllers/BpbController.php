<?php

namespace App\Http\Controllers;

use App\Models\Bpb;
use App\Models\BpbLog;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\PaymentVoucher;
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
        // Fetch latest 5 Approved POs for BPB (Reguler + Perihal "Permintaan Pembayaran Barang" only)
        $latestPOs = PurchaseOrder::with(['perihal:id,nama'])
            ->where('status', 'Approved')
            ->where('tipe_po', 'Reguler')
            ->whereHas('perihal', function($q){
                $q->where(DB::raw('LOWER(nama)'), 'permintaan pembayaran barang');
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
            $hasAllDept = (optional($user->departments) ?? collect())->contains('name', 'All');
            if ($roleName !== 'admin' && !$hasAllDept) {
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
            'defaultDepartmentId' => (function() use ($user) {
                $userDepts = (optional($user->departments) ?? collect())->reject(function($d){ return strtolower($d->name ?? '') === 'all'; });
                return $userDepts->count() === 1 ? ($userDepts->first()->id ?? null) : null;
            })(),
        ]);
    }

    // Return Approved POs with allowed perihal that still have remaining qty (>0) for given supplier/department
    public function eligiblePurchaseOrders(Request $request)
    {
        $request->validate([
            // For Transfer: supplier_id is used; For Kredit: credit_card_id is used
            'supplier_id' => ['required_without:credit_card_id','nullable','exists:suppliers,id'],
            'credit_card_id' => ['required_without:supplier_id','nullable','exists:credit_cards,id'],
            'department_id' => ['nullable','exists:departments,id'],
            'search' => ['nullable','string'],
            'per_page' => ['nullable','integer','min:1','max:100'],
        ]);

        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;
        $creditCardId = $request->filled('credit_card_id') ? (int) $request->input('credit_card_id') : null;
        $deptId = $request->input('department_id');
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) ($request->input('per_page', 20));

        $poQuery = PurchaseOrder::query()
            ->with([
                'items:id,purchase_order_id,qty',
                'perihal:id,nama',
            ])
            ->where('status', 'Approved')
            ->where('tipe_po', 'Reguler')
            ->whereHas('perihal', function($q){
                $q->where(DB::raw('LOWER(nama)'), 'permintaan pembayaran barang');
            });
        // Filter by supplier (Transfer) or credit card (Kredit)
        if (!empty($supplierId)) {
            $poQuery->where('supplier_id', $supplierId);
        } elseif (!empty($creditCardId)) {
            $poQuery->where('credit_card_id', $creditCardId);
        }
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
            ->where('tipe_po', 'Reguler')
            ->whereHas('perihal', function($q){
                $q->where(DB::raw('LOWER(nama)'), 'permintaan pembayaran barang');
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
            // Supplier may be omitted for Kredit method; derive from PO when missing
            'supplier_id' => ['nullable','exists:suppliers,id'],
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

            // Derive supplier from PO if not provided (e.g., Kredit method)
            $supplierId = $validated['supplier_id'] ?? null;
            if (empty($supplierId)) {
                $poModel = PurchaseOrder::find((int) $validated['purchase_order_id']);
                if ($poModel) {
                    $supplierId = $poModel->supplier_id;
                }
            }

            $bpb = Bpb::create([
                'department_id' => $validated['department_id'],
                'purchase_order_id' => $validated['purchase_order_id'],
                'supplier_id' => $supplierId,
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
            $search = trim((string) $request->input('search'));
            $searchColumns = $request->filled('search_columns')
                ? array_filter(array_map('trim', explode(',', (string) $request->input('search_columns'))))
                : [];

            if (!empty($searchColumns)) {
                $query->where(function ($q) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        switch ($column) {
                            case 'no_bpb':
                                $q->orWhere('no_bpb', 'like', "%$search%");
                                break;
                            case 'no_po':
                                $q->orWhereHas('purchaseOrder', function($po) use ($search){
                                    $po->where('no_po', 'like', "%$search%");
                                });
                                break;
                            case 'no_pv':
                                $q->orWhereHas('paymentVoucher', function($pv) use ($search){
                                    $pv->where('no_pv', 'like', "%$search%");
                                });
                                break;
                            case 'tanggal':
                                $q->orWhere('tanggal', 'like', "%$search%");
                                break;
                            case 'status':
                                $q->orWhere('status', 'like', "%$search%");
                                break;
                            case 'supplier':
                                $q->orWhereHas('supplier', function($s) use ($search){
                                    $s->where('nama_supplier', 'like', "%$search%");
                                });
                                break;
                            case 'department':
                                $q->orWhereHas('department', function($d) use ($search){
                                    $d->where('name', 'like', "%$search%");
                                });
                                break;
                            case 'perihal':
                                $q->orWhereHas('purchaseOrder.perihal', function($p) use ($search){
                                    $p->where('nama', 'like', "%$search%");
                                });
                                break;
                            case 'subtotal':
                            case 'diskon':
                            case 'dpp':
                            case 'ppn':
                            case 'pph':
                            case 'grand_total':
                                $q->orWhereRaw('CAST('.$column.' AS CHAR) LIKE ?', ["%$search%"]); 
                                break;
                            case 'keterangan':
                                $q->orWhere('keterangan', 'like', "%$search%");
                                break;
                        }
                    }
                });
            } else {
                // Default broad search when no search_columns specified
                $query->where(function ($q) use ($search) {
                    $q->where('no_bpb', 'like', "%$search%")
                      ->orWhere('status', 'like', "%$search%")
                      ->orWhere('tanggal', 'like', "%$search%")
                      ->orWhere('keterangan', 'like', "%$search%")
                      ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%$search%"]);
                })
                ->orWhereHas('purchaseOrder', function($po) use ($search){
                    $po->where('no_po', 'like', "%$search%");
                })
                ->orWhereHas('paymentVoucher', function($pv) use ($search){
                    $pv->where('no_pv', 'like', "%$search%");
                })
                ->orWhereHas('supplier', function($s) use ($search){
                    $s->where('nama_supplier', 'like', "%$search%");
                })
                ->orWhereHas('department', function($d) use ($search){
                    $d->where('name', 'like', "%$search%");
                })
                ->orWhereHas('purchaseOrder.perihal', function($p) use ($search){
                    $p->where('nama', 'like', "%$search%");
                });
            }
        }

        $perPage = (int) ($request->input('per_page', 10));
        $bpbs = $query->latest()->paginate($perPage)->withQueryString();

        // Fallback: if a BPB has no direct paymentVoucher linked, but its PO has an Approved PV,
        // attach that PV so the UI can display its no_pv via row.payment_voucher?.no_pv
        $collection = $bpbs->getCollection();
        $poIds = $collection->pluck('purchase_order_id')->filter()->unique()->values();
        if ($poIds->isNotEmpty()) {
            $pvByPo = PaymentVoucher::query()
                ->whereIn('purchase_order_id', $poIds)
                ->where('status', 'Approved')
                ->orderByDesc('id')
                ->get(['id', 'no_pv', 'purchase_order_id'])
                ->keyBy('purchase_order_id');

            $collection = $collection->map(function ($bpb) use ($pvByPo) {
                if (!$bpb->relationLoaded('paymentVoucher') || $bpb->paymentVoucher === null) {
                    $poId = (int) ($bpb->purchase_order_id ?? 0);
                    if ($poId && $pvByPo->has($poId)) {
                        $bpb->setRelation('paymentVoucher', $pvByPo->get($poId));
                    }
                }
                return $bpb;
            });
            $bpbs->setCollection($collection);
        }

        // Options for filters
        // Department options scoped to the logged-in user's departments (Admin sees all)
        $departmentOptions = (function() use ($user) {
            $q = Department::query()->active()->select(['id','name'])->orderBy('name');
            $roleName = strtolower(optional($user->role)->name ?? '');
            $hasAllDept = (optional($user->departments) ?? collect())->contains('name', 'All');
            if ($roleName !== 'admin' && !$hasAllDept) {
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
            'department_id' => ['sometimes', 'exists:departments,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'keterangan' => ['nullable', 'string'],
            // Optional pricing fields and items, when present we will recalc totals and replace items
            'diskon' => ['nullable','numeric'],
            'use_ppn' => ['nullable','boolean'],
            'ppn_rate' => ['nullable','numeric'],
            'use_pph' => ['nullable','boolean'],
            'pph_rate' => ['nullable','numeric'],
            'items' => ['sometimes','array','min:1'],
            'items.*.nama_barang' => ['required_with:items','string'],
            'items.*.qty' => ['required_with:items','numeric','min:0'],
            'items.*.satuan' => ['required_with:items','string'],
            'items.*.harga' => ['required_with:items','numeric'],
            'items.*.purchase_order_item_id' => ['required_with:items','exists:purchase_order_items,id'],
        ]);

        // Base updates
        $baseUpdates = [
            'department_id' => $validated['department_id'] ?? $bpb->department_id,
            'purchase_order_id' => $validated['purchase_order_id'] ?? $bpb->purchase_order_id,
            'supplier_id' => $validated['supplier_id'] ?? $bpb->supplier_id,
            'keterangan' => $validated['keterangan'] ?? $bpb->keterangan,
            'updated_by' => $user->id,
        ];
        if ($bpb->status === 'Rejected') {
            $baseUpdates['status'] = 'Draft';
            $baseUpdates['rejected_by'] = null;
            $baseUpdates['rejected_at'] = null;

            // If department is changed while recovering from Rejected, clear number to regenerate on send
            $deptChanged = array_key_exists('department_id', $validated) && (int)($validated['department_id']) !== (int)$bpb->department_id;
            if ($deptChanged) {
                $baseUpdates['no_bpb'] = null;
            }
        }

        // If items not provided, perform simple update only
        if (!$request->has('items')) {
            $bpb->update($baseUpdates);

            BpbLog::create([
                'bpb_id' => $bpb->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'description' => 'Memperbarui BPB',
                'ip_address' => $request->ip(),
            ]);
            return response()->json(['message' => 'BPB diperbarui', 'bpb' => $bpb->fresh()]);
        }

        // Items provided: validate against PO remaining (Approved BPBs only), recalc totals and replace items
        DB::transaction(function () use ($request, $validated, $bpb, $user, $baseUpdates) {
            $items = $validated['items'];

            // Determine PO ID for validation: prefer incoming value, fallback to existing
            $poId = (int) ($validated['purchase_order_id'] ?? $bpb->purchase_order_id);

            if ($poId) {
                // Lock PO items and compute remaining based on Approved BPBs only
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

                // Add back current BPB item quantities (since we will replace them)
                $currentRows = DB::table('bpb_items')
                    ->where('bpb_id', $bpb->id)
                    ->whereNotNull('purchase_order_item_id')
                    ->selectRaw('purchase_order_item_id as poi_id, COALESCE(SUM(qty),0) as curr_qty')
                    ->groupBy('purchase_order_item_id')
                    ->lockForUpdate()
                    ->get();
                $currentByPoi = $currentRows->pluck('curr_qty','poi_id');

                foreach ($items as $it) {
                    $poi = (int) $it['purchase_order_item_id'];
                    $poQty = (float) ($poQtyById[$poi] ?? 0);
                    $received = (float) ($receivedByPoi[$poi] ?? 0);
                    $current = (float) ($currentByPoi[$poi] ?? 0);
                    $remaining = max(0, $poQty - $received + $current);
                    $qty = (float) $it['qty'];
                    if ($qty < 0 || $qty - $remaining > 0.000001) {
                        abort(422, "Qty untuk item PO #$poi melebihi sisa yang diperbolehkan");
                    }
                }
            }

            // Recalculate totals
            $subtotal = collect($items)->reduce(function ($c, $i) { return $c + ($i['qty'] * $i['harga']); }, 0);
            $diskon = (float)($validated['diskon'] ?? ($bpb->diskon ?? 0));
            $dpp = max(0, $subtotal - $diskon);
            $ppnRate = (float)(($validated['use_ppn'] ?? ($bpb->ppn > 0)) ? ($validated['ppn_rate'] ?? 11) : 0);
            $ppn = $ppnRate > 0 ? $dpp * ($ppnRate/100) : 0;
            $pphRate = (float)(($validated['use_pph'] ?? ($bpb->pph > 0)) ? ($validated['pph_rate'] ?? 0) : 0);
            $pph = $pphRate > 0 ? $dpp * ($pphRate/100) : 0;
            $grandTotal = $dpp + $ppn + $pph;

            // Update BPB header
            $bpb->update(array_merge($baseUpdates, [
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'dpp' => $dpp,
                'ppn' => $ppn,
                'pph' => $pph,
                'grand_total' => $grandTotal,
            ]));

            // Replace items
            DB::table('bpb_items')->where('bpb_id', $bpb->id)->delete();
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

            // Log update
            BpbLog::create([
                'bpb_id' => $bpb->id,
                'user_id' => $user->id,
                'action' => 'updated',
                'description' => 'Memperbarui BPB (items & totals)',
                'ip_address' => $request->ip(),
            ]);
        });

        return response()->json(['message' => 'BPB diperbarui', 'bpb' => $bpb->fresh(['items'])]);
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

        return redirect()->back()->with('success', 'Dokumen dikirim');
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
        $bpb = $bpb->load(['items','department','purchaseOrder.perihal','paymentVoucher','supplier','creator']);
        // Fallback: if BPB has no direct paymentVoucher linked, attach Approved PV by matching purchase_order_id
        if (!$bpb->paymentVoucher && $bpb->purchase_order_id) {
            $pv = PaymentVoucher::query()
                ->where('purchase_order_id', $bpb->purchase_order_id)
                ->where('status', 'Approved')
                ->orderByDesc('id')
                ->first(['id','no_pv','purchase_order_id']);
            if ($pv) {
                $bpb->setRelation('paymentVoucher', $pv);
            }
        }
        return Inertia::render('bpb/Detail', [
            'bpb' => $bpb,
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
        $rawName = $bpb->no_bpb ?: 'BPB';
        $safeName = preg_replace('/[\\\\\/]+/', '-', $rawName);
        $filename = $safeName . '.pdf';
      return $pdf->download($filename);
    }

    public function preview(Bpb $bpb)
    {
        if ($bpb->status === 'Canceled') {
            abort(403, 'Dokumen dibatalkan dan tidak dapat dipreview');
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

        $rawName = $bpb->no_bpb ?: 'BPB';
        $safeName = preg_replace('/[\\\\\/]+/', '-', $rawName);
        $filename = $safeName . '.pdf';
        return $pdf->stream($filename);
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


