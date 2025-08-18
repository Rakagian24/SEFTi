<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Department;
use App\Models\Perihal;
use App\Services\DepartmentService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $user = Auth::user();

        // Use DepartmentScope (do NOT bypass) so 'All' access works and multi-department users are respected
        $query = PurchaseOrder::query()->with(['department', 'perihal', 'bank', 'pph']);

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
        if ($request->filled('perihal_id')) {
            $query->where('perihal_id', $request->perihal_id);
        }
        if ($request->filled('metode_pembayaran')) {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
        }
        // Free text search across common columns
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('no_po', 'like', "%$search%")
                    ->orWhere('no_invoice', 'like', "%$search%")
                    ->orWhere('detail_keperluan', 'like', "%$search%")
                    ->orWhere('keterangan', 'like', "%$search%")
                    ->orWhere('metode_pembayaran', 'like', "%$search%")
                    ->orWhereHas('department', function($deptQuery) use ($search) {
                        $deptQuery->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('perihal', function($perihalQuery) use ($search) {
                        $perihalQuery->where('nama', 'like', "%$search%");
                    })
                    ->orWhereHas('bank', function($bankQuery) use ($search) {
                        $bankQuery->where('nama_bank', 'like', "%$search%");
                    });
            });
        }

        // Note: Do NOT add a default department filter here. DepartmentScope already filters
        // according to the logged-in user's departments (and skips when user has 'All').

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return Inertia::render('purchase-orders/Index', [
            'purchaseOrders' => $data,
            'filters' => $request->all(),
            'departments' => DepartmentService::getOptionsForFilter(),
            'perihals' => Perihal::orderBy('nama')->get(['id','nama','status']),
        ]);
    }

    // Form Create (Inertia)
    public function create()
    {
        return Inertia::render('purchase-orders/Create', [
            'departments' => DepartmentService::getOptionsForForm(),
            'perihals' => Perihal::where('status', 'active')->orderBy('nama')->get(['id','nama','status']),
            'banks' => \App\Models\Bank::where('status', 'active')->orderBy('nama_bank')->get(['id','nama_bank','singkatan']),
            'pphs' => \App\Models\Pph::where('status', 'active')->orderBy('nama_pph')->get(['id','kode_pph','nama_pph','tarif_pph']),
        ]);
    }

    // Get preview number for form
    public function getPreviewNumber(Request $request)
    {
        $request->validate([
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'department_id' => 'required|exists:departments,id',
        ]);

        $department = \App\Models\Department::find($request->department_id);
        if (!$department || !$department->alias) {
            return response()->json(['error' => 'Department tidak valid atau tidak memiliki alias'], 422);
        }

        $previewNumber = \App\Services\DocumentNumberService::generateFormPreviewNumber(
            'Purchase Order',
            $request->tipe_po,
            $request->department_id,
            $department->alias
        );

        return response()->json(['preview_number' => $previewNumber]);
    }

    // Tambah PO
    public function store(Request $request)
    {
        // Debug: Log all incoming data
        Log::info('PurchaseOrder Store - Raw Request Data:', [
            'all_data' => $request->all(),
            'files' => $request->allFiles(),
            'content_type' => $request->header('Content-Type')
        ]);

        // Normalize payload (handle FormData JSON strings)
        $payload = $request->all();
        if (isset($payload['barang']) && is_string($payload['barang'])) {
            $decoded = json_decode($payload['barang'], true);
            $payload['barang'] = is_array($decoded) ? $decoded : [];
        }
        if (isset($payload['pph']) && is_string($payload['pph'])) {
            $decodedPph = json_decode($payload['pph'], true);
            $payload['pph'] = is_array($decodedPph) ? $decodedPph : [];
        }

        // Debug: Log normalized payload
        Log::info('PurchaseOrder Store - Normalized Payload:', $payload);

        $validator = Validator::make($payload, [
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'perihal_id' => 'required|exists:perihals,id',
            'department_id' => 'required|exists:departments,id',
            'no_po' => 'nullable|string', // Will be auto-generated
            'no_invoice' => 'nullable|string',
            'harga' => 'nullable|numeric|min:0',
            'detail_keperluan' => 'nullable|string',
            'metode_pembayaran' => 'nullable|string',
            'no_kartu_kredit' => 'required_if:metode_pembayaran,Kredit|string|nullable',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'nullable|string',
            'no_rekening' => 'nullable|string',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'barang' => 'required|array|min:1',
            'barang.*.nama' => 'required|string',
            'barang.*.qty' => 'required|integer|min:1',
            'barang.*.satuan' => 'required|string',
            'barang.*.harga' => 'required|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'ppn' => 'nullable|boolean',
            'pph_id' => 'nullable', // Allow any value, will be processed later
            'cicilan' => 'nullable|numeric|min:0',
            'termin' => 'nullable|integer|min:0',
            'nominal' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
            'note' => 'nullable|string', // Add note field
            'status' => 'nullable|string|in:Draft,In Progress,Approved,Canceled,Rejected', // Add status field
            'dokumen' => 'nullable|file|max:5120', // 5MB
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $data['created_by'] = Auth::id();

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'Draft';
        }

        // If metode pembayaran is Kredit, force status to Approved
        if (($data['metode_pembayaran'] ?? null) === 'Kredit') {
            $data['status'] = 'Approved';
        }

        // Allow department_id to be set for tipe "Lainnya" as requested
        // (previously this was forced to null)

        $barang = $data['barang'];
        unset($data['barang']);

        // Debug: Log validated data before processing
        Log::info('PurchaseOrder Store - Validated Data:', $data);

        // Hitung total dari barang
        $total = collect($barang)->sum(function($item) {
            return $item['qty'] * $item['harga'];
        });
        $data['total'] = $total;

        // Debug: Log data after calculations
        Log::info('PurchaseOrder Store - Data After Calculations:', [
            'total' => $total,
            'final_data' => $data
        ]);

        // Hitung perhitungan pajak
        $diskon = data_get($payload, 'diskon', 0);
        $data['diskon'] = $diskon;

        $ppn = (bool) data_get($payload, 'ppn', false);
        $data['ppn'] = $ppn;

        // Hitung DPP (setelah diskon)
        $dpp = $total - $diskon;

        // Hitung PPN
        $ppnNominal = $ppn ? ($dpp * 0.11) : 0;
        $data['ppn_nominal'] = $ppnNominal;

        // Hitung PPH
        $pphId = data_get($payload, 'pph_id');
        $pphNominal = 0;
        if ($pphId) {
            // Handle both array and single value
            if (is_array($pphId) && count($pphId) > 0) {
                $pphId = $pphId[0]; // Take first PPH if array
            }

            if ($pphId && is_numeric($pphId)) {
                $pph = \App\Models\Pph::find($pphId);
                if ($pph && $pph->tarif_pph) {
                    $pphNominal = $dpp * ($pph->tarif_pph / 100);
                }
                $data['pph_id'] = $pphId;
            } else {
                $data['pph_id'] = null;
            }
        } else {
            $data['pph_id'] = null;
        }
        $data['pph_nominal'] = $pphNominal;

        // Handle keterangan field (map from note if needed)
        if (isset($payload['keterangan']) && !empty($payload['keterangan'])) {
            $data['keterangan'] = $payload['keterangan'];
        } elseif (isset($payload['note']) && !empty($payload['note'])) {
            $data['keterangan'] = $payload['note'];
        }

        // Hitung Grand Total
        $data['grand_total'] = $dpp + $ppnNominal + $pphNominal;

        // Generate nomor PO saat status bukan Draft
        if ($data['status'] !== 'Draft') {
            // Always generate with department (including Lainnya)
            $department = isset($data['department_id']) ? Department::find($data['department_id']) : null;
            if ($department && $department->alias) {
                $data['no_po'] = DocumentNumberService::generateNumber(
                    'Purchase Order',
                    $data['tipe_po'],
                    $data['department_id'],
                    $department->alias
                );
            } else {
                // Jika tidak ada department, gunakan fallback
                $data['no_po'] = $this->generateNoPO();
            }

            // If status set to Approved at creation time, stamp approval info and tanggal
            if ($data['status'] === 'Approved') {
                $data['tanggal'] = now();
                $data['approved_by'] = Auth::id();
                $data['approved_at'] = now();
            }
        }
        // Jika Draft, no_po akan di-generate saat status berubah dari Draft

        // Simpan dokumen jika ada
        if ($request->hasFile('dokumen')) {
            $data['dokumen'] = $request->file('dokumen')->store('po-dokumen', 'public');
        }

        $po = PurchaseOrder::create($data);

        // Debug: Log created PO
        Log::info('PurchaseOrder Store - PO Created:', [
            'po_id' => $po->id,
            'po_data' => $po->toArray()
        ]);

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
        // For Inertia, redirect back to index after create
        if (!$request->wantsJson()) {
            return redirect()->route('purchase-orders.index');
        }
        return response()->json($po->load('items'));
    }

    // Tambah PPH dari Purchase Order
    public function addPph(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_pph' => 'required|string|max:255|unique:pphs,kode_pph',
            'nama_pph' => 'required|string|max:255|unique:pphs,nama_pph',
            'tarif_pph' => 'nullable|numeric|min:0|max:100',
            'deskripsi' => 'nullable|string',
            'status' => 'required|string|max:50',
        ], [
            'kode_pph.required' => 'Kode PPh wajib diisi.',
            'kode_pph.unique' => 'Kode PPh sudah digunakan.',
            'nama_pph.required' => 'Nama PPh wajib diisi.',
            'nama_pph.unique' => 'Nama PPh sudah digunakan.',
            'tarif_pph.numeric' => 'Tarif PPh harus berupa angka.',
            'tarif_pph.min' => 'Tarif PPh minimal 0.',
            'tarif_pph.max' => 'Tarif PPh maksimal 100.',
            'status.required' => 'Status wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $pph = \App\Models\Pph::create($validator->validated());

            // Log activity
            \App\Models\PphLog::create([
                'pph_id' => $pph->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => 'PPh dibuat dari Purchase Order',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data PPh berhasil ditambahkan',
                'data' => $pph
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data PPh: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tambah Perihal dari Purchase Order (via modal quick add)
    public function addPerihal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100|unique:perihals,nama',
            'deskripsi' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ], [
            'nama.required' => 'Nama perihal wajib diisi.',
            'nama.unique' => 'Nama perihal sudah digunakan.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            if (!isset($data['status']) || empty($data['status'])) {
                $data['status'] = 'active';
            }

            $perihal = \App\Models\Perihal::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data perihal berhasil ditambahkan',
                'data' => $perihal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data perihal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Detail PO
    public function show(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order->load(['department', 'perihal', 'bank', 'items', 'creator', 'approver', 'canceller', 'rejecter']);
        return Inertia::render('purchase-orders/Detail', [
            'purchaseOrder' => $po,
        ]);
    }

    // Edit PO (form)
    public function edit(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order->load(['department', 'items', 'pph']);

        // Check if PO can be edited (only Draft status)
        if ($po->status !== 'Draft') {
            abort(403, 'Purchase Order tidak dapat diedit karena status bukan Draft');
        }

        // Ensure related items are loaded for the edit form
        $po->load(['items']);

        return Inertia::render('purchase-orders/Edit', [
            'purchaseOrder' => $po,
            'departments' => DepartmentService::getOptionsForForm(),
            'perihals' => Perihal::where('status', 'active')->orderBy('nama')->get(['id','nama','status']),
            'banks' => \App\Models\Bank::where('status', 'active')->orderBy('nama_bank')->get(['id','nama_bank','singkatan']),
            'pphs' => \App\Models\Pph::where('status', 'active')->orderBy('nama_pph')->get(['id','kode_pph','nama_pph','tarif_pph']),
        ]);
    }

    // Update PO
    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        $po = $purchase_order;

        // Check if PO can be updated (only Draft status)
        if ($po->status !== 'Draft') {
            return response()->json(['error' => 'Purchase Order tidak dapat diupdate karena status bukan Draft'], 403);
        }

        // Normalize payload (handle FormData JSON strings)
        $payload = $request->all();
        if (isset($payload['barang']) && is_string($payload['barang'])) {
            $decoded = json_decode($payload['barang'], true);
            $payload['barang'] = is_array($decoded) ? $decoded : [];
        }
        if (isset($payload['pph']) && is_string($payload['pph'])) {
            $decodedPph = json_decode($payload['pph'], true);
            $payload['pph'] = is_array($decodedPph) ? $decodedPph : [];
        }

        $validator = Validator::make($payload, [
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'perihal_id' => 'required|exists:perihals,id',
            'department_id' => 'required|exists:departments,id',
            'no_po' => 'nullable|string',
            'no_invoice' => 'nullable|string',
            'harga' => 'nullable|numeric|min:0',
            'detail_keperluan' => 'nullable|string',
            'metode_pembayaran' => 'nullable|string',
            'no_kartu_kredit' => 'required_if:metode_pembayaran,Kredit|string|nullable',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'nullable|string',
            'no_rekening' => 'nullable|string',
            'no_giro' => 'nullable|string',
            'tanggal_giro' => 'nullable|date',
            'tanggal_cair' => 'nullable|date',
            'barang' => 'required|array|min:1',
            'barang.*.nama' => 'required|string',
            'barang.*.qty' => 'required|integer|min:1',
            'barang.*.satuan' => 'required|string',
            'barang.*.harga' => 'required|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'ppn' => 'nullable|boolean',
            'pph_id' => 'nullable',
            'cicilan' => 'nullable|numeric|min:0',
            'termin' => 'nullable|integer|min:0',
            'nominal' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'nullable|string|in:Draft,In Progress,Approved,Canceled,Rejected',
            'dokumen' => 'nullable|file|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        // Allow department_id to be set for tipe "Lainnya" as requested
        // (previously this was forced to null)

        $barang = $data['barang'];
        unset($data['barang']);

        // Hitung total dari barang
        $total = collect($barang)->sum(function($item) {
            return $item['qty'] * $item['harga'];
        });
        $data['total'] = $total;

        // Simpan dokumen jika ada
        if ($request->hasFile('dokumen')) {
            $data['dokumen'] = $request->file('dokumen')->store('po-dokumen', 'public');
        }

        // Auto-approve if metode pembayaran is Kredit
        $effectiveMetode = $data['metode_pembayaran'] ?? $po->metode_pembayaran;
        if ($effectiveMetode === 'Kredit') {
            $data['status'] = 'Approved';
        }

        // If status changed to Approved on update, prepare number and approval metadata
        if (($data['status'] ?? null) === 'Approved') {
            // Generate nomor PO if missing
            if (empty($po->no_po)) {
                $departmentId = $data['department_id'] ?? $po->department_id;
                $department = $departmentId ? Department::find($departmentId) : null;
                if ($department && $department->alias) {
                    $data['no_po'] = DocumentNumberService::generateNumber(
                        'Purchase Order',
                        ($data['tipe_po'] ?? $po->tipe_po),
                        $departmentId,
                        $department->alias
                    );
                } else {
                    $data['no_po'] = $this->generateNoPO();
                }
            }
            // Stamp approval info and tanggal
            $data['tanggal'] = now();
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        DB::beginTransaction();
        try {
            // Update PO
            $po->update($data);

            // Update items
            $po->items()->delete(); // Delete existing items
            foreach ($barang as $item) {
                $po->items()->create([
                    'nama_barang' => $item['nama'],
                    'qty' => $item['qty'],
                    'satuan' => $item['satuan'],
                    'harga' => $item['harga'],
                ]);
            }

            // Handle PPH
            if (isset($payload['pph']) && is_array($payload['pph'])) {
                $po->pph = $payload['pph'];
                $po->save();
            }

            // Log activity
            PurchaseOrderLog::create([
                'purchase_order_id' => $po->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Purchase Order diupdate',
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            if (!$request->wantsJson()) {
                return redirect()->route('purchase-orders.index');
            }

            return response()->json(['success' => true, 'data' => $po->load(['department', 'items', 'pph'])]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PurchaseOrder Update - Error:', [
                'po_id' => $po->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Batalkan PO (hanya Draft)
    public function destroy(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order;
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
        if (!request()->wantsJson()) {
            return redirect()->route('purchase-orders.index');
        }
        return response()->json(['success' => true]);
    }

    // Kirim PO (ubah status Draft -> In Progress, generate no_po, isi tanggal)
    public function send(Request $request)
    {
        // Debug: Log the send request
        Log::info('PurchaseOrder Send - Request received:', [
            'ids' => $request->input('ids', []),
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        $ids = $request->input('ids', []);
        $user = Auth::user();
        $updated = [];
        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                Log::info('PurchaseOrder Send - Processing PO ID:', ['po_id' => $id]);

                $po = PurchaseOrder::findOrFail($id);
                Log::info('PurchaseOrder Send - PO found:', [
                    'po_id' => $po->id,
                    'status' => $po->status,
                    'tipe_po' => $po->tipe_po,
                    'department_id' => $po->department_id
                ]);

                if ($po->status !== 'Draft') {
                    Log::info('PurchaseOrder Send - PO status not Draft, skipping:', ['po_id' => $po->id, 'status' => $po->status]);
                    continue;
                }

                // Generate nomor PO otomatis jika belum ada
                if (!$po->no_po) {
                    // Always generate with department
                    $department = $po->department;
                    Log::info('PurchaseOrder Send - Department info:', [
                        'department_id' => $department->id ?? 'null',
                        'department_alias' => $department->alias ?? 'null'
                    ]);

                    if ($department && $department->alias) {
                        $noPo = DocumentNumberService::generateNumber(
                            'Purchase Order',
                            $po->tipe_po,
                            $po->department_id,
                            $department->alias
                        );
                        Log::info('PurchaseOrder Send - Generated PO number:', ['no_po' => $noPo]);
                    } else {
                        // Fallback jika department tidak valid
                        $noPo = $this->generateNoPO();
                        Log::info('PurchaseOrder Send - Using fallback PO number:', ['no_po' => $noPo]);
                    }
                } else {
                    $noPo = $po->no_po;
                    Log::info('PurchaseOrder Send - Using existing PO number:', ['no_po' => $noPo]);
                }

                $updateData = [
                    'status' => 'In Progress',
                    'no_po' => $noPo,
                    'tanggal' => now(),
                ];

                Log::info('PurchaseOrder Send - Updating PO with data:', $updateData);

                $po->update($updateData);

                Log::info('PurchaseOrder Send - PO updated successfully:', [
                    'po_id' => $po->id,
                    'new_status' => $po->status,
                    'new_no_po' => $po->no_po
                ]);

                // Log activity
                PurchaseOrderLog::create([
                    'purchase_order_id' => $po->id,
                    'user_id' => $user->id,
                    'action' => 'sent',
                    'description' => 'Purchase Order dikirim',
                    'ip_address' => $request->ip(),
                ]);

                Log::info('PurchaseOrder Send - Activity log created for PO:', ['po_id' => $po->id]);

                $updated[] = $po->id;
            }

            Log::info('PurchaseOrder Send - All POs processed, committing transaction:', ['updated_count' => count($updated), 'updated_ids' => $updated]);

            DB::commit();

            Log::info('PurchaseOrder Send - Transaction committed successfully');

        } catch (\Exception $e) {
            Log::error('PurchaseOrder Send - Error occurred:', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'po_ids' => $ids
            ]);

            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        if (!$request->wantsJson()) {
            return redirect()->route('purchase-orders.index');
        }

        Log::info('PurchaseOrder Send - Returning success response:', ['updated' => $updated]);
        return response()->json(['success' => true, 'updated' => $updated]);
    }

    // Download PDF
    public function download(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order->load(['department', 'perihal', 'bank', 'items']);

        // Calculate summary
        $total = 0;
        if ($po->items && count($po->items) > 0) {
            $total = $po->items->sum(function($item) {
                return ($item->qty ?? 1) * ($item->harga ?? 0);
            });
        } else {
            // Fallback to harga field if no items
            $total = $po->harga ?? 0;
        }

        $diskon = $po->diskon ?? 0;
        $dpp = max($total - $diskon, 0);
        $ppn = ($po->ppn ? $dpp * 0.11 : 0);

        $pphPersen = 0;
        $pph = 0;

        // Handle PPH calculation
        if ($po->pph_id) {
            $pphModel = \App\Models\Pph::find($po->pph_id);
            if ($pphModel) {
                $pphPersen = $pphModel->tarif_pph ?? 0;
                $pph = $dpp * ($pphPersen / 100);
            }
        }

        $grandTotal = $dpp + $ppn + $pph;

        // Format date
        $tanggal = $po->tanggal ? date('d F Y', strtotime($po->tanggal)) : date('d F Y');

        $pdf = Pdf::loadView('purchase_order_pdf', [
            'po' => $po,
            'tanggal' => $tanggal,
            'total' => $total,
            'diskon' => $diskon,
            'ppn' => $ppn,
            'pph' => $pph,
            'pphPersen' => $pphPersen,
            'grandTotal' => $grandTotal,
        ])->setPaper('a4');

        return $pdf->download('PurchaseOrder_' . ($po->no_po ?? 'Draft') . '.pdf');
    }

    // Log activity (dummy)
    public function log(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order;
        $logs = \App\Models\PurchaseOrderLog::with(['user.department', 'user.role'])
            ->where('purchase_order_id', $po->id)
            ->orderByDesc('created_at')
            ->get();
        return response()->json([
            'purchaseOrder' => $po,
            'logs' => $logs,
        ]);
    }

    // Helper generate nomor PO (fallback untuk format lama)
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
