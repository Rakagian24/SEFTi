<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PurchaseOrderLog;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(\App\Models\PurchaseOrder::class, 'purchase_order');
    }

    // List + filter
    public function index(Request $request)
    {
        $query = PurchaseOrder::query();

        // Filter dinamis
        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
        }
        if ($request->filled('no_po')) {
            $query->where('no_po', 'like', '%'.$request->no_po.'%');
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('perihal')) {
            $query->where('perihal', 'like', '%'.$request->perihal.'%');
        }
        if ($request->filled('metode_pembayaran')) {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
        }

        // Default: bulan berjalan, departemen user login
        if (!$request->filled('tanggal_start') && !$request->filled('tanggal_end')) {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }
        if (!$request->filled('department_id')) {
            $user = Auth::user();
            if ($user && $user->department_id) {
                $query->where('department_id', $user->department_id);
            }
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('purchase-orders/Index', [
            'data' => $data,
            'filters' => $request->all(),
            'perPage' => $perPage,
            'page' => $data->currentPage(),
            'total' => $data->total(),
        ]);
    }

    // Tambah PO
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'perihal' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'nullable|string',
            'barang' => 'required|array|min:1',
            'barang.*.nama' => 'required|string',
            'barang.*.qty' => 'required|integer|min:1',
            'barang.*.satuan' => 'required|string',
            'barang.*.harga' => 'required|numeric|min:0',
            // diskon, ppn, pph opsional
            'cicilan' => 'nullable|numeric|min:0',
            'termin' => 'nullable|integer|min:0',
            'nominal' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
            'dokumen' => 'nullable|file|max:5120', // 5MB
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $barang = $data['barang'];
        unset($data['barang']);
        // Simpan diskon, ppn, pph jika ada
        $data['diskon'] = $request->input('diskon', 0);
        $data['ppn'] = $request->input('ppn', false);
        $data['pph'] = $request->input('pph', []);
        // Simpan dokumen jika ada
        if ($request->hasFile('dokumen')) {
            $data['dokumen'] = $request->file('dokumen')->store('po-dokumen', 'public');
        }
        $po = PurchaseOrder::create($data);
        foreach ($barang as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'nama_barang' => $item['nama'],
                'qty' => $item['qty'],
                'satuan' => $item['satuan'],
                'harga' => $item['harga'],
            ]);
        }
        // Log activity
        PurchaseOrderLog::create([
            'purchase_order_id' => $po->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Purchase Order dibuat',
            'ip_address' => $request->ip(),
        ]);
        return response()->json($po->load('items'));
    }

    // Edit PO (hanya Draft)
    public function update(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        if ($po->status !== 'Draft') {
            return response()->json(['error' => 'Hanya PO Draft yang bisa diedit'], 403);
        }
        $validator = Validator::make($request->all(), [
            'perihal' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $data['updated_by'] = Auth::id();
        $po->update($data);
        // Log activity
        PurchaseOrderLog::create([
            'purchase_order_id' => $po->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => 'Purchase Order diupdate',
            'ip_address' => $request->ip(),
        ]);
        return response()->json($po);
    }

    // Batalkan PO (hanya Draft)
    public function destroy($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        if ($po->status !== 'Draft') {
            return response()->json(['error' => 'Hanya PO Draft yang bisa dibatalkan'], 403);
        }
        $po->update([
            'status' => 'Canceled',
            'canceled_by' => Auth::id(),
            'canceled_at' => now(),
        ]);
        // Log activity
        PurchaseOrderLog::create([
            'purchase_order_id' => $po->id,
            'user_id' => Auth::id(),
            'action' => 'canceled',
            'description' => 'Purchase Order dibatalkan',
            'ip_address' => request()->ip(),
        ]);
        return response()->json(['success' => true]);
    }

    // Kirim PO (ubah status Draft -> In Progress, generate no_po, isi tanggal)
    public function send(Request $request)
    {
        $ids = $request->input('ids', []);
        $user = Auth::user();
        $updated = [];
        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                $po = PurchaseOrder::findOrFail($id);
                if ($po->status !== 'Draft') continue;
                $po->update([
                    'status' => 'In Progress',
                    'no_po' => $this->generateNoPO(),
                    'tanggal' => now(),
                ]);
                // Log activity
                PurchaseOrderLog::create([
                    'purchase_order_id' => $po->id,
                    'user_id' => $user->id,
                    'action' => 'sent',
                    'description' => 'Purchase Order dikirim',
                    'ip_address' => $request->ip(),
                ]);
                $updated[] = $po->id;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true, 'updated' => $updated]);
    }

    // Detail PO
    public function show($id)
    {
        $po = PurchaseOrder::with(['department', 'creator', 'approver', 'canceller', 'rejecter'])->findOrFail($id);
        return response()->json($po);
    }

    // Download PDF (dummy)
    public function download($id)
    {
        $po = PurchaseOrder::with(['department', 'items'])->findOrFail($id);
        // Hitung summary
        $total = $po->items->sum('harga');
        $diskon = $po->diskon ?? 0;
        $dpp = max($total - $diskon, 0);
        $ppn = ($po->ppn ? $dpp * 0.11 : 0);
        $pphPersen = 0;
        $pph = 0;
        if (is_array($po->pph) && count($po->pph)) {
            $pphPersen = $po->pph[0]['tarif'] * 100;
            $pph = $dpp * $po->pph[0]['tarif'];
        }
        $grandTotal = $dpp + $ppn + $pph;
        $pdf = Pdf::loadView('purchase_order_pdf', [
            'po' => $po,
            'tanggal' => $po->tanggal ? date('d F Y', strtotime($po->tanggal)) : date('d F Y'),
            'total' => $total,
            'diskon' => $diskon,
            'ppn' => $ppn,
            'pph' => $pph,
            'pphPersen' => $pphPersen,
            'grandTotal' => $grandTotal,
        ])->setPaper('a4');
        return $pdf->download('PurchaseOrder_'.$po->no_po.'.pdf');
    }

    // Log activity (dummy)
    public function log($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $logs = \App\Models\PurchaseOrderLog::with(['user.department', 'user.role'])
            ->where('purchase_order_id', $po->id)
            ->orderByDesc('created_at')
            ->get();
        return response()->json([
            'purchaseOrder' => $po,
            'logs' => $logs,
        ]);
    }

    // Helper generate nomor PO
    private function generateNoPO()
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';
        $last = PurchaseOrder::whereDate('created_at', now()->toDateString())
            ->orderByDesc('no_po')->first();
        $num = 1;
        if ($last && $last->no_po) {
            $parts = explode('-', $last->no_po);
            $num = isset($parts[2]) ? ((int)$parts[2] + 1) : 1;
        }
        return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
