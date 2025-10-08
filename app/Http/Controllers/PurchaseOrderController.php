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
use Carbon\Carbon;

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
        $userRole = $user->role->name ?? '';

        // For high-level roles (Admin, Kabag, Direksi), bypass DepartmentScope to see all POs
        if (in_array($userRole, ['Admin', 'Kabag', 'Direksi'])) {
            $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['department', 'perihal', 'supplier', 'creator', 'bank', 'pph']);
        } else {
            // Use DepartmentScope for other roles, but bypass if department filter is applied
            if ($request->filled('department_id')) {
                $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                    ->with(['department', 'perihal', 'supplier', 'creator', 'bank', 'pph']);
            } else {
                $query = PurchaseOrder::query()->with(['department', 'perihal', 'supplier', 'creator', 'bank', 'pph']);
            }
        }

        // Filter dinamis
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
            // Use optimized search method for better performance
            $query->searchOptimized($request->input('search'));
        }

        // Note: Do NOT add a default department filter here. DepartmentScope already filters
        // according to the logged-in user's departments (and skips when user has 'All').

        $perPage = $request->input('per_page', 10);
        $data = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        // Default columns configuration for Purchase Order
        $defaultColumns = [
            ['key' => 'no_po', 'label' => 'No. PO', 'checked' => true, 'sortable' => true],
            ['key' => 'no_invoice', 'label' => 'No. Invoice', 'checked' => false, 'sortable' => true],
            ['key' => 'tipe_po', 'label' => 'Tipe PO', 'checked' => true, 'sortable' => false],
            ['key' => 'tanggal', 'label' => 'Tanggal', 'checked' => true, 'sortable' => true],
            ['key' => 'department', 'label' => 'Departemen', 'checked' => true, 'sortable' => false],
            ['key' => 'perihal', 'label' => 'Perihal', 'checked' => true, 'sortable' => false],
            ['key' => 'supplier', 'label' => 'Supplier', 'checked' => false, 'sortable' => false],
            ['key' => 'metode_pembayaran', 'label' => 'Metode Pembayaran', 'checked' => false, 'sortable' => false],
            ['key' => 'total', 'label' => 'Total', 'checked' => true, 'sortable' => true],
            ['key' => 'diskon', 'label' => 'Diskon', 'checked' => false, 'sortable' => true],
            ['key' => 'ppn', 'label' => 'PPN', 'checked' => false, 'sortable' => true],
            ['key' => 'pph', 'label' => 'PPH', 'checked' => false, 'sortable' => true],
            ['key' => 'grand_total', 'label' => 'Grand Total', 'checked' => true, 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'checked' => true, 'sortable' => true],
            ['key' => 'created_by', 'label' => 'Dibuat Oleh', 'checked' => false, 'sortable' => false],
            ['key' => 'created_at', 'label' => 'Tanggal Dibuat', 'checked' => false, 'sortable' => true],
        ];

        // Get columns from request or use defaults
        $columns = $defaultColumns;
        if ($request->filled('columns')) {
            try {
                $requestedColumns = json_decode($request->input('columns'), true);
                if (is_array($requestedColumns)) {
                    $columns = $requestedColumns;
        }
        } catch (\Exception $e) {
                        // If JSON decode fails, use defaults
                        Log::warning('Failed to decode columns parameter: ' . $e->getMessage());
        }
        }

        // Debug logging removed

        // Check if this is an API request (JSON)
        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json($data);
        }

        return Inertia::render('purchase-orders/Index', [
            'purchaseOrders' => $data,
            'filters' => $request->all(),
            'departments' => DepartmentService::getOptionsForFilter(),
            'perihals' => Perihal::active()->orderBy('nama')->get(['id','nama','status']),
            'columns' => $columns,
        ]);
    }

    // Form Create (Inertia)
    public function create()
    {
        return Inertia::render('purchase-orders/Create', [
            'departments' => DepartmentService::getOptionsForForm(),
            'perihals' => Perihal::active()->orderBy('nama')->get(['id','nama','status']),
            'suppliers' => \App\Models\Supplier::active()->with('banks')->orderBy('nama_supplier')->get(['id','nama_supplier']),
            'banks' => \App\Models\Bank::active()->orderBy('nama_bank')->get(['id','nama_bank','singkatan']),
            'pphs' => \App\Models\Pph::active()->orderBy('nama_pph')->get(['id','kode_pph','nama_pph','tarif_pph']),
                        'termins' => \App\Models\Termin::active()
                ->with(['purchaseOrders' => function($query) {
                    $query->select('id', 'termin_id', 'cicilan', 'grand_total');
                }])
                ->orderByDesc('created_at')
                ->get(['id','no_referensi','jumlah_termin','department_id','created_at'])
                ->filter(function($t) {
                    // Only return termin that are not used in other Purchase Orders
                    return $t->purchaseOrders->count() === 0;
                })
                ->map(function($t) {
                    return [
                        'id' => $t->id,
                        'no_referensi' => $t->no_referensi,
                        'jumlah_termin' => $t->jumlah_termin,
                        'department_id' => $t->department_id,
                        'status' => $t->status,
                        'created_at' => $t->created_at,
                    ];
                })
                ->values()
                ->toArray(),
        ]);
    }

    // Get suppliers by department for dynamic dropdown in PO form
    public function getSuppliersByDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:1000',
        ]);

        $departmentId = $request->input('department_id');
        $search = $request->input('search');
        $perPage = (int) ($request->input('per_page', 100));

        $query = \App\Models\Supplier::active()->where('department_id', $departmentId);
        if ($search) {
            $query->where('nama_supplier', 'like', "%{$search}%");
        }
        $suppliers = $query->orderBy('nama_supplier')
            ->limit($perPage)
            ->get(['id', 'nama_supplier']);

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    public function getTerminInfo($terminId)
    {
        $termin = \App\Models\Termin::with(['purchaseOrders' => function($query) {
            $query->select('id', 'termin_id', 'cicilan', 'grand_total');
        }])->findOrFail($terminId);

        $response = [
            'id' => $termin->id,
            'no_referensi' => $termin->no_referensi,
            'jumlah_termin' => $termin->jumlah_termin,
            'total_cicilan' => $termin->total_cicilan,
            'sisa_pembayaran' => $termin->sisa_pembayaran,
            'jumlah_termin_dibuat' => $termin->jumlah_termin_dibuat,
            'status_termin' => $termin->status_termin,
            'barang_list' => null,
            'grand_total' => 0,
        ];

        // Jika sudah ada PO sebelumnya, ambil data barang dan grand total
        if ($termin->purchaseOrders->count() > 0) {
            $firstPO = $termin->purchaseOrders->first();

            // Ambil data barang dari relasi items
            $barangList = $firstPO->items()->select('nama_barang', 'qty', 'satuan', 'harga')->get();
            $response['barang_list'] = $barangList->map(function($item) {
                return [
                    'nama' => $item->nama_barang,
                    'qty' => $item->qty,
                    'satuan' => $item->satuan,
                    'harga' => $item->harga,
                ];
        })->toArray();

            $response['grand_total'] = $firstPO->grand_total ?? 0;
        }

        return response()->json($response);
    }

    // Search Termins (for large datasets)
    public function searchTermins(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 20);

        $query = \App\Models\Termin::active();
        if ($search) {
            $query->where('no_referensi', 'like', "%{$search}%");
        }
        $termins = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(function($t) {
                return [
                    'id' => $t->id,
                    'no_referensi' => $t->no_referensi,
                    'jumlah_termin' => $t->jumlah_termin,
                    'keterangan' => $t->keterangan,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $termins->items(),
            'current_page' => $termins->currentPage(),
            'last_page' => $termins->lastPage(),
        ]);
    }

    // Get Termins by Department
    public function getTerminsByDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $query = \App\Models\Termin::active()
            ->where('department_id', $request->department_id);

        // Add search filter if provided
        if ($request->filled('search')) {
            $query->where('no_referensi', 'like', '%' . $request->search . '%');
        }

        $termins = $query->with(['purchaseOrders' => function($query) {
                $query->select('id', 'termin_id', 'cicilan', 'grand_total');
            }])
            ->orderByDesc('created_at')
            ->get(['id', 'no_referensi', 'jumlah_termin', 'keterangan', 'status', 'created_at'])
            ->filter(function($t) {
                // Only return termin that are not used in other Purchase Orders
                return $t->purchaseOrders->count() === 0;
            })
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'no_referensi' => $t->no_referensi,
                    'jumlah_termin' => $t->jumlah_termin,
                    'keterangan' => $t->keterangan,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $termins,
        ]);
    }

    // Get supplier bank accounts
    public function getSupplierBankAccounts(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $supplier = \App\Models\Supplier::with(['bankAccounts.bank'])->findOrFail($request->supplier_id);

        $bankAccounts = $supplier->bankAccounts->map(function($account) {
            return [
                'id' => $account->id, // ID dari bank_supplier_accounts table
                'bank_id' => $account->bank_id,
                'bank_name' => $account->bank->nama_bank,
                'bank_singkatan' => $account->bank->singkatan,
                'nama_rekening' => $account->nama_rekening,
                'no_rekening' => $account->no_rekening,
            ];
        });

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'nama_supplier' => $supplier->nama_supplier,
            ],
            'bank_accounts' => $bankAccounts,
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
        // Set maximum execution time to prevent hanging
        set_time_limit(120); // 2 minutes

        // Log start of request
        Log::info('PurchaseOrder Store - Request started', [
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true),
            'user_id' => Auth::id()
        ]);
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
        Log::info('PurchaseOrder Store - Normalized Payload:', [
            'payload_size' => count($payload),
            'barang_count' => isset($payload['barang']) ? count($payload['barang']) : 0,
            'pph_id' => $payload['pph_id'] ?? 'not_set',
            'status' => $payload['status'] ?? 'not_set'
        ]);

            // Jika pembuat adalah Kepala Toko, status langsung Verified
            $user = Auth::user();
            $userRole = $user->role->name ?? '';
            $intendedStatus = $payload['status'] ?? 'Draft';
            $isDraft = ($intendedStatus === 'Draft');
            if (strtolower($userRole) === 'kepala toko' && !$isDraft) {
                $payload['status'] = 'Verified';
                $intendedStatus = 'Verified';
                $isDraft = false;
            }

        // Validasi berbeda untuk Draft vs Submit
        $rules = [
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'tanggal' => 'nullable|date', // Tanggal opsional, akan diisi otomatis
            'department_id' => 'required|exists:departments,id',

            // Field yang wajib untuk submit, opsional untuk draft
            'perihal_id' => $isDraft ? 'nullable|exists:perihals,id' : 'required|exists:perihals,id',
            'supplier_id' => $isDraft ? 'nullable|exists:suppliers,id' : 'required_if:metode_pembayaran,Transfer|exists:suppliers,id',
            'harga' => 'nullable|numeric|min:0',
            'detail_keperluan' => 'nullable|string',
            'metode_pembayaran' => $isDraft ? 'nullable|string' : 'required|string',

            // Field opsional
            'no_po' => 'nullable|string', // Will be auto-generated
            'no_invoice' => 'nullable|string',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'ppn' => 'nullable|boolean',
            'pph_id' => 'nullable|exists:pphs,id',
            'cicilan' => 'nullable|numeric|min:0',
            'termin' => 'nullable|integer|min:0',
            'termin_id' => 'nullable|exists:termins,id',
            'nominal' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:Draft,In Progress,Verified,Approved,Canceled,Rejected',
            'dokumen' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:51200', // Increased max size to 50MB
            // Customer fields for Refund Konsumen
            'customer_id' => 'nullable|exists:ar_partners,id',
            'customer_bank_id' => 'nullable|exists:banks,id',
            'customer_nama_rekening' => 'nullable|string',
            'customer_no_rekening' => 'nullable|string',
        ];

        // Validasi field berdasarkan metode pembayaran (lebih ketat untuk submit)
        if ($isDraft) {
            // Untuk draft, validasi minimal
            $rules['no_kartu_kredit'] = 'nullable|string';
            $rules['bank_id'] = 'nullable|exists:banks,id';
            $rules['nama_rekening'] = 'nullable|string';
            $rules['no_rekening'] = 'nullable|string';
            $rules['no_giro'] = 'nullable|string';
            $rules['tanggal_giro'] = 'nullable|date';
            $rules['tanggal_cair'] = 'nullable|date';
        } else {
            // Untuk submit, validasi ketat berdasarkan metode pembayaran
            $rules['no_kartu_kredit'] = 'required_if:metode_pembayaran,Kredit|string|nullable';
            $rules['bank_id'] = 'required_if:metode_pembayaran,Transfer|exists:banks,id';
            $rules['nama_rekening'] = 'required_if:metode_pembayaran,Transfer|string';
            $rules['no_rekening'] = 'required_if:metode_pembayaran,Transfer|string';
            $rules['no_giro'] = 'required_if:metode_pembayaran,Cek/Giro|string';
            $rules['tanggal_giro'] = 'required_if:metode_pembayaran,Cek/Giro|date';
            $rules['tanggal_cair'] = 'required_if:metode_pembayaran,Cek/Giro|date';
        }

        // Special validation for Refund Konsumen perihal
        if (!$isDraft && isset($payload['perihal_id'])) {
            $perihal = \App\Models\Perihal::find($payload['perihal_id']);
            if ($perihal && strtolower($perihal->nama) === 'permintaan pembayaran refund konsumen') {
                Log::info('Refund Konsumen validation applied', [
                    'perihal_id' => $payload['perihal_id'],
                    'perihal_name' => $perihal->nama,
                    'customer_id' => $payload['customer_id'] ?? 'not_set',
                    'customer_bank_id' => $payload['customer_bank_id'] ?? 'not_set',
                    'customer_nama_rekening' => $payload['customer_nama_rekening'] ?? 'not_set',
                    'customer_no_rekening' => $payload['customer_no_rekening'] ?? 'not_set'
                ]);

                // For Refund Konsumen, customer fields are required instead of supplier fields
                $rules['customer_id'] = 'required|exists:ar_partners,id';
                $rules['customer_bank_id'] = 'required_if:metode_pembayaran,Transfer|exists:banks,id';
                $rules['customer_nama_rekening'] = 'required_if:metode_pembayaran,Transfer|string';
                $rules['customer_no_rekening'] = 'required_if:metode_pembayaran,Transfer|string';

                // Make supplier fields optional for Refund Konsumen
                $rules['supplier_id'] = 'nullable|exists:suppliers,id';
                $rules['bank_id'] = 'nullable|exists:banks,id';
                $rules['nama_rekening'] = 'nullable|string';
                $rules['no_rekening'] = 'nullable|string';
            }
        }

        // Validasi field berdasarkan tipe PO
        if (!$isDraft) {
            // Untuk submit, validasi berdasarkan tipe PO
            if (($payload['tipe_po'] ?? null) === 'Reguler') {
                $rules['harga'] = 'required|numeric|min:0';
            } elseif (($payload['tipe_po'] ?? null) === 'Lainnya') {
                $rules['termin_id'] = 'required|exists:termins,id';
            }
        }

        // Validasi barang berdasarkan status
        if ($isDraft) {
            // Untuk draft, barang opsional
            $rules['barang'] = 'nullable|array';
            $rules['barang.*.nama'] = 'sometimes|required|string';
            $rules['barang.*.qty'] = 'sometimes|required|integer|min:1';
            $rules['barang.*.satuan'] = 'sometimes|required|string';
            $rules['barang.*.harga'] = 'sometimes|required|numeric|min:0';
            $rules['barang.*.tipe'] = 'nullable|in:Barang,Jasa';
        } else {
            // Untuk submit, barang wajib
            $rules['barang'] = 'required|array|min:1';
            $rules['barang.*.nama'] = 'required|string';
            $rules['barang.*.qty'] = 'required|integer|min:1';
            $rules['barang.*.satuan'] = 'required|string';
            $rules['barang.*.harga'] = 'required|numeric|min:0';
            $rules['barang.*.tipe'] = 'nullable|in:Barang,Jasa';
        }

        Log::info('PurchaseOrder Store - About to validate with rules:', [
            'rules_count' => count($rules),
            'payload_keys' => array_keys($payload),
            'isDraft' => $isDraft
        ]);

        $validator = Validator::make($payload, $rules);

        Log::info('PurchaseOrder Store - Validation completed');

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = $this->formatValidationErrors($errors);

            return response()->json([
                'errors' => $errors,
                'error_messages' => $errorMessages,
                'message' => 'Validasi gagal. Silakan periksa kembali data yang diisi.',
                'status' => 'validation_failed'
            ], 422);
        }


        $data = $validator->validated();

        // Ensure diskon, ppn, ppn_nominal, pph_id, pph_nominal are always set (reset to 0/null if unchecked)
        $data['diskon'] = $data['diskon'] ?? 0;
        $data['ppn'] = $data['ppn'] ?? false;
        $data['ppn_nominal'] = $data['ppn_nominal'] ?? 0;
        $data['pph_id'] = $data['pph_id'] ?? null;
        $data['pph_nominal'] = $data['pph_nominal'] ?? 0;

        // Ensure empty string fields are properly handled for nullable fields
        $nullableFields = ['note', 'detail_keperluan', 'keterangan', 'no_invoice', 'no_po'];
        foreach ($nullableFields as $field) {
            if (isset($payload[$field]) && $payload[$field] === '') {
                $data[$field] = null;
            }
        }

        // Map note -> keterangan if note is explicitly provided
        if (array_key_exists('note', $payload)) {
            $data['keterangan'] = (isset($payload['note']) && trim((string)$payload['note']) !== '')
                ? $payload['note']
                : null;
            unset($data['note']);
        }

        $data['created_by'] = Auth::id();

        // Always set tanggal to current date (today)
        $data['tanggal'] = now();


        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'Draft';
        }

        // If metode pembayaran is Kredit, force status to Approved only when not Draft
        if (($data['metode_pembayaran'] ?? null) === 'Kredit' && ($data['status'] ?? 'Draft') !== 'Draft') {
            $data['status'] = 'Approved';
        }

        // Allow department_id to be set for tipe "Lainnya" as requested
        // (previously this was forced to null)

        $barang = $data['barang'] ?? [];
        unset($data['barang']);

        // Debug: Log validated data before processing
        Log::info('PurchaseOrder Store - Validated Data:', $data);

        // Hitung total dari barang
        $total = collect($barang)->sum(function($item) {
            return $item['qty'] * $item['harga'];
        });
        // Fallback untuk tipe "Lainnya" tanpa item: gunakan nominal sebagai total
        if ((($data['tipe_po'] ?? null) === 'Lainnya') && (empty($barang) || count($barang) === 0)) {
            $total = (float) ($payload['nominal'] ?? 0);
        }
        $data['total'] = $total;

        // Hitung total DPP untuk PPh (hanya item bertipe 'Jasa')
        $dppPph = collect($barang)
            ->filter(function($item) {
                return isset($item['tipe']) && strtolower($item['tipe']) === 'jasa';
            })
            ->sum(function($item) {
                return $item['qty'] * $item['harga'];
            });
        // Fallback PPh base untuk tipe "Lainnya" tanpa item: gunakan nominal
        if ((($data['tipe_po'] ?? null) === 'Lainnya') && (empty($barang) || count($barang) === 0)) {
            $dppPph = (float) ($payload['nominal'] ?? 0);
        }

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
                    // PPh hanya untuk item bertipe 'Jasa'
                    $pphNominal = $dppPph * ($pph->tarif_pph / 100);
                }
                $data['pph_id'] = $pphId;
                    $pphTarif = $pph ? $pph->tarif_pph : null;
            } else {
                $data['pph_id'] = null;
                    $pphTarif = null;
            }
        } else {
            $data['pph_id'] = null;
                $pphTarif = null;
        }
        $data['pph_nominal'] = $pphNominal;

            // Debug: Log detail perhitungan PPh
            Log::info('PurchaseOrder Store - PPh Calculation:', [
                'dppPph' => $dppPph,
                'pph_id' => $pphId,
                'pph_tarif' => $pphTarif,
                'pph_nominal' => $pphNominal,
            ]);

        // Nullify fields only relevant for tipe "Lainnya" when tipe is Reguler
        if (($data['tipe_po'] ?? 'Reguler') === 'Reguler') {
            $data['cicilan'] = null;
            $data['termin'] = null;
            $data['nominal'] = null;
            $data['termin_id'] = null;
        }

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

        // Debug: Log barang, tipe, DPP PPh, tarif PPh, dan hasil perhitungan sebelum simpan
        $debugBarang = [];
        foreach ($barang as $item) {
            $debugBarang[] = [
                'nama' => $item['nama'] ?? null,
                'tipe' => $item['tipe'] ?? null,
                'qty' => $item['qty'] ?? null,
                'harga' => $item['harga'] ?? null
            ];
        }
        Log::info('PurchaseOrder Store - Barang Detail:', $debugBarang);
        Log::info('PurchaseOrder Store - DPP PPh & Tarif:', [
            'dppPph' => isset($dppPph) ? $dppPph : null,
            'pph_id' => $data['pph_id'] ?? null,
            'pph_nominal' => $data['pph_nominal'] ?? null
        ]);

        // Use database transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Pastikan pph_nominal benar-benar hasil perhitungan
            $po = new PurchaseOrder($data);
            $po->pph_nominal = isset($data['pph_nominal']) ? $data['pph_nominal'] : 0;
            $po->save();

        // Debug: Log created PO
        Log::info('PurchaseOrder Store - PO Created:', [
            'po_id' => $po->id,
            'po_data' => $po->toArray()
        ]);

        foreach ($barang as $item) {
            $perihalNama = strtolower(optional($po->perihal)->nama ?? '');
            // Ambil tipe dari inputan, fallback ke logika lama jika tidak ada
            $tipe = isset($item['tipe']) && in_array(strtolower($item['tipe']), ['barang','jasa']) ? ucfirst(strtolower($item['tipe'])) : 'Barang';
            if ($perihalNama === 'permintaan pembayaran barang') {
                $tipe = 'Barang';
            } else if ($perihalNama === 'permintaan pembayaran jasa') {
                $tipe = 'Jasa';
            } else if ($perihalNama === 'permintaan pembayaran barang/jasa') {
                // biarin sesuai input user (Barang atau Jasa)
            }
            Log::info('PurchaseOrder Store - Item Debug:', [
                'item' => $item,
                'tipe_final' => $tipe
            ]);

            try {
                Log::info('PurchaseOrder Store - About to create PurchaseOrderItem:', [
                    'purchase_order_id' => $po->id,
                    'nama_barang' => $item['nama'],
                    'qty' => $item['qty'],
                    'satuan' => $item['satuan'],
                    'harga' => $item['harga'],
                    'tipe' => $tipe,
                ]);

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'nama_barang' => $item['nama'],
                    'qty' => $item['qty'],
                    'satuan' => $item['satuan'],
                    'harga' => $item['harga'],
                    'tipe' => $tipe,
                ]);

                Log::info('PurchaseOrder Store - PurchaseOrderItem created successfully:', [
                    'item_id' => $poItem->id
                ]);
            } catch (\Exception $e) {
                Log::error('PurchaseOrder Store - Error creating PurchaseOrderItem:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        }

        Log::info('PurchaseOrder Store - All items created, creating log entry');

        // Log activity
        try {
            PurchaseOrderLog::create([
                'purchase_order_id' => $po->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => 'Membuat data Purchase Order',
                'ip_address' => $request->ip(),
            ]);
            Log::info('PurchaseOrder Store - Log entry created successfully');
        } catch (\Exception $e) {
            Log::error('PurchaseOrder Store - Error creating log entry:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw here, just log the error
        }

        // Commit transaction if everything is successful
        DB::commit();
        Log::info('PurchaseOrder Store - Transaction committed successfully');

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollback();
            Log::error('PurchaseOrder Store - Transaction rolled back due to error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return error response
            return response()->json([
                'error' => 'Terjadi kesalahan saat menyimpan Purchase Order: ' . $e->getMessage()
            ], 500);
        }

        Log::info('PurchaseOrder Store - About to return response');

        // Debug: Log all headers to see what's being sent
        Log::info('PurchaseOrder Store - Request headers:', [
            'x-inertia' => $request->header('X-Inertia'),
            'x-inertia-version' => $request->header('X-Inertia-Version'),
            'x-requested-with' => $request->header('X-Requested-With'),
            'content-type' => $request->header('Content-Type'),
            'user-agent' => $request->header('User-Agent'),
            'all_headers' => $request->headers->all()
        ]);

        // Check if this is an Inertia request
        if ($request->header('X-Inertia')) {
            Log::info('PurchaseOrder Store - Inertia request detected, returning JSON for success');
            return response()->json([
                'success' => true,
                'message' => 'Purchase Order berhasil dibuat',
                'po_id' => $po->id,
            ]);
        }

        // Return JSON for AJAX/JSON requests; otherwise redirect for normal web
        if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
            Log::info('PurchaseOrder Store - Returning JSON response');
            try {
                Log::info('PurchaseOrder Store - Loading items relationship');
                $poWithItems = $po->load('items');
                Log::info('PurchaseOrder Store - Items loaded successfully', [
                    'items_count' => $poWithItems->items->count()
                ]);

                // Create a simplified response to avoid potential JSON issues
                $responseData = [
                    'id' => $po->id,
                    'no_po' => $po->no_po,
                    'status' => $po->status,
                    'total' => $po->total,
                    'grand_total' => $po->grand_total,
                    'created_at' => $po->created_at,
                    'items_count' => $poWithItems->items->count(),
                    'success' => true,
                    'message' => 'Purchase Order berhasil dibuat'
                ];

                Log::info('PurchaseOrder Store - About to send response', [
                    'response_data' => $responseData
                ]);

                Log::info('PurchaseOrder Store - Request completed successfully', [
                    'timestamp' => now(),
                    'memory_usage' => memory_get_usage(true),
                    'po_id' => $po->id
                ]);

                return response()->json($responseData);
            } catch (\Exception $e) {
                Log::error('PurchaseOrder Store - Error loading items:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Return simplified error response
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase Order dibuat tapi ada masalah loading data',
                    'po_id' => $po->id
                ]);
            }
        }

        Log::info('PurchaseOrder Store - Returning redirect response');
        return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order berhasil dibuat');
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
                'description' => 'Membuat data PPh dari Purchase Order',
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

    // Tambah Termin dari Purchase Order (via modal quick add)
    public function addTermin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_referensi' => 'required|string|max:100|unique:termins,no_referensi',
            'jumlah_termin' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'status' => 'nullable|in:active,inactive',
        ], [
            'no_referensi.required' => 'No Referensi wajib diisi.',
            'no_referensi.unique' => 'No Referensi sudah digunakan.',
            'jumlah_termin.required' => 'Jumlah Termin wajib diisi.',
            'jumlah_termin.integer' => 'Jumlah Termin harus berupa angka.',
            'jumlah_termin.min' => 'Jumlah Termin minimal 1.',
            'department_id.required' => 'Department wajib diisi.',
            'department_id.exists' => 'Department tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
}

        try {
            $data = $validator->validated();
            if (!isset($data['status']) || empty($data['status'])) {
                $data['status'] = 'active';
}

            $termin = \App\Models\Termin::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data termin berhasil ditambahkan',
                'data' => $termin,
            ]);
} catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data termin: ' . $e->getMessage(),
            ], 500);
}
}

    // Detail PO
    public function show(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order->load(['department', 'perihal', 'supplier', 'bank', 'pph', 'termin', 'items', 'creator', 'updater', 'approver', 'canceller', 'rejecter']);
        return Inertia::render('purchase-orders/Detail', [
            'purchaseOrder' => $po,
        ]);
}

        // Edit PO (form)
    public function edit(PurchaseOrder $purchase_order)
    {
        $po = $purchase_order->load(['department', 'items', 'pph', 'supplier']);

        // Check if PO can be edited by current user
        if (!$po->canBeEditedByUser(Auth::user())) {
            abort(403, 'Purchase Order tidak dapat diedit');
        }

        // Ensure related items are loaded for the edit form
        $po->load(['items']);

        return Inertia::render('purchase-orders/Edit', [
            'purchaseOrder' => $po,
            'departments' => DepartmentService::getOptionsForForm(),
            'perihals' => Perihal::where('status', 'active')->orderBy('nama')->get(['id','nama','status']),
            'suppliers' => \App\Models\Supplier::with('banks')->orderBy('nama_supplier')->get(['id','nama_supplier']),
            'banks' => \App\Models\Bank::where('status', 'active')->orderBy('nama_bank')->get(['id','nama_bank','singkatan']),
            'pphs' => \App\Models\Pph::where('status', 'active')->orderBy('nama_pph')->get(['id','kode_pph','nama_pph','tarif_pph']),
            // Termins: keep the current PO's selected termin in options even if already used by this PO
            'termins' => \App\Models\Termin::where('status', 'active')
                ->with(['purchaseOrders' => function($query) {
                    $query->select('id', 'termin_id');
                }])
                ->orderByDesc('created_at')
                ->get(['id','no_referensi','jumlah_termin','keterangan','status','created_at'])
                ->filter(function($t) use ($po) {
                    // allow termins unused OR only used by this current PO
                    $count = $t->purchaseOrders->count();
                    if ($count === 0) return true;
                    if ($count === 1 && optional($t->purchaseOrders->first())->id === $po->id) return true;
                    return false;
                })
                ->map(function($t) {
                    return [
                        'id' => $t->id,
                        'no_referensi' => $t->no_referensi,
                        'jumlah_termin' => $t->jumlah_termin,
                        'keterangan' => $t->keterangan,
                        'status' => $t->status,
                        'created_at' => $t->created_at,
                    ];
                })
                ->values()
                ->toArray(),
        ]);
}

        // Update PO
    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        $po = $purchase_order;

        // Check if PO can be updated by current user
        if (!$po->canBeEditedByUser(Auth::user())) {
            return response()->json(['error' => 'Purchase Order tidak dapat diupdate'], 403);
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

        $intendedStatus = $payload['status'] ?? $po->status ?? 'Draft';
        $isDraft = ($intendedStatus === 'Draft');

        // Validasi berbeda untuk Draft vs Submit
        $rules = [
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'tanggal' => 'nullable|date', // Tanggal opsional, akan diisi otomatis
            'department_id' => 'required|exists:departments,id',

            // Field yang wajib untuk submit, opsional untuk draft
            'perihal_id' => $isDraft ? 'nullable|exists:perihals,id' : 'required|exists:perihals,id',
            'supplier_id' => $isDraft ? 'nullable|exists:suppliers,id' : 'required_if:metode_pembayaran,Transfer|exists:suppliers,id',
            'harga' => 'nullable|numeric|min:0',
            'detail_keperluan' => 'nullable|string',
            'metode_pembayaran' => $isDraft ? 'nullable|string' : 'required|string',

            // Field opsional
            'no_po' => 'nullable|string',
            'no_invoice' => 'nullable|string',
            'note' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'ppn' => 'nullable|boolean',
            'pph_id' => 'nullable|exists:pphs,id',
            'cicilan' => 'nullable|numeric|min:0',
            'termin' => 'nullable|integer|min:0',
            'termin_id' => 'nullable|exists:termins,id',
            'nominal' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:Draft,In Progress,Approved,Canceled,Rejected',
            'dokumen' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Customer fields for Refund Konsumen
            'customer_id' => 'nullable|exists:ar_partners,id',
            'customer_bank_id' => 'nullable|exists:banks,id',
            'customer_nama_rekening' => 'nullable|string',
            'customer_no_rekening' => 'nullable|string',
        ];

        // Validasi field berdasarkan metode pembayaran (lebih ketat untuk submit)
        if ($isDraft) {
            // Untuk draft, validasi minimal
            $rules['no_kartu_kredit'] = 'nullable|string';
            $rules['bank_id'] = 'nullable|exists:banks,id';
            $rules['nama_rekening'] = 'nullable|string';
            $rules['no_rekening'] = 'nullable|string';
            $rules['no_giro'] = 'nullable|string';
            $rules['tanggal_giro'] = 'nullable|date';
            $rules['tanggal_cair'] = 'nullable|date';
        } else {
            // Untuk submit, validasi ketat berdasarkan metode pembayaran
            $rules['no_kartu_kredit'] = 'required_if:metode_pembayaran,Kredit|string|nullable';
            $rules['bank_id'] = 'required_if:metode_pembayaran,Transfer|exists:banks,id';
            $rules['nama_rekening'] = 'required_if:metode_pembayaran,Transfer|string';
            $rules['no_rekening'] = 'required_if:metode_pembayaran,Transfer|string';
            $rules['no_giro'] = 'required_if:metode_pembayaran,Cek/Giro|string';
            $rules['tanggal_giro'] = 'required_if:metode_pembayaran,Cek/Giro|date';
            $rules['tanggal_cair'] = 'required_if:metode_pembayaran,Cek/Giro|date';
        }

        // Special validation for Refund Konsumen perihal
        if (!$isDraft && isset($payload['perihal_id'])) {
            $perihal = \App\Models\Perihal::find($payload['perihal_id']);
            if ($perihal && strtolower($perihal->nama) === 'permintaan pembayaran refund konsumen') {
                Log::info('Refund Konsumen validation applied', [
                    'perihal_id' => $payload['perihal_id'],
                    'perihal_name' => $perihal->nama,
                    'customer_id' => $payload['customer_id'] ?? 'not_set',
                    'customer_bank_id' => $payload['customer_bank_id'] ?? 'not_set',
                    'customer_nama_rekening' => $payload['customer_nama_rekening'] ?? 'not_set',
                    'customer_no_rekening' => $payload['customer_no_rekening'] ?? 'not_set'
                ]);

                // For Refund Konsumen, customer fields are required instead of supplier fields
                $rules['customer_id'] = 'required|exists:ar_partners,id';
                $rules['customer_bank_id'] = 'required_if:metode_pembayaran,Transfer|exists:banks,id';
                $rules['customer_nama_rekening'] = 'required_if:metode_pembayaran,Transfer|string';
                $rules['customer_no_rekening'] = 'required_if:metode_pembayaran,Transfer|string';

                // Make supplier fields optional for Refund Konsumen
                $rules['supplier_id'] = 'nullable|exists:suppliers,id';
                $rules['bank_id'] = 'nullable|exists:banks,id';
                $rules['nama_rekening'] = 'nullable|string';
                $rules['no_rekening'] = 'nullable|string';
            }
        }

        // Validasi field berdasarkan tipe PO
        if (!$isDraft) {
            // Untuk submit, validasi berdasarkan tipe PO
            if (($payload['tipe_po'] ?? null) === 'Reguler') {
                $rules['harga'] = 'required|numeric|min:0';
            } elseif (($payload['tipe_po'] ?? null) === 'Lainnya') {
                $rules['termin_id'] = 'required|exists:termins,id';
            }
        }

        // Validasi barang berdasarkan status
        if ($isDraft) {
            // Untuk draft, barang opsional
            $rules['barang'] = 'nullable|array';
            $rules['barang.*.nama'] = 'sometimes|required|string';
            $rules['barang.*.qty'] = 'sometimes|required|integer|min:1';
            $rules['barang.*.satuan'] = 'sometimes|required|string';
            $rules['barang.*.harga'] = 'sometimes|required|numeric|min:0';
            $rules['barang.*.tipe'] = 'nullable|in:Barang,Jasa';
        } else {
            // Untuk submit, barang wajib
            $rules['barang'] = 'required|array|min:1';
            $rules['barang.*.nama'] = 'required|string';
            $rules['barang.*.qty'] = 'required|integer|min:1';
            $rules['barang.*.satuan'] = 'required|string';
            $rules['barang.*.harga'] = 'required|numeric|min:0';
            $rules['barang.*.tipe'] = 'nullable|in:Barang,Jasa';
        }

        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = $this->formatValidationErrors($errors);

            return response()->json([
                'errors' => $errors,
                'error_messages' => $errorMessages,
                'message' => 'Validasi gagal. Silakan periksa kembali data yang diisi.',
                'status' => 'validation_failed'
            ], 422);
        }


        $data = $validator->validated();

        // Ensure diskon, ppn, ppn_nominal, pph_id, pph_nominal are always set (reset to 0/null if unchecked)
        $data['diskon'] = $data['diskon'] ?? 0;
        $data['ppn'] = $data['ppn'] ?? false;
        $data['ppn_nominal'] = $data['ppn_nominal'] ?? 0;
        $data['pph_id'] = $data['pph_id'] ?? null;
        $data['pph_nominal'] = $data['pph_nominal'] ?? 0;

        // Ensure empty string fields are properly handled for nullable fields
        $nullableFields = ['note', 'detail_keperluan', 'keterangan', 'no_invoice', 'no_po'];
        foreach ($nullableFields as $field) {
            if (isset($payload[$field]) && $payload[$field] === '') {
                $data[$field] = null;
            }
        }

        // If client sends 'note', force map it to 'keterangan' (override any incoming keterangan)
        if (array_key_exists('note', $payload)) {
            $data['keterangan'] = (isset($payload['note']) && trim((string)$payload['note']) !== '')
                ? $payload['note']
                : null;
            unset($data['note']);
        }
        $data['updated_by'] = Auth::id();

        // Always set tanggal to current date (today)
        $data['tanggal'] = now();

        // Debug: Log the data being processed
        Log::info('PurchaseOrder Update - Data being processed:', [
            'po_id' => $po->id,
            'pph_id' => $data['pph_id'] ?? null,
            'data_keys' => array_keys($data)
        ]);


        // Allow department_id to be set for tipe "Lainnya" as requested
        // (previously this was forced to null)

        $barang = $data['barang'] ?? [];
        unset($data['barang']);

        // Hitung total dari barang
        $total = collect($barang)->sum(function($item) {
            return $item['qty'] * $item['harga'];
        });
        // Fallback untuk tipe "Lainnya" tanpa item: gunakan nominal sebagai total
        if ((($data['tipe_po'] ?? $po->tipe_po ?? null) === 'Lainnya') && (empty($barang) || count($barang) === 0)) {
            $total = (float) ($payload['nominal'] ?? $po->nominal ?? 0);
        }
        $data['total'] = $total;

        // Hitung total DPP untuk PPh (hanya item bertipe 'Jasa')
        $dppPph = collect($barang)
            ->filter(function($item) {
                return isset($item['tipe']) && strtolower($item['tipe']) === 'jasa';
            })
            ->sum(function($item) {
                return $item['qty'] * $item['harga'];
            });
        // Fallback PPh base untuk tipe "Lainnya" tanpa item: gunakan nominal
        if ((($data['tipe_po'] ?? $po->tipe_po ?? null) === 'Lainnya') && (empty($barang) || count($barang) === 0)) {
            $dppPph = (float) ($payload['nominal'] ?? $po->nominal ?? 0);
        }
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
                    // PPh hanya untuk item bertipe 'Jasa'
                    $pphNominal = $dppPph * ($pph->tarif_pph / 100);
                }
                $data['pph_id'] = $pphId;
                    $pphTarif = $pph ? $pph->tarif_pph : null;
            } else {
                $data['pph_id'] = null;
                    $pphTarif = null;
            }
        } else {
            $data['pph_id'] = null;
                $pphTarif = null;
        }
        $data['pph_nominal'] = $pphNominal;

            // Debug: Log detail perhitungan PPh
            Log::info('PurchaseOrder Update - PPh Calculation:', [
                'dppPph' => $dppPph,
                'pph_id' => $pphId,
                'pph_tarif' => $pphTarif,
                'pph_nominal' => $pphNominal,
            ]);

        // Simpan dokumen jika ada
        if ($request->hasFile('dokumen')) {
            $data['dokumen'] = $request->file('dokumen')->store('po-dokumen', 'public');
        }

        // Auto-approve if metode pembayaran is Kredit and status is not Draft
        $effectiveMetode = $data['metode_pembayaran'] ?? $po->metode_pembayaran;
        $effectiveStatus = $data['status'] ?? $po->status;
        if ($effectiveMetode === 'Kredit' && $effectiveStatus !== 'Draft') {
            $data['status'] = 'Approved';
        }

        // Additional validation for PPH ID
        if (!empty($data['pph_id'])) {
            $pph = \App\Models\Pph::find($data['pph_id']);
            if (!$pph) {
                return response()->json([
                    'errors' => ['pph_id' => ['PPH yang dipilih tidak ditemukan']],
                    'message' => 'PPH yang dipilih tidak ditemukan dalam sistem.',
                    'status' => 'pph_not_found'
                ], 422);
            }
        }

        // Handle status change for rejected PO - change to In Progress when resubmitted
        $newStatus = $data['status'] ?? $po->status;
        $isStatusChangedFromDraft = ($po->status === 'Draft' && $newStatus !== 'Draft');
        $isStatusChangedFromRejected = ($po->status === 'Rejected' && $newStatus !== 'Draft');

        // If PO was rejected and is being resubmitted, change status to In Progress
        if ($isStatusChangedFromRejected && $newStatus !== 'Draft') {
            $data['status'] = 'In Progress';
            $newStatus = 'In Progress';
        }

        // Generate nomor PO if status changed from Draft to non-Draft and no_po is empty
        if ($isStatusChangedFromDraft && empty($po->no_po)) {
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

        // If status changed to Approved on update, prepare approval metadata
        if (($data['status'] ?? null) === 'Approved') {
            // Stamp approval info and tanggal
            $data['tanggal'] = now();
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        DB::beginTransaction();
        try {
            // Update PO
            $po->update($data);
            $po->refresh();

            // Debug persisted values
            Log::info('PO Update - Persisted values', [
                'po_id' => $po->id,
                'keterangan_saved' => $po->keterangan,
                'detail_keperluan_saved' => $po->detail_keperluan,
                'no_invoice_saved' => $po->no_invoice,
                'status_saved' => $po->status,
            ]);

            // Update items
            $po->items()->delete(); // Delete existing items
            foreach ($barang as $item) {
                $perihalNama = strtolower(optional($po->perihal)->nama ?? '');
                $tipe = 'Lainnya';
                if ($perihalNama === 'permintaan pembayaran barang') {
                    $tipe = 'Barang';
                } else if ($perihalNama === 'permintaan pembayaran jasa') {
                    $tipe = 'Jasa';
                } else if ($perihalNama === 'permintaan pembayaran barang/jasa') {
                    // biarin sesuai input user (Barang atau Jasa)
                }

                $po->items()->create([
                    'nama_barang' => $item['nama'],
                    'qty' => $item['qty'],
                    'satuan' => $item['satuan'],
                    'harga' => $item['harga'],
                    'tipe' => $tipe,
                ]);
            }

            // Handle PPH
            if (isset($payload['pph']) && is_array($payload['pph'])) {
                $po->pph = $payload['pph'];
                $po->save();
            }

            // Log activity
            $logDescription = 'Mengubah data Purchase Order';
            if ($isStatusChangedFromRejected) {
                $logDescription = 'Memperbaiki dan mengirim ulang Purchase Order yang ditolak';
            }

            PurchaseOrderLog::create([
                'purchase_order_id' => $po->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => $logDescription,
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => true, 'data' => $po->load(['department', 'items', 'pph'])]);
            }
            return redirect()->route('purchase-orders.index');
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
            'description' => 'Membatalkan data Purchase Order',
            'ip_address' => request()->ip(),
        ]);
        if (request()->ajax() || request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('purchase-orders.index');
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
        // Sort IDs by created_at ASC to ensure lower sequence first
        if (!empty($ids)) {
            $ids = PurchaseOrder::whereIn('id', $ids)
                ->orderBy('created_at', 'asc')
                ->pluck('id')
                ->toArray();
        }
        $user = Auth::user();
        $updated = [];
        $failed = [];

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

                if (!$po->canBeSentByUser($user)) {
                    Log::info('PurchaseOrder Send - PO cannot be sent by user, skipping:', ['po_id' => $po->id, 'status' => $po->status]);
                    continue;
                }

                // Validasi field-field wajib sebelum dikirim
                $validationErrors = $this->validatePurchaseOrderForSending($po);
                if (!empty($validationErrors)) {
                    Log::info('PurchaseOrder Send - Validation failed for PO:', [
                        'po_id' => $po->id,
                        'errors' => $validationErrors
                    ]);
                    $failed[] = [
                        'id' => $po->id,
                        'no_po' => $po->no_po,
                        'errors' => $validationErrors
                    ];
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
                        // Double-check uniqueness to guard against race within same transaction
                        if (!DocumentNumberService::isNumberUnique($noPo)) {
                            // Regenerate once (another thread might have consumed the number); this keeps sequence gap-free
                            $noPo = DocumentNumberService::generateNumber(
                                'Purchase Order',
                                $po->tipe_po,
                                $po->department_id,
                                $department->alias
                            );
                        }
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
                // Jika user adalah Kepala Toko, status langsung Verified
                $userRole = $user->role->name ?? '';
                if (strtolower($userRole) === 'kepala toko') {
                    $updateData = [
                        'status' => 'Verified',
                        'no_po' => $noPo,
                        'tanggal' => now(),
                    ];
                } else {
                    $updateData = [
                        'status' => 'In Progress',
                        'no_po' => $noPo,
                        'tanggal' => now(),
                    ];
                }

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
                    'description' => 'Mengirim data Purchase Order',
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

        Log::info('PurchaseOrder Send - Returning success response:', ['updated' => $updated, 'failed' => $failed]);

        // Return Inertia response with flash messages and data
        if (!empty($failed)) {
            $message = count($updated) . ' PO berhasil dikirim, ' . count($failed) . ' PO gagal karena data tidak lengkap';

            // Store failed POs in session for frontend to access
            session(['failed_pos' => $failed]);
            session(['updated_pos' => $updated]);

            return redirect()->route('purchase-orders.index')->with([
                'success' => $message,
                'failed_pos' => $failed,
                'updated_pos' => $updated
            ]);
        }

        // All POs successful
        $message = count($updated) . ' Purchase Order berhasil dikirim';
        return redirect()->route('purchase-orders.index')->with([
            'success' => $message,
            'updated_pos' => $updated
        ]);
    }

    /**
     * Validasi field-field wajib untuk Purchase Order sebelum dikirim
     */
    private function validatePurchaseOrderForSending($po)
    {
        $errors = [];

        // Validasi field dasar yang wajib
        if (empty($po->perihal_id)) {
            $errors[] = 'Perihal wajib dipilih';
        }
        // Supplier hanya wajib untuk metode Transfer
        if ($po->metode_pembayaran === 'Transfer' && empty($po->supplier_id)) {
            $errors[] = 'Supplier wajib dipilih untuk metode pembayaran Transfer';
        }
        if (empty($po->metode_pembayaran)) {
            $errors[] = 'Metode pembayaran wajib dipilih';
        }

        // Validasi berdasarkan tipe PO (sama dengan edit)
        if ($po->tipe_po === 'Reguler') {
            if (empty($po->harga) || $po->harga <= 0) {
                $errors[] = 'Harga wajib diisi dan harus lebih dari 0 untuk tipe PO Reguler';
            }
        } elseif ($po->tipe_po === 'Lainnya') {
            // Cicilan tidak lagi digunakan untuk tipe PO Lainnya
            if (empty($po->termin_id)) {
                $errors[] = 'No. Referensi Termin wajib dipilih untuk tipe PO Lainnya';
            }
        }

        // Validasi barang
        $items = $po->items;
        if (!$items || $items->count() === 0) {
            $errors[] = 'Barang wajib ditambahkan minimal 1 item';
        } else {
            foreach ($items as $item) {
                if (empty($item->nama_barang)) {
                    $errors[] = 'Nama barang wajib diisi untuk semua item';
                    break;
                }
                if (empty($item->qty) || $item->qty <= 0) {
                    $errors[] = 'Quantity wajib diisi dan harus lebih dari 0 untuk semua item';
                    break;
                }
                if (empty($item->satuan)) {
                    $errors[] = 'Satuan wajib diisi untuk semua item';
                    break;
                }
                if (empty($item->harga) || $item->harga <= 0) {
                    $errors[] = 'Harga barang wajib diisi dan harus lebih dari 0 untuk semua item';
                    break;
                }
            }
        }

        // Validasi berdasarkan metode pembayaran
        if ($po->metode_pembayaran === 'Kredit') {
            if (empty($po->no_kartu_kredit)) {
                $errors[] = 'No. Kartu Kredit wajib diisi untuk metode pembayaran Kredit';
            }
        } elseif ($po->metode_pembayaran === 'Transfer') {
            if (empty($po->bank_id)) {
                $errors[] = 'Bank wajib dipilih untuk metode pembayaran Transfer';
            }
            if (empty($po->nama_rekening)) {
                $errors[] = 'Nama rekening wajib diisi untuk metode pembayaran Transfer';
            }
            if (empty($po->no_rekening)) {
                $errors[] = 'No. rekening wajib diisi untuk metode pembayaran Transfer';
            }
        } elseif ($po->metode_pembayaran === 'Cek/Giro') {
            if (empty($po->no_giro)) {
                $errors[] = 'No. Cek/Giro wajib diisi untuk metode pembayaran Cek/Giro';
            }
            if (empty($po->tanggal_giro)) {
                $errors[] = 'Tanggal Giro wajib diisi untuk metode pembayaran Cek/Giro';
            }
            if (empty($po->tanggal_cair)) {
                $errors[] = 'Tanggal Cair wajib diisi untuk metode pembayaran Cek/Giro';
            }
        }

        // Validasi dokumen - HAPUS validasi dokumen wajib (sama dengan edit)
        // if (empty($po->dokumen)) {
        //     $errors[] = 'Draft Invoice wajib diupload';
        // }

        return $errors;
    }

    // Download PDF
    public function download(PurchaseOrder $purchase_order)
    {
        try {
            Log::info('PurchaseOrder Download - Starting download for PO:', [
                'po_id' => $purchase_order->id,
                'user_id' => Auth::id()
            ]);

            $po = $purchase_order->load(['department', 'perihal', 'bank', 'items', 'termin']);

            // Calculate summary
            $total = 0;
            if ($po->items && count($po->items) > 0) {
                $total = $po->items->sum(function($item) {
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } else {
                // Fallback: untuk tipe Lainnya gunakan nominal, selain itu gunakan harga
                $total = ($po->tipe_po === 'Lainnya') ? ((float) ($po->nominal ?? 0)) : ((float) ($po->harga ?? 0));
            }

            $diskon = $po->diskon ?? 0;
            $dpp = max($total - $diskon, 0);
            $ppn = ($po->ppn ? $dpp * 0.11 : 0);

            // Hitung DPP khusus untuk PPh (hanya item bertipe 'Jasa')
            $dppPph = 0;
            if ($po->items && count($po->items) > 0) {
                $dppPph = $po->items->filter(function($item) {
                    return isset($item->tipe) && strtolower($item->tipe) === 'jasa';
                })->sum(function($item) {
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } else if ($po->tipe_po === 'Lainnya') {
                // Fallback PPh base untuk tipe Lainnya tanpa item: gunakan nominal
                $dppPph = (float) ($po->nominal ?? 0);
            }

            $pphPersen = 0;
            $pph = 0;
            if ($po->pph_id) {
                $pphModel = \App\Models\Pph::find($po->pph_id);
                if ($pphModel) {
                    $pphPersen = $pphModel->tarif_pph ?? 0;
                    $pph = $dppPph * ($pphPersen / 100);
                }
            }

            $grandTotal = $dpp + $ppn + $pph;

            // Format date in Indonesian
            $tanggal = $po->tanggal
                ? Carbon::parse($po->tanggal)->locale('id')->translatedFormat('d F Y')
                : Carbon::now()->locale('id')->translatedFormat('d F Y');

            // Clean filename - remove invalid characters
            $filename = 'PurchaseOrder_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $po->no_po ?? 'Draft') . '.pdf';

            Log::info('PurchaseOrder Download - Generated filename:', ['filename' => $filename]);

            // Use base64 encoded images for PDF to avoid path issues
            $logoSrc = $this->getBase64Image('images/company-logo.png');
            $signatureSrc = $this->getBase64Image('images/signature.png');
            $approvedSrc = $this->getBase64Image('images/approved.png');

            // Create PDF with optimized settings
            $pdf = Pdf::loadView('purchase_order_pdf', [
                'po' => $po,
                'tanggal' => $tanggal,
                'total' => $total,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'pph' => $pph,
                'pphPersen' => $pphPersen,
                'grandTotal' => $grandTotal,
                'cicilan' => $po->cicilan ?? 0,
                'logoSrc' => $logoSrc,
                'signatureSrc' => $signatureSrc,
                'approvedSrc' => $approvedSrc,
            ])
            ->setOptions(config('dompdf.options'))
            ->setPaper('a4', 'portrait');

            Log::info('PurchaseOrder Download - PDF generated successfully, returning download response');

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            Log::error('PurchaseOrder Download - Error occurred:', [
                'po_id' => $purchase_order->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    // Preview PDF in browser without forcing download
    public function preview(PurchaseOrder $purchase_order)
    {
        try {
            Log::info('PurchaseOrder Preview - Starting preview for PO:', [
                'po_id' => $purchase_order->id,
                'user_id' => Auth::id()
            ]);

            $po = $purchase_order->load(['department', 'perihal', 'bank', 'items', 'termin']);

            // Calculate summary (same as download)
            $total = 0;
            if ($po->items && count($po->items) > 0) {
                $total = $po->items->sum(function($item) {
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
            });
            } else {
                // Fallback: untuk tipe Lainnya gunakan nominal, selain itu gunakan harga
                $total = ($po->tipe_po === 'Lainnya') ? ((float) ($po->nominal ?? 0)) : ((float) ($po->harga ?? 0));
            }

            $diskon = $po->diskon ?? 0;
            $dpp = max($total - $diskon, 0);
            $ppn = ($po->ppn ? $dpp * 0.11 : 0);

            // Hitung DPP khusus untuk PPh (hanya item bertipe 'Jasa')
            $dppPph = 0;
            if ($po->items && count($po->items) > 0) {
                $dppPph = $po->items->filter(function($item) {
                    return isset($item->tipe) && strtolower($item->tipe) === 'jasa';
                })->sum(function($item) {
                    return ($item->qty ?? 1) * ($item->harga ?? 0);
                });
            } else if ($po->tipe_po === 'Lainnya') {
                // Fallback PPh base untuk tipe Lainnya tanpa item: gunakan nominal
                $dppPph = (float) ($po->nominal ?? 0);
            }

            $pphPersen = 0;
            $pph = 0;
            if ($po->pph_id) {
                $pphModel = \App\Models\Pph::find($po->pph_id);
                if ($pphModel) {
                    $pphPersen = $pphModel->tarif_pph ?? 0;
                    $pph = $dppPph * ($pphPersen / 100);
                }
            }

            $grandTotal = $dpp + $ppn + $pph;
            $tanggal = $po->tanggal
                ? Carbon::parse($po->tanggal)->locale('id')->translatedFormat('d F Y')
                : Carbon::now()->locale('id')->translatedFormat('d F Y');

            Log::info('PurchaseOrder Preview - Data prepared, rendering view');

            // Render the blade directly so you can live-refresh styles
            return view('purchase_order_pdf', [
                'po' => $po,
                'tanggal' => $tanggal,
                'total' => $total,
                'diskon' => $diskon,
                'ppn' => $ppn,
                'pph' => $pph,
                'pphPersen' => $pphPersen,
                'grandTotal' => $grandTotal,
                'cicilan' => $po->cicilan ?? 0,
                // Use URL for browser preview
                'logoSrc' => asset('images/company-logo.png'),
                'signatureSrc' => asset('images/signature.png'),
                'approvedSrc' => asset('images/approved.png'),
            ]);
} catch (\Exception $e) {
            Log::error('PurchaseOrder Preview - Error occurred:', [
                'po_id' => $purchase_order->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to generate preview: ' . $e->getMessage()], 500);
}
}

    // Helper method to convert image to base64 for PDF
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

    // Log activity
    public function log(PurchaseOrder $purchase_order, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $po = \App\Models\PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($purchase_order->id);

        $logsQuery = \App\Models\PurchaseOrderLog::with(['user.department', 'user.role'])
            ->where('purchase_order_id', $po->id);

        // Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $logsQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', "%$search%")
                    ->orWhere('action', 'like', "%$search%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%$search%");
});
});
}
        if ($request->filled('action')) {
            $logsQuery->where('action', $request->input('action'));
}
        if ($request->filled('role')) {
            $roleId = $request->input('role');
            $logsQuery->whereHas('user.role', function ($q) use ($roleId) {
                $q->where('id', $roleId);
});
}
        if ($request->filled('department')) {
            $departmentId = $request->input('department');
            $logsQuery->whereHas('user.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
});
}
        if ($request->filled('date')) {
            $logsQuery->whereDate('created_at', $request->input('date'));
}

        $perPage = (int) $request->input('per_page', 10);
        $logs = $logsQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = \App\Models\PurchaseOrderLog::where('purchase_order_id', $po->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        $filters = $request->only(['search', 'action', 'role', 'department', 'date', 'per_page']);

        if ($request->wantsJson()) {
            return response()->json([
                'purchaseOrder' => $po,
                'logs' => $logs,
                'filters' => $filters,
                'roleOptions' => $roleOptions,
                'departmentOptions' => $departmentOptions,
                'actionOptions' => $actionOptions,
            ]);
}

        return Inertia::render('purchase-orders/Log', [
            'purchaseOrder' => $po,
            'logs' => $logs,
            'filters' => $filters,
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
}

    /**
     * Format validation errors menjadi pesan yang user-friendly
     */
    private function formatValidationErrors($errors)
    {
        $errorMessages = [];

        foreach ($errors->toArray() as $field => $messages) {
            $fieldLabel = $this->getFieldLabel($field);
            $errorMessages[$field] = $fieldLabel . ': ' . implode(', ', $messages);
        }

        return $errorMessages;
    }

    /**
     * Get user-friendly field labels
     */
    private function getFieldLabel($field)
    {
        $labels = [
            'tipe_po' => 'Tipe PO',
            'department_id' => 'Departemen',
            'perihal_id' => 'Perihal',
            'supplier_id' => 'Supplier',
            'harga' => 'Harga',
            'detail_keperluan' => 'Detail Keperluan',
            'metode_pembayaran' => 'Metode Pembayaran',
            'bank_id' => 'Bank',
            'nama_rekening' => 'Nama Rekening',
            'no_rekening' => 'No. Rekening',
            'no_giro' => 'No. Cek/Giro',
            'tanggal_giro' => 'Tanggal Giro',
            'tanggal_cair' => 'Tanggal Cair',
            'no_kartu_kredit' => 'No. Kartu Kredit',
            'barang' => 'Barang',
            'barang.*.nama' => 'Nama Barang',
            'barang.*.qty' => 'Quantity',
            'barang.*.satuan' => 'Satuan',
            'barang.*.harga' => 'Harga Barang',
            'diskon' => 'Diskon',
            'ppn' => 'PPN',
            'pph_id' => 'PPH',
            'cicilan' => 'Cicilan',
            'termin' => 'Termin',
            'termin_id' => 'No. Referensi Termin',
            'nominal' => 'Nominal',
            'keterangan' => 'Keterangan',
            'note' => 'Note',
            'dokumen' => 'Draft Invoice',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
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

    /**
     * Get AR Partners for customer selection
     */
    public function getArPartners(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);
            $departmentId = $request->input('department_id');

            $query = \App\Models\ArPartner::select('id', 'nama_ap', 'jenis_ap', 'alamat', 'email', 'no_telepon')
                ->orderBy('nama_ap');

            if ($search) {
                $query->where('nama_ap', 'like', "%{$search}%");
            }
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }

            $arPartners = $query->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $arPartners
            ]);
        } catch (\Exception $e) {
            Log::error('Get AR Partners Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat data Customer'
            ], 500);
        }
    }
}
