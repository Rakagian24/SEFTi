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
        // Fetch latest 5 POs for dropdown per rules
        $latestPOs = PurchaseOrder::orderBy('id','desc')->take(5)->get(['id','no_po']);
        $suppliers = Supplier::active()->orderBy('nama_supplier')->get(['id','nama_supplier','alamat','no_telepon']);
        return Inertia::render('bpb/Create', [
            'latestPOs' => $latestPOs,
            'suppliers' => $suppliers,
        ]);
    }

    public function edit(Bpb $bpb)
    {
        // Disallow editing non Draft/Rejected
        if (!in_array($bpb->status, ['Draft', 'Rejected'])) {
            return redirect()->route('bpb.index')->with('error', 'Dokumen tidak dapat diubah');
        }

        $latestPOs = PurchaseOrder::orderBy('id','desc')->take(5)->get(['id','no_po']);
        $suppliers = Supplier::active()->orderBy('nama_supplier')->get(['id','nama_supplier','alamat','no_telepon']);
        return Inertia::render('bpb/Edit', [
            'bpb' => $bpb->load(['items','supplier','purchaseOrder','department']),
            'latestPOs' => $latestPOs,
            'suppliers' => $suppliers,
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
            'items.*.qty' => ['required','numeric'],
            'items.*.satuan' => ['required','string'],
            'items.*.harga' => ['required','numeric'],
        ]);

        $userId = Auth::id();

        $bpb = null;

        DB::transaction(function () use (&$bpb, $validated, $userId, $request) {
            $items = $validated['items'];
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

        return response()->json(['message' => 'Draft BPB tersimpan', 'bpb' => $bpb]);
    }
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Bpb::query()->with(['department', 'purchaseOrder', 'paymentVoucher', 'supplier', 'creator']);

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
        } else if ($user) {
            $deptIds = $user->departments ? $user->departments->pluck('id') : collect([$user->department_id])->filter();
            if ($deptIds->isNotEmpty()) {
                $query->whereIn('department_id', $deptIds);
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
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
        $departmentOptions = Department::active()->orderBy('name')->get(['id', 'name'])->map(function($d){
            return [
                'id' => $d->id,
                'name' => $d->name,
                'label' => $d->name,
                'value' => (string)$d->id,
            ];
        })->values();
        $supplierOptions = Supplier::active()->orderBy('nama_supplier')->get(['id', 'nama_supplier'])->map(function($s){
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
        if (!in_array($bpb->status, ['Draft', 'Rejected'])) {
            return response()->json(['error' => 'Dokumen tidak dapat diubah'], 422);
        }

        $validated = $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = Auth::id();
        $bpb->update($validated);

        // Log update
        BpbLog::create([
            'bpb_id' => $bpb->id,
            'user_id' => Auth::id(),
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

                // Generate number and tanggal on send
                if (!$bpb->no_bpb) {
                    $department = Department::find($bpb->department_id);
                    $alias = $department?->alias ?? ($department?->name ?? 'DEPT');
                    $bpb->no_bpb = DocumentNumberService::generateNumber('Bukti Penerimaan Barang', null, $bpb->department_id, $alias);
                }

                $bpb->tanggal = now();
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
        });

        return response()->json(['message' => 'Dokumen dikirim']);
    }

    public function cancel(Bpb $bpb)
    {
        if (!in_array($bpb->status, ['Draft', 'Rejected'])) {
            return response()->json(['error' => 'Tidak dapat dibatalkan'], 422);
        }

        $bpb->update([
            'status' => 'Canceled',
            'canceled_by' => Auth::id(),
            'canceled_at' => now(),
        ]);

        // Log cancel
        BpbLog::create([
            'bpb_id' => $bpb->id,
            'user_id' => Auth::id(),
            'action' => 'canceled',
            'description' => 'Membatalkan BPB',
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['message' => 'Dokumen dibatalkan']);
    }

    public function show(Bpb $bpb)
    {
        return response()->json($bpb->load(['department', 'purchaseOrder', 'paymentVoucher', 'supplier', 'creator']));
    }

    public function detail(Bpb $bpb)
    {
        return Inertia::render('bpb/Detail', [
            'bpb' => $bpb->load(['items','department','purchaseOrder','paymentVoucher','supplier','creator']),
        ]);
    }

    public function downloadPdf(Bpb $bpb)
    {
        if ($bpb->status === 'Canceled') {
            abort(403, 'Dokumen dibatalkan dan tidak dapat diunduh');
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bpb_pdf', ['bpb' => $bpb->load(['department','purchaseOrder','supplier'])]);
        $filename = ($bpb->no_bpb ?: 'BPB') . '.pdf';
        return $pdf->download($filename);
    }

    public function log(Bpb $bpb, Request $request)
    {
        $bpb = \App\Models\Bpb::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->findOrFail($bpb->id);

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


