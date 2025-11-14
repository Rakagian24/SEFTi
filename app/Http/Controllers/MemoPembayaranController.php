<?php

namespace App\Http\Controllers;

use App\Models\MemoPembayaran;
use App\Models\Department;
use App\Models\Perihal;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Bank;
use App\Models\Pph;
use App\Services\DepartmentService;
use App\Services\DocumentNumberService;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MemoPembayaranLog;
use Inertia\Inertia;
use Carbon\Carbon;

class MemoPembayaranController extends Controller
{
    protected $approvalWorkflowService;

    public function __construct(ApprovalWorkflowService $approvalWorkflowService)
    {
        $this->authorizeResource(\App\Models\MemoPembayaran::class, 'memo_pembayaran');
        $this->approvalWorkflowService = $approvalWorkflowService;
    }

    // List + filter
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRoleName = $user->role->name ?? '';

        // Use DepartmentScope by default so 'All' access works; for Staff Toko/Digital Marketing, bypass scope and restrict to own docs
        if (in_array(strtolower($userRoleName), ['staff toko','staff digital marketing'], true)) {
            $query = MemoPembayaran::withoutGlobalScope(\App\Scopes\DepartmentScope::class)->with([
                'department',
                'purchaseOrder.perihal',
                'purchaseOrder.supplier',
                'supplier',
                'bankSupplierAccount.bank',
                'creditCard.bank',
                'creator'
            ])->where('created_by', $user->id);
        } else {
            $query = MemoPembayaran::query()->with([
            'department',
            'purchaseOrder.perihal',
            'purchaseOrder.supplier',
            'supplier',
            'bankSupplierAccount.bank',
            'creditCard.bank',
            'creator'
            ]);
        }

        // Batasi visibilitas Draft: hanya untuk role Staff (Toko, Digital Marketing, Akunting & Finance)
        $staffRolesAllowedDraft = ['Staff Toko', 'Staff Digital Marketing', 'Staff Akunting & Finance', 'Admin', 'Kepala Toko'];
        if (!in_array($userRoleName, $staffRolesAllowedDraft, true)) {
            $query->where('status', '!=', 'Draft');
        }

        // Staff Toko & Staff Digital Marketing handled above (bypass scope + created_by)

        // Filter dinamis
        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
        }

        if ($request->filled('no_mb') && !empty(trim($request->no_mb))) {
            $query->where('no_mb', 'like', '%'.trim($request->no_mb).'%');
        }

        if ($request->filled('department_id') && $request->department_id !== '') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->filled('metode_pembayaran') && $request->metode_pembayaran !== '') {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
        }

        if ($request->filled('supplier_id') && $request->supplier_id !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id)
                  ->orWhereHas('purchaseOrder', function ($subQ) use ($request) {
                      $subQ->where('supplier_id', $request->supplier_id);
                  });
            });
        }

        // Free text search across common columns
        if ($request->filled('search') && !empty(trim($request->search))) {
            $search = trim($request->search);
            $searchColumns = $request->filled('search_columns') ? explode(',', $request->search_columns) : [];

            $query->where(function ($q) use ($search, $searchColumns) {
                // If specific columns are specified, search only in those columns
                if (!empty($searchColumns)) {
                    foreach ($searchColumns as $column) {
                        switch ($column) {
                            case 'no_mb':
                                $q->orWhere('no_mb', 'like', '%'.$search.'%');
                                break;
                            case 'no_po':
                                $q->orWhereHas('purchaseOrder', function ($subQ) use ($search) {
                                    $subQ->where('no_po', 'like', '%'.$search.'%');
                                });
                                break;
                            case 'supplier':
                                $q->orWhere(function ($supplierQ) use ($search) {
                                    $supplierQ->whereHas('supplier', function ($subQ) use ($search) {
                                        $subQ->where('nama_supplier', 'like', '%'.$search.'%');
                                    })->orWhereHas('purchaseOrder.supplier', function ($subQ) use ($search) {
                                        $subQ->where('nama_supplier', 'like', '%'.$search.'%');
                                    });
                                });
                                break;
                            case 'tanggal':
                                $q->orWhere('tanggal', 'like', '%'.$search.'%');
                                break;
                            case 'status':
                                $q->orWhere('status', 'like', '%'.$search.'%');
                                break;
                            case 'perihal':
                                $q->orWhereHas('purchaseOrder.perihal', function ($subQ) use ($search) {
                                    $subQ->where('nama', 'like', '%'.$search.'%');
                                });
                                break;
                            case 'department':
                                $q->orWhereHas('department', function ($subQ) use ($search) {
                                    $subQ->where('name', 'like', '%'.$search.'%');
                                });
                                break;
                            case 'metode_pembayaran':
                                $q->orWhere('metode_pembayaran', 'like', '%'.$search.'%');
                                break;
                            case 'grand_total':
                            case 'total':
                                $q->orWhereRaw('CAST(total AS CHAR) LIKE ?', ['%'.$search.'%'])
                                  ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ['%'.$search.'%']);
                                break;
                            case 'nama_rekening':
                                $q->orWhere('nama_rekening', 'like', '%'.$search.'%');
                                break;
                            case 'no_rekening':
                                $q->orWhere('no_rekening', 'like', '%'.$search.'%');
                                break;
                            case 'no_kartu_kredit':
                                $q->orWhere('no_kartu_kredit', 'like', '%'.$search.'%');
                                break;
                            case 'no_giro':
                                $q->orWhere('no_giro', 'like', '%'.$search.'%');
                                break;
                            case 'keterangan':
                                $q->orWhere('keterangan', 'like', '%'.$search.'%');
                                break;
                            case 'created_by':
                                $q->orWhereHas('creator', function ($subQ) use ($search) {
                                    $subQ->where('name', 'like', '%'.$search.'%');
                                });
                                break;
                        }
                    }
                } else {
                    // Default search across common columns
                    $q->where('no_mb', 'like', '%'.$search.'%')
                      ->orWhere('keterangan', 'like', '%'.$search.'%')
                      ->orWhere('status', 'like', '%'.$search.'%')
                      ->orWhere('tanggal', 'like', '%'.$search.'%')
                      ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ['%'.$search.'%'])
                      ->orWhereHas('department', function ($subQ) use ($search) {
                          $subQ->where('name', 'like', '%'.$search.'%');
                      })
                      ->orWhereHas('purchaseOrder', function ($subQ) use ($search) {
                          $subQ->where('no_po', 'like', '%'.$search.'%');
                      })
                      ->orWhereHas('supplier', function ($subQ) use ($search) {
                          $subQ->where('nama_supplier', 'like', '%'.$search.'%');
                      });
                }
            });
        }

        // Removed default current-month filter; no implicit date filtering

        // Pagination
        $perPage = $request->get('per_page', 10);
        $memoPembayarans = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get filter options
        $departments = DepartmentService::getOptionsForFilter();
        // Get unique values for dynamic filters
        $statusOptions = MemoPembayaran::select('status')->distinct()->pluck('status')->filter();
        $metodePembayaranOptions = MemoPembayaran::select('metode_pembayaran')->distinct()->pluck('metode_pembayaran')->filter();

        return Inertia::render('memo-pembayaran/Index', [
            'memoPembayarans' => $memoPembayarans,
            'filters' => $request->only(['tanggal_start', 'tanggal_end', 'no_mb', 'department_id', 'status', 'metode_pembayaran', 'supplier_id', 'search', 'search_columns', 'per_page']),
            'departments' => $departments,
            'statusOptions' => $statusOptions,
            'metodePembayaranOptions' => $metodePembayaranOptions,
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        $purchaseOrders = PurchaseOrder::where('status', 'Approved')
            ->where('dp_active', false)
            ->where(function ($query) {
                // PO tipe Reguler: perihal Jasa atau Barang/Jasa
                $query->where(function ($q) {
                    $q->where('tipe_po', 'Reguler')
                      ->whereHas('perihal', function ($perihalQuery) {
                          $perihalQuery->whereIn('nama', ['Permintaan Pembayaran Jasa', 'Permintaan Pembayaran Barang/Jasa']);
                      });
                })
                // PO tipe Lainnya: semua perihal diperbolehkan
                ->orWhere('tipe_po', 'Lainnya');
            })
            ->with(['perihal', 'supplier', 'bankSupplierAccount.bank', 'bank'])
            ->orderBy('created_at', 'desc')
            ->get();

        $banks = Bank::active()
            ->orderBy('nama_bank')
            ->get();

        $creditCards = \App\Models\CreditCard::active()
            ->with('bank')
            ->orderBy('no_kartu_kredit')
            ->get(['id', 'no_kartu_kredit', 'nama_pemilik', 'bank_id']);

        return Inertia::render('memo-pembayaran/Create', [
            'purchaseOrders' => $purchaseOrders,
            'banks' => $banks,
            'creditCards' => $creditCards,
        ]);
    }


    /**
     * Search approved Purchase Orders for Memo Pembayaran selection
     */
    public function searchPurchaseOrders(Request $request)
    {
        $search = $request->input('search');
        $supplierId = $request->input('supplier_id');
        $metode = $request->input('metode_pembayaran');
        $noGiro = $request->input('no_giro');
        $noKartuKredit = $request->input('no_kartu_kredit');
        $perPage = (int) $request->input('per_page', 20);
        $status = $request->input('status');

        $query = PurchaseOrder::query()
            ->with(['perihal', 'supplier', 'department', 'termin', 'bankSupplierAccount.bank', 'bank'])
            ->where('dp_active', false)
            ->where(function ($q) {
                // PO tipe Reguler: perihal Jasa atau Barang/Jasa
                $q->where(function ($subQ) {
                    $subQ->where('tipe_po', 'Reguler')
                         ->whereHas('perihal', function ($perihalQuery) {
                             $perihalQuery->whereIn('nama', ['Permintaan Pembayaran Jasa', 'Permintaan Pembayaran Barang/Jasa']);
                         });
                })
                // PO tipe Lainnya: semua perihal diperbolehkan
                ->orWhere('tipe_po', 'Lainnya');
            });

        // Khusus Staff Toko & Staff Digital Marketing: hanya tampilkan PO yang dibuat sendiri dan bypass DepartmentScope
        $user = Auth::user();
        $role = strtolower(optional($user->role)->name ?? '');
        if (in_array($role, ['staff toko','staff digital marketing'], true)) {
            $query = PurchaseOrder::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['perihal', 'supplier', 'department', 'termin', 'bankSupplierAccount.bank', 'bank'])
                ->where('dp_active', false)
                ->where(function ($q) {
                    // PO tipe Reguler: perihal Jasa atau Barang/Jasa
                    $q->where(function ($subQ) {
                        $subQ->where('tipe_po', 'Reguler')
                             ->whereHas('perihal', function ($perihalQuery) {
                                 $perihalQuery->whereIn('nama', ['Permintaan Pembayaran Jasa', 'Permintaan Pembayaran Barang/Jasa']);
                             });
                    })
                    // PO tipe Lainnya: semua perihal diperbolehkan
                    ->orWhere('tipe_po', 'Lainnya');
                })
                ->where('created_by', $user->id);
        }

        // Izinkan PO yang sudah pernah digunakan; validasi nominal akan ditangani saat submit berdasarkan outstanding

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'Approved');
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($metode) {
            $query->where('metode_pembayaran', $metode);
        }
        if ($noGiro) {
            $query->where('no_giro', $noGiro);
        }
        if ($noKartuKredit) {
            $query->where('no_kartu_kredit', $noKartuKredit);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                ->orWhere('no_giro', 'like', "%{$search}%");
            });
        }

        try {
            $purchaseOrders = $query->orderByDesc('created_at')
                ->paginate($perPage)
                ->through(function($po) {
                    try {
                        // Load termin manually if not loaded via relationship
                        $terminData = null;
                        if ($po->termin_id) {
                            if ($po->termin) {
                                $terminData = [
                                    'id' => $po->termin->id,
                                    'nama' => $po->termin->nama,
                                    'jumlah_termin' => $po->termin->jumlah_termin,
                                    'jumlah_termin_dibuat' => $po->termin->jumlah_termin_dibuat,
                                    'total_cicilan' => $po->termin->total_cicilan,
                                    'sisa_pembayaran' => $po->termin->sisa_pembayaran,
                                    'status' => $po->termin->status,
                                    'status_termin' => $po->termin->status_termin,
                                    'grand_total' => $po->termin->grand_total,
                                    'no_referensi' => $po->termin->no_referensi,
                                ];
                            } else {
                                // Try to load termin manually
                                $termin = \App\Models\Termin::find($po->termin_id);
                                if ($termin) {
                                    $terminData = [
                                        'id' => $termin->id,
                                        'nama' => $termin->nama,
                                        'jumlah_termin' => $termin->jumlah_termin,
                                        'jumlah_termin_dibuat' => $termin->jumlah_termin_dibuat,
                                        'total_cicilan' => $termin->total_cicilan,
                                        'sisa_pembayaran' => $termin->sisa_pembayaran,
                                        'status' => $termin->status,
                                        'status_termin' => $termin->status_termin,
                                        'grand_total' => $termin->grand_total,
                                        'no_referensi' => $termin->no_referensi,
                                    ];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error processing PO ' . $po->id . ': ' . $e->getMessage());
                        $terminData = null;
                    }

                // Hitung outstanding berdasarkan total Memo Pembayaran non-draft/non-canceled/non-rejected
                $usedTotal = (float) DB::table('memo_pembayarans')
                    ->where('purchase_order_id', $po->id)
                    ->whereNotIn('status', ['Draft', 'Canceled', 'Rejected'])
                    ->sum('total');
                $grandTotal = (float) ($po->grand_total ?? $po->total ?? 0);
                $outstanding = max(0, $grandTotal - $usedTotal);

                return [
                    'id' => $po->id,
                    'no_po' => $po->no_po,
                    'no_invoice' => $po->no_invoice,
                    'tanggal' => optional($po->tanggal)->toDateString(),
                    'perihal' => $po->perihal ? [
                        'id' => $po->perihal->id,
                        'nama' => $po->perihal->nama
                    ] : null,
                    'total' => $po->total,
                    'grand_total' => $grandTotal,
                    'outstanding' => $outstanding,
                    'metode_pembayaran' => $po->metode_pembayaran,
                    'bank_id' => $po->bank_id,
                    // Root-level fallbacks for bank info (historical fields)
                    'nama_rekening' => $po->nama_rekening,
                    'no_rekening' => $po->no_rekening,
                    'no_giro' => $po->no_giro,
                    'no_kartu_kredit' => $po->no_kartu_kredit,
                    'keterangan' => $po->keterangan,
                    'status' => $po->status,
                    'tipe_po' => $po->tipe_po,
                    'termin_id' => $po->termin_id,
                    'termin' => $terminData,
                    'department' => $po->department ? [
                        'id' => $po->department->id,
                        'nama' => $po->department->name,
                    ] : null,
                    // Include supplier contact info so UI can show phone/address
                    'supplier' => $po->supplier ? [
                        'id' => $po->supplier->id,
                        'nama_supplier' => $po->supplier->nama_supplier,
                        'no_telepon' => $po->supplier->no_telepon,
                        'alamat' => $po->supplier->alamat,
                        'email' => $po->supplier->email,
                    ] : null,
                    // Normalized bank account block (preferred by UI)
                    'bankSupplierAccount' => $po->bankSupplierAccount ? [
                        'id' => $po->bankSupplierAccount->id,
                        'nama_rekening' => $po->bankSupplierAccount->nama_rekening,
                        'no_rekening' => $po->bankSupplierAccount->no_rekening,
                        'bank' => $po->bankSupplierAccount->bank ? [
                            'id' => $po->bankSupplierAccount->bank->id,
                            'nama_bank' => $po->bankSupplierAccount->bank->nama_bank,
                        ] : null,
                    ] : null,
                    // Bank relation on PO (fallback)
                    'bank' => $po->bank ? [
                        'id' => $po->bank->id,
                        'nama_bank' => $po->bank->nama_bank,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $purchaseOrders->items(),
                'current_page' => $purchaseOrders->currentPage(),
                'last_page' => $purchaseOrders->lastPage(),
                'total_count' => $purchaseOrders->total(),
                'filter_info' => [
                    'supplier_id' => $supplierId,
                    'metode_pembayaran' => $metode,
                    'no_giro' => $noGiro,
                    'no_kartu_kredit' => $noKartuKredit,
                    'status' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in searchPurchaseOrders: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error occurred while searching purchase orders',
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Return suppliers list for Memo Pembayaran selects (not limited by PO)
     */
    public function suppliersOptions(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 100);

        $query = Supplier::query();
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

    /**
     * Return distinct cek/giro numbers from Approved Purchase Orders
     */
    public function giroNumbers(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 100);

        $query = PurchaseOrder::where('status', 'Approved')
            ->where('dp_active', false)
            ->whereNotNull('no_giro')
            ->where('no_giro', '!=', '')
            ->where(function ($q) {
                $q->where('metode_pembayaran', 'Cek/Giro')
                ->orWhere('metode_pembayaran', 'Cek / Giro')
                ->orWhere('metode_pembayaran', 'Giro')
                ->orWhere('metode_pembayaran', 'like', '%Giro%');
            })
            ->where(function ($query) {
                // PO tipe Reguler: perihal Jasa atau Barang/Jasa
                $query->where(function ($q) {
                    $q->where('tipe_po', 'Reguler')
                      ->whereHas('perihal', function ($perihalQuery) {
                          $perihalQuery->whereIn('nama', ['Permintaan Pembayaran Jasa', 'Permintaan Pembayaran Barang/Jasa']);
                      });
                })
                // PO tipe Lainnya: semua perihal diperbolehkan
                ->orWhere('tipe_po', 'Lainnya');
            })
            ->with(['perihal']); // Include perihal for better display

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('no_giro', 'like', "%{$search}%")
                ->orWhere('no_po', 'like', "%{$search}%")
                ->orWhereHas('perihal', function($perihalQuery) use ($search) {
                    $perihalQuery->where('nama', 'like', "%{$search}%");
                });
            });
        }

        $results = $query->orderByDesc('created_at')
        ->limit($perPage)
        ->get(['id', 'no_po', 'no_giro', 'perihal_id', 'total', 'tanggal_giro', 'tanggal_cair'])
        ->groupBy('no_giro')
        ->map(function ($items, $noGiro) {
            $first = $items->first();
            return [
                'label' => $noGiro,
                'value' => $noGiro,
                'tanggal_giro' => $first->tanggal_giro,
                'tanggal_cair' => $first->tanggal_cair,
                'purchase_orders' => $items->map(function ($po) {
                    return [
                        'id' => $po->id,
                        'no_po' => $po->no_po,
                        'total' => $po->total,
                        'perihal' => $po->perihal ? $po->perihal->nama : null,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function store(Request $request)
    {
        $baseRules = [
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'cicilan' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:65535',
            'action' => 'required|in:draft,send',
            // Items & tax inputs (optional, for BPB-like calculation)
            'items' => 'nullable|array',
            'items.*.purchase_order_item_id' => 'required_with:items|integer',
            'items.*.qty' => 'required_with:items|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'use_ppn' => 'nullable|boolean',
            'ppn_rate' => 'nullable|numeric|min:0',
            'use_pph' => 'nullable|boolean',
            'pph_rate' => 'nullable|numeric|min:0',
        ];

        if ($request->input('action') === 'send') {
            $baseRules = array_merge($baseRules, [
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'metode_pembayaran' => 'required|in:Transfer,Kredit',
                // Note is optional
                // Conditional requirements
                'supplier_id' => 'required_if:metode_pembayaran,Transfer|nullable|exists:suppliers,id',
                'credit_card_id' => 'required_if:metode_pembayaran,Kredit|nullable|exists:credit_cards,id',
            ]);
        } else {
            // Draft boleh kosong
            $baseRules = array_merge($baseRules, [
                'total' => 'nullable|numeric|min:0',
                'metode_pembayaran' => 'nullable|in:Transfer,Kredit',
                'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
                'credit_card_id' => 'nullable|exists:credit_cards,id',
                'no_giro' => 'nullable|string',
                'tanggal_giro' => 'nullable|date',
                'tanggal_cair' => 'nullable|date',
                // Allow supplier_id to be provided on draft without PO
                'supplier_id' => 'nullable|integer|exists:suppliers,id',
            ]);
        }

        $request->validate($baseRules);

        // Custom validation for purchase order
        if ($request->input('action') === 'send' && $request->purchase_order_id) {
            $po = PurchaseOrder::find($request->purchase_order_id);

            // Allow multiple memos per PO but enforce outstanding cap
            // Determine outstanding = grand_total - sum(total of existing memos except Draft/Canceled/Rejected)
            if ($po) {
                $usedTotal = (float) DB::table('memo_pembayarans')
                    ->where('purchase_order_id', $po->id)
                    ->whereNotIn('status', ['Draft', 'Canceled', 'Rejected'])
                    ->sum('total');
                $grandTotal = (float) ($po->grand_total ?? $po->total ?? 0);
                $outstanding = max(0, $grandTotal - $usedTotal);

                $requestedNominal = (float) ($request->total ?? 0);
                if ($po->tipe_po === 'Lainnya' && $po->termin_id) {
                    $requestedNominal = (float) ($request->cicilan ?? 0);
                }

                if ($requestedNominal <= 0) {
                    return back()->withErrors([
                        'total' => 'Nominal harus lebih dari 0'
                    ])->withInput();
                }
                if ($requestedNominal - $outstanding > 0.00001) {
                    return back()->withErrors([
                        'total' => 'Nominal melebihi sisa outstanding PO (maksimal ' . number_format($outstanding, 0, ',', '.') . ')'
                    ])->with('error', 'Nominal melebihi outstanding')->withInput();
                }
            }

            // Check termin completion for PO Lainnya
            if ($po && $po->tipe_po === 'Lainnya' && $po->termin_id) {
                $termin = $po->termin;
                if ($termin) {
                    $jumlahDibuat = $termin->jumlah_termin_dibuat;
                    $jumlahTotal = $termin->jumlah_termin;
                    $statusTermin = $termin->status_termin;

                    if (($statusTermin === 'completed') || ($jumlahTotal > 0 && $jumlahDibuat >= $jumlahTotal)) {
                        return back()->withErrors([
                            'purchase_order_id' => 'Termin untuk Purchase Order ini sudah selesai dan tidak bisa digunakan lagi'
                        ])->with('error', 'Termin sudah selesai untuk PO ini')->withInput();
                    }

                    // Check if previous memo pembayaran for this termin is approved
                    $approvedMemoCount = DB::table('memo_pembayarans')
                        ->join('purchase_orders', 'memo_pembayarans.purchase_order_id', '=', 'purchase_orders.id')
                        ->where('purchase_orders.termin_id', $termin->id)
                        ->where('memo_pembayarans.status', 'Approved')
                        ->count();

                    $totalMemoCount = DB::table('memo_pembayarans')
                        ->join('purchase_orders', 'memo_pembayarans.purchase_order_id', '=', 'purchase_orders.id')
                        ->where('purchase_orders.termin_id', $termin->id)
                        ->whereNotIn('memo_pembayarans.status', ['Draft', 'Canceled'])
                        ->count();

                    // If this is not the first memo and previous memo is not approved yet
                    if ($totalMemoCount > 0 && $approvedMemoCount < $totalMemoCount) {
                        return back()->withErrors([
                            'purchase_order_id' => 'Memo Pembayaran sebelumnya untuk termin ini belum di-approve. Harap tunggu approval terlebih dahulu.'
                        ])->with('error', 'Memo sebelumnya belum approved')->withInput();
                    }
                }
            }

            // Check if purchase order has Approved status
            if ($po && $po->status !== 'Approved') {
                return back()->withErrors([
                    'purchase_order_id' => 'Purchase Order ' . $po->no_po . ' belum disetujui'
                ])->withInput();
            }

            // Simplified: do not enforce giro/credit card or total consistency here

            // Enforce final-termin cicilan rule: last step must consume remaining amount exactly
            if ($po && $po->tipe_po === 'Lainnya' && $po->termin_id) {
                $termin = $po->termin; // already loaded earlier if exists
                if ($termin) {
                    $jumlahDibuat = (int) ($termin->jumlah_termin_dibuat ?? 0);
                    $jumlahTotal = (int) ($termin->jumlah_termin ?? 0);
                    $sisa = (float) ($termin->sisa_pembayaran ?? 0);
                    // This send would create the next memo, so check if it's the last one
                    if ($jumlahTotal > 0 && ($jumlahDibuat + 1) === $jumlahTotal) {
                        $cicilanInput = (float) ($request->cicilan ?? 0);
                        if (abs($cicilanInput - $sisa) > 0.001) {
                            return back()->withErrors([
                                'cicilan' => 'Pada tahap termin terakhir, nilai Cicilan harus sama dengan Sisa Pembayaran (' . number_format($sisa, 0, ',', '.') . ')'
                            ])->withInput();
                        }
                    }
                }

                // Enforce final-termin cicilan rule for update: last step must consume remaining amount exactly
                if ($po->tipe_po === 'Lainnya' && $po->termin_id) {
                    $termin = $po->termin;
                    if ($termin) {
                        $jumlahDibuat = (int) ($termin->jumlah_termin_dibuat ?? 0);
                        $jumlahTotal = (int) ($termin->jumlah_termin ?? 0);
                        $sisa = (float) ($termin->sisa_pembayaran ?? 0);
                        if ($jumlahTotal > 0 && ($jumlahDibuat + 1) === $jumlahTotal) {
                            $cicilanInput = (float) ($request->cicilan ?? 0);
                            if (abs($cicilanInput - $sisa) > 0.001) {
                                return back()->withErrors([
                                    'cicilan' => 'Pada tahap termin terakhir, nilai Cicilan harus sama dengan Sisa Pembayaran (' . number_format($sisa, 0, ',', '.') . ')'
                                ])->withInput();
                            }
                        }
                    }
                }
            }
        }

        try {
            DB::beginTransaction();

            // Log incoming request for debugging
            Log::info('MemoPembayaranController@store request', $request->all());

            $user = Auth::user();
            $department = $user->department;

            // Use department and supplier from selected PO as primary source
            $departmentId = null;
            $supplierId = null;
            $bankSupplierAccountId = null;
            if ($request->purchase_order_id) {
                $po = PurchaseOrder::select('department_id', 'supplier_id', 'bank_supplier_account_id')->find($request->purchase_order_id);
                if ($po) {
                    if ($po->department_id) {
                        $departmentId = $po->department_id;
                    }
                    if ($po->supplier_id) {
                        $supplierId = $po->supplier_id;
                    }
                    // Ambil bank supplier account dari PO terpilih
                    if ($po->bank_supplier_account_id) {
                        $bankSupplierAccountId = $po->bank_supplier_account_id;
                    }
                }
            }

            // If no PO provided, accept supplier_id from request (drafts) and try deriving department from supplier
            if (!$request->purchase_order_id) {
                if (!$supplierId && $request->filled('supplier_id')) {
                    $supplierId = (int) $request->input('supplier_id');
                }
                // Derive supplier from bank supplier account if available
                if (!$supplierId && $request->filled('bank_supplier_account_id')) {
                    $acc = DB::table('bank_supplier_accounts')
                        ->select('supplier_id')
                        ->where('id', $request->bank_supplier_account_id)
                        ->first();
                    if ($acc && $acc->supplier_id) {
                        $supplierId = (int) $acc->supplier_id;
                    }
                }
                // Jika tidak ada PO, gunakan bank supplier account dari request (draft)
                if ($request->filled('bank_supplier_account_id')) {
                    $bankSupplierAccountId = (int) $request->bank_supplier_account_id;
                }
                // If department still null, derive from supplier
                if (!$departmentId && $supplierId) {
                    $sup = Supplier::select('department_id')->find($supplierId);
                    if ($sup && $sup->department_id) {
                        $departmentId = (int) $sup->department_id;
                    }
                }
            }

            // Fallback to user's department if no PO department found
            if (!$departmentId) {
                $departmentId = $department->id ?? null;
            }

            // Determine status and auto-fill fields based on action
            $status = $request->action === 'send' ? 'In Progress' : 'Draft';
            $noMb = null;
            $tanggal = null;

            if ($request->action === 'send') {
                // Cek role pembuat
                $userRole = strtolower($user->role->name ?? '');
                if ($userRole === 'kepala toko') {
                    $status = 'Verified';
                } elseif ($userRole === 'kabag') {
                    $status = 'Approved';
                }
                // Determine alias from the department used (PO's department or user's department)
                $departmentAlias = null;
                if ($departmentId) {
                    $deptModel = \App\Models\Department::select('alias', 'name')->find($departmentId);
                    if ($deptModel) {
                        $departmentAlias = $deptModel->alias ?? substr($deptModel->name, 0, 3);
                    }
                }
                $departmentAlias = $departmentAlias ?: 'GEN';
                $noMb = DocumentNumberService::generateNumber('MP', null, $departmentId, $departmentAlias);
                $tanggal = now()->toDateString();
            }

            // Compute totals from items (BPB-like)
            $items = collect($request->input('items', []))
                ->filter(fn($it) => isset($it['purchase_order_item_id']))
                ->values();

            // Validate item qty against remaining per PO item (based on Approved BPBs)
            if ($request->purchase_order_id && $items->count() > 0) {
                $poId = (int) $request->purchase_order_id;
                // Lock PO items to prevent race conditions
                $poItemRows = DB::table('purchase_order_items')
                    ->where('purchase_order_id', $poId)
                    ->lockForUpdate()
                    ->get(['id','qty']);
                $poQtyById = $poItemRows->pluck('qty','id');

                // Sum received qty from Approved BPBs per purchase_order_item_id
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
                    $qty = (float) ($it['qty'] ?? 0);
                    if ($qty < 0 || $qty - $remaining > 0.000001) {
                        abort(422, "Qty untuk item PO #$poi melebihi sisa yang diperbolehkan");
                    }
                }
            }

            $diskonInput = (float) ($request->input('diskon', 0) ?? 0);
            $usePpn = filter_var($request->input('use_ppn', false), FILTER_VALIDATE_BOOLEAN);
            $ppnRate = (float) ($request->input('ppn_rate', 11) ?? 11);
            $usePph = filter_var($request->input('use_pph', false), FILTER_VALIDATE_BOOLEAN);
            $pphRate = (float) ($request->input('pph_rate', 0) ?? 0);

            $subtotal = 0.0;
            if ($request->purchase_order_id && $items->count() > 0) {
                $poItemPrices = PurchaseOrderItem::where('purchase_order_id', $request->purchase_order_id)
                    ->get()
                    ->keyBy('id');
                foreach ($items as $it) {
                    $row = $poItemPrices->get((int) $it['purchase_order_item_id']);
                    if (!$row) continue; // ignore unknown ids
                    $qty = max(0, (float) ($it['qty'] ?? 0));
                    $harga = (float) ($row->harga ?? 0);
                    $subtotal += ($qty * $harga);
                }
            }

            $dpp = max(0.0, $subtotal - $diskonInput);
            $ppnNominal = $usePpn ? ($dpp * ($ppnRate / 100.0)) : 0.0;
            $pphNominal = $usePph ? ($dpp * ($pphRate / 100.0)) : 0.0;
            $grandTotal = $dpp + $ppnNominal + $pphNominal;

            $effectiveTotal = $request->total;
            if ($request->purchase_order_id) {
                $poForAmount = PurchaseOrder::select('id', 'tipe_po', 'termin_id')->find($request->purchase_order_id);
                if ($poForAmount && $poForAmount->tipe_po === 'Lainnya' && $poForAmount->termin_id) {
                    $effectiveTotal = $request->cicilan ?? 0;
                } elseif ($grandTotal > 0) {
                    // Use computed grand total when items are provided
                    $effectiveTotal = $grandTotal;
                }
            }
            // If no PO provided but items exist, still use computed total
            if (!$request->purchase_order_id && $grandTotal > 0) {
                $effectiveTotal = $grandTotal;
            }

            $memoPembayaran = MemoPembayaran::create([
                'no_mb' => $noMb,
                'department_id' => $departmentId,
                'purchase_order_id' => $request->purchase_order_id,
                'supplier_id' => $supplierId,
                'total' => $effectiveTotal,
                'grand_total' => $effectiveTotal, // mirror for compatibility
                'cicilan' => $request->cicilan,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank_supplier_account_id' => $bankSupplierAccountId,
                'credit_card_id' => $request->credit_card_id,
                'no_giro' => $request->no_giro,
                'tanggal_giro' => $request->tanggal_giro,
                'tanggal_cair' => $request->tanggal_cair,
                'keterangan' => $request->keterangan,
                'diskon' => $diskonInput,
                'ppn' => $usePpn,
                'ppn_nominal' => $ppnNominal,
                'pph_nominal' => $pphNominal,
                'tanggal' => $tanggal,
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                // Auto-approve stamping for Kabag creator
                'approved_by' => ($status === 'Approved') ? Auth::id() : null,
                'approved_at' => ($status === 'Approved') ? now() : null,
            ]);


            // Log after create attempt
            Log::info('MemoPembayaranController@store created memo', ['memo' => $memoPembayaran]);

            // Purchase order is already linked via purchase_order_id field

            DB::commit();

            $message = $request->action === 'send'
                ? 'Memo Pembayaran berhasil dikirim'
                : 'Memo Pembayaran berhasil disimpan sebagai draft';

            return redirect()->route('memo-pembayaran.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating Memo Pembayaran', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat membuat Memo Pembayaran']);
        }
    }

    public function show(MemoPembayaran $memoPembayaran)
        {
        // Guard: Staff Toko & Staff Digital Marketing hanya bisa melihat dokumen miliknya
        // $user = Auth::user();
        // $roleLower = strtolower($user->role->name ?? '');
        // if (in_array($roleLower, ['staff toko','staff digital marketing'], true) && (int)$memoPembayaran->created_by !== (int)$user->id) {
        //     abort(403, 'Unauthorized');
        // }
        $memoPembayaran->load([
            'department',
            'purchaseOrder' => function ($q) {
                $q->withoutGlobalScopes();
            },
            'purchaseOrder.perihal',
            'purchaseOrder.termin',
            'purchaseOrder.supplier' => function ($q) {
                $q->withoutGlobalScopes();
            },
            'purchaseOrder.bankSupplierAccount.bank',
            'purchaseOrder.creditCard.bank',
            'supplier',
            'bank',
            'bankSupplierAccount',
            'bankSupplierAccount.bank',
            'creditCard',
            'creator',
            'updater',
            'canceler',
            'approver',
            'rejecter'
        ]);

            $data = $memoPembayaran->toArray();
            $data['purchaseOrder'] = $memoPembayaran->purchaseOrder ? $memoPembayaran->purchaseOrder->toArray() : null;

            return Inertia::render('memo-pembayaran/Detail', [
                'memoPembayaran' => $data,
            ]);
        }

    public function edit(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeEditedByUser(Auth::user())) {
            return redirect()->route('memo-pembayaran.index')
                ->with('error', 'Memo Pembayaran tidak dapat diedit');
        }

        $purchaseOrders = PurchaseOrder::where('status', 'Approved')
            ->where('dp_active', false)
            ->whereHas('perihal', function ($query) {
                $query->where('nama', 'Permintaan Pembayaran Jasa');
            })
            ->with(['perihal', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->get();

        $banks = Bank::active()
            ->orderBy('nama_bank')
            ->get();

        $creditCards = \App\Models\CreditCard::active()
            ->with('bank')
            ->orderBy('no_kartu_kredit')
            ->get(['id', 'no_kartu_kredit', 'nama_pemilik', 'bank_id']);

        $memoPembayaran->load([
            'department',
            'purchaseOrder' => function ($q) {
                $q->withoutGlobalScopes();
            },
            'purchaseOrder.perihal',
            'purchaseOrder.supplier' => function ($q) {
                $q->withoutGlobalScopes();
            },
            'purchaseOrder.termin',
            'supplier' => function ($q) {
                $q->withoutGlobalScopes();
            },
            'bankSupplierAccount.bank',
            'creditCard.bank'
        ]);

        return Inertia::render('memo-pembayaran/Edit', [
            'memoPembayaran' => $memoPembayaran,
            'purchaseOrders' => $purchaseOrders,
            'banks' => $banks,
            'creditCards' => $creditCards,
        ]);
    }


    public function update(Request $request, MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeEditedByUser(Auth::user())) {
            return redirect()->route('memo-pembayaran.index')->with('error', 'Memo Pembayaran tidak dapat diedit');
        }

        // Base rules
        $rules = [
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'cicilan' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'required|in:Transfer,Kredit',
            'keterangan' => 'nullable|string|max:65535',
            'action' => 'required|in:draft,send',
            // Allow supplier on edit for drafts without PO
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            // Items & taxes (BPB-like)
            'items' => 'nullable|array',
            'items.*.purchase_order_item_id' => 'required_with:items|integer',
            'items.*.qty' => 'required_with:items|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'use_ppn' => 'nullable|boolean',
            'ppn_rate' => 'nullable|numeric|min:0',
            'use_pph' => 'nullable|boolean',
            'pph_rate' => 'nullable|numeric|min:0',
        ];

        // Kondisional total
        if ($request->input('action') === 'send') {
            // On send, require minimal fields according to business rules (Note optional)
            $rules['purchase_order_id'] = 'required|exists:purchase_orders,id';
        } else {
            $rules['total'] = 'nullable|numeric|min:0';
        }

        // Kondisional field lain
        if ($request->input('action') === 'send') {
            $rules = array_merge($rules, [
                'supplier_id' => 'required_if:metode_pembayaran,Transfer|nullable|exists:suppliers,id',
                'credit_card_id' => 'required_if:metode_pembayaran,Kredit|nullable|exists:credit_cards,id',
            ]);
        } else {
            $rules = array_merge($rules, [
                'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
                'credit_card_id' => 'nullable|exists:credit_cards,id',
                'no_giro' => 'nullable|string',
                'tanggal_giro' => 'nullable|date',
                'tanggal_cair' => 'nullable|date',
            ]);
        }

        $request->validate($rules);

        // Custom validation for purchase order
        if ($request->input('action') === 'send' && $request->purchase_order_id) {
            $po = PurchaseOrder::find($request->purchase_order_id);
            if ($po) {
                // Allow multiple memos per PO but enforce outstanding cap on update as well
                $usedTotal = (float) DB::table('memo_pembayarans')
                    ->where('purchase_order_id', $po->id)
                    ->where('id', '!=', $memoPembayaran->id)
                    ->whereNotIn('status', ['Draft', 'Canceled', 'Rejected'])
                    ->sum('total');
                $grandTotal = (float) ($po->grand_total ?? $po->total ?? 0);
                $outstanding = max(0, $grandTotal - $usedTotal);

                $requestedNominal = (float) ($request->total ?? 0);
                if ($po->tipe_po === 'Lainnya' && $po->termin_id) {
                    $requestedNominal = (float) ($request->cicilan ?? 0);
                }

                if ($requestedNominal <= 0) {
                    return back()->withErrors([
                        'total' => 'Nominal harus lebih dari 0'
                    ])->withInput();
                }
                if ($requestedNominal - $outstanding > 0.00001) {
                    return back()->withErrors([
                        'total' => 'Nominal melebihi sisa outstanding PO (maksimal ' . number_format($outstanding, 0, ',', '.') . ')'
                    ])->with('error', 'Nominal melebihi outstanding')->withInput();
                }
                // Check termin completion for PO Lainnya
                if ($po->tipe_po === 'Lainnya' && $po->termin_id) {
                    $termin = $po->termin;
                    if ($termin) {
                        $jumlahDibuat = $termin->jumlah_termin_dibuat;
                        $jumlahTotal = $termin->jumlah_termin;
                        $statusTermin = $termin->status_termin;

                        if (($statusTermin === 'completed') || ($jumlahTotal > 0 && $jumlahDibuat >= $jumlahTotal)) {
                            return back()->withErrors([
                                'purchase_order_id' => 'Termin untuk Purchase Order ini sudah selesai dan tidak bisa digunakan lagi'
                            ])->with('error', 'Termin sudah selesai untuk PO ini')->withInput();
                        }

                        // Check if previous memo pembayaran for this termin is approved (only for new PO selection)
                        if ($memoPembayaran->purchase_order_id !== $request->purchase_order_id) {
                            $approvedMemoCount = DB::table('memo_pembayarans')
                                ->join('purchase_orders', 'memo_pembayarans.purchase_order_id', '=', 'purchase_orders.id')
                                ->where('purchase_orders.termin_id', $termin->id)
                                ->where('memo_pembayarans.status', 'Approved')
                                ->count();

                            $totalMemoCount = DB::table('memo_pembayarans')
                                ->join('purchase_orders', 'memo_pembayarans.purchase_order_id', '=', 'purchase_orders.id')
                                ->where('purchase_orders.termin_id', $termin->id)
                                ->whereNotIn('memo_pembayarans.status', ['Draft', 'Canceled'])
                                ->count();

                            // If this is not the first memo and previous memo is not approved yet
                            if ($totalMemoCount > 0 && $approvedMemoCount < $totalMemoCount) {
                                return back()->withErrors([
                                    'purchase_order_id' => 'Memo Pembayaran sebelumnya untuk termin ini belum di-approve. Harap tunggu approval terlebih dahulu.'
                                ])->with('error', 'Memo sebelumnya belum approved')->withInput();
                            }
                        }
                    }
                }
            }
        }

        try {
            DB::beginTransaction();

            // Tentukan status
            $status = $request->action === 'send' ? 'In Progress' : 'Draft';
            if ($request->action === 'send') {
                $userRole = strtolower(Auth::user()->role->name ?? '');
                if ($userRole === 'kepala toko') {
                    $status = 'Verified';
                } elseif ($userRole === 'kabag') {
                    $status = 'Approved';
                }
            }
            $noMb = $memoPembayaran->no_mb;
            $tanggal = $memoPembayaran->tanggal;

            // Kirim pertama kali dari Draft → generate nomor baru & set tanggal sekarang
            if ($request->action === 'send' && $memoPembayaran->status === 'Draft') {
                $department = $memoPembayaran->department;
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);
                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                $tanggal = now()->toDateString();
            }
            // Kirim ulang dari Rejected → pertahankan nomor, perbarui tanggal ke hari ini
            if ($request->action === 'send' && $memoPembayaran->status === 'Rejected') {
                $tanggal = now()->toDateString();
            }

            // Set flag to prevent double logging in observer
            $memoPembayaran->skip_observer_log = true;

            // Determine supplier/department similar to store() when no PO
            $departmentId = $memoPembayaran->department_id;
            $supplierId = $memoPembayaran->supplier_id;
            $bankSupplierAccountId = $memoPembayaran->bank_supplier_account_id;
            if ($request->purchase_order_id) {
                $po = PurchaseOrder::select('department_id','supplier_id','bank_supplier_account_id')->find($request->purchase_order_id);
                if ($po) {
                    $departmentId = $po->department_id ?: $departmentId;
                    $supplierId = $po->supplier_id ?: $supplierId;
                    // Ambil bank supplier account dari PO terpilih saat update
                    $bankSupplierAccountId = $po->bank_supplier_account_id ?: $bankSupplierAccountId;
                }
            } else {
                if ($request->filled('supplier_id')) {
                    $supplierId = (int) $request->input('supplier_id');
                }
                if (!$supplierId && $request->filled('bank_supplier_account_id')) {
                    $acc = DB::table('bank_supplier_accounts')->select('supplier_id')->where('id', $request->bank_supplier_account_id)->first();
                    if ($acc && $acc->supplier_id) {
                        $supplierId = (int) $acc->supplier_id;
                    }
                }
                // Jika tidak ada PO, gunakan nilai dari request
                if ($request->filled('bank_supplier_account_id')) {
                    $bankSupplierAccountId = (int) $request->bank_supplier_account_id;
                }
                if (!$departmentId && $supplierId) {
                    $sup = Supplier::select('department_id')->find($supplierId);
                    if ($sup && $sup->department_id) {
                        $departmentId = (int) $sup->department_id;
                    }
                }
                if (!$departmentId) {
                    $departmentId = Auth::user()->department->id ?? $departmentId;
                }
            }

            // Compute totals from items (BPB-like)
            $items = collect($request->input('items', []))
                ->filter(fn($it) => isset($it['purchase_order_item_id']))
                ->values();

            // Validate item qty against remaining per PO item (based on Approved BPBs)
            if ($request->purchase_order_id && $items->count() > 0) {
                $poId = (int) $request->purchase_order_id;
                // Lock PO items and compute remaining
                $poItemRows = DB::table('purchase_order_items')
                    ->where('purchase_order_id', $poId)
                    ->lockForUpdate()
                    ->get(['id','qty']);
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

                // When updating, do not add back current memo items because memo items are not persisted; we validate against overall remaining
                foreach ($items as $it) {
                    $poi = (int) $it['purchase_order_item_id'];
                    $poQty = (float) ($poQtyById[$poi] ?? 0);
                    $received = (float) ($receivedByPoi[$poi] ?? 0);
                    $remaining = max(0, $poQty - $received);
                    $qty = (float) ($it['qty'] ?? 0);
                    if ($qty < 0 || $qty - $remaining > 0.000001) {
                        abort(422, "Qty untuk item PO #$poi melebihi sisa yang diperbolehkan");
                    }
                }
            }

            $diskonInput = (float) ($request->input('diskon', 0) ?? 0);
            $usePpn = filter_var($request->input('use_ppn', false), FILTER_VALIDATE_BOOLEAN);
            $ppnRate = (float) ($request->input('ppn_rate', 11) ?? 11);
            $usePph = filter_var($request->input('use_pph', false), FILTER_VALIDATE_BOOLEAN);
            $pphRate = (float) ($request->input('pph_rate', 0) ?? 0);

            $subtotal = 0.0;
            if ($request->purchase_order_id && $items->count() > 0) {
                $poItemPrices = PurchaseOrderItem::where('purchase_order_id', $request->purchase_order_id)
                    ->get()
                    ->keyBy('id');
                foreach ($items as $it) {
                    $row = $poItemPrices->get((int) $it['purchase_order_item_id']);
                    if (!$row) continue;
                    $qty = max(0, (float) ($it['qty'] ?? 0));
                    $harga = (float) ($row->harga ?? 0);
                    $subtotal += ($qty * $harga);
                }
            }
            $dpp = max(0.0, $subtotal - $diskonInput);
            $ppnNominal = $usePpn ? ($dpp * ($ppnRate / 100.0)) : 0.0;
            $pphNominal = $usePph ? ($dpp * ($pphRate / 100.0)) : 0.0;
            $grandTotal = $dpp + $ppnNominal + $pphNominal;

            $effectiveTotal = $request->total ?? 0;
            if ($request->purchase_order_id) {
                $poForAmount = PurchaseOrder::select('id', 'tipe_po', 'termin_id')->find($request->purchase_order_id);
                if ($poForAmount && $poForAmount->tipe_po === 'Lainnya' && $poForAmount->termin_id) {
                    $effectiveTotal = $request->cicilan ?? 0;
                } elseif ($grandTotal > 0) {
                    $effectiveTotal = $grandTotal;
                }
            }
            if (!$request->purchase_order_id && $grandTotal > 0) {
                $effectiveTotal = $grandTotal;
            }

            // Update memo
            $updateData = [
                'no_mb' => $noMb,
                'purchase_order_id' => $request->purchase_order_id,
                'department_id' => $departmentId,
                'supplier_id' => $supplierId,
                'total' => $effectiveTotal,
                'grand_total' => $effectiveTotal,
                'cicilan' => $request->cicilan,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank_supplier_account_id' => $bankSupplierAccountId,
                'no_giro' => $request->no_giro,
                'tanggal_giro' => $request->tanggal_giro,
                'tanggal_cair' => $request->tanggal_cair,
                'keterangan' => $request->keterangan,
                'diskon' => $diskonInput,
                'ppn' => $usePpn,
                'ppn_nominal' => $ppnNominal,
                'pph_nominal' => $pphNominal,
                'tanggal' => $tanggal,
                'status' => $status,
                'updated_by' => Auth::id(),
            ];
            if ($status === 'Approved') {
                $updateData['approved_by'] = Auth::id();
                $updateData['approved_at'] = now();
            }
            $memoPembayaran->update($updateData);

            // Log the update action
            MemoPembayaranLog::create([
                'memo_pembayaran_id' => $memoPembayaran->id,
                'action' => $request->action === 'send' ? 'sent' : 'updated',
                'description' => $request->action === 'send'
                    ? 'Memo Pembayaran dikirim dengan nomor ' . $noMb
                    : 'Memo Pembayaran diperbarui',
                'user_id' => Auth::id(),
                'new_values' => [
                    'no_mb' => $noMb,
                    'status' => $status,
                    'tanggal' => $tanggal,
                ],
            ]);

            // Purchase order is already linked via purchase_order_id field

            DB::commit();

            $message = $request->action === 'send'
                ? 'Memo Pembayaran berhasil dikirim'
                : 'Memo Pembayaran berhasil disimpan sebagai draft';

            return redirect()->route('memo-pembayaran.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Memo Pembayaran: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui Memo Pembayaran'])->with('error', 'Terjadi kesalahan saat memperbarui Memo Pembayaran');
        }
    }


    public function destroy(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeDeletedByUser(Auth::user())) {
            return redirect()->route('memo-pembayaran.index')->with('error', 'Memo Pembayaran tidak dapat dibatalkan');
        }

        try {
            DB::beginTransaction();

            $memoPembayaran->update([
                'status' => 'Canceled',
                'canceled_by' => Auth::id(),
                'canceled_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('memo-pembayaran.index');
        } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error canceling Memo Pembayaran: ' . $e->getMessage());
                    return back()->withErrors(['error' => 'Terjadi kesalahan saat membatalkan Memo Pembayaran']);
        }
    }

    public function send(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:memo_pembayarans,id',
        ]);

        try {
            DB::beginTransaction();

            $memoPembayarans = MemoPembayaran::whereIn('id', $request->ids)
                ->whereIn('status', ['Draft', 'Rejected'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->filter(function ($memo) {
                    return $memo->canBeSentByUser(Auth::user());
                });

            // Track any memos that failed to process
            $failed = [];

            $updatedIds = [];
            foreach ($memoPembayarans as $memoPembayaran) {
                // Pastikan relasi creator di-load dan handle jika null (data lama)
                $memoPembayaran->load('creator.role');
                $creator = $memoPembayaran->creator ?: Auth::user();
                Log::info('Role creator MemoPembayaran:', [
                    'memo_id' => $memoPembayaran->id,
                    'creator_id' => $memoPembayaran->creator?->id,
                    'creator_name' => $memoPembayaran->creator?->name,
                    'role' => $memoPembayaran->creator?->role?->name
                ]);

                // Generate document number
                $department = $memoPembayaran->department;
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);

                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                // Guard uniqueness in case of concurrent bulk operations
                if (!DocumentNumberService::isNumberUnique($noMb)) {
                    $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                }

                // Tentukan status awal berdasarkan role pembuat
                $status = 'In Progress';
                $userRole = strtolower($creator->role->name ?? '');
                if ($userRole === 'kepala toko') {
                    $status = 'Verified';
                } elseif ($userRole === 'kabag') {
                    $status = 'Approved';
                }

                // Set flag to prevent double logging in observer
                $memoPembayaran->skip_observer_log = true;

                $updateData = [
                    'no_mb' => $noMb,
                    'tanggal' => now(),
                    'status' => $status,
                    'updated_by' => Auth::id(),
                ];
                if ($status === 'Approved') {
                    $updateData['approved_by'] = Auth::id();
                    $updateData['approved_at'] = now();
                }
                $memoPembayaran->update($updateData);

                $updatedIds[] = $memoPembayaran->id;

                // Log the send action (and approval if applicable)
                MemoPembayaranLog::create([
                    'memo_pembayaran_id' => $memoPembayaran->id,
                    'action' => 'sent',
                    'description' => 'Memo Pembayaran dikirim dengan nomor ' . $noMb,
                    'user_id' => Auth::id(),
                    'new_values' => [
                        'status' => $status,
                        'no_mb' => $noMb,
                        'tanggal' => now(),
                    ],
                ]);
                if ($status === 'Approved') {
                    MemoPembayaranLog::create([
                        'memo_pembayaran_id' => $memoPembayaran->id,
                        'action' => 'approved',
                        'description' => 'Memo Pembayaran auto-approved oleh Kabag saat kirim',
                        'user_id' => Auth::id(),
                        'new_values' => [
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ],
                    ]);
                }
            }

            DB::commit();

            $updatedCount = count($updatedIds);
            $successMsg = $updatedCount > 0 ? ($updatedCount . ' Memo Pembayaran berhasil dikirim') : null;
            $redirect = back();
            if ($successMsg) {
                $redirect = $redirect->with('success', $successMsg);
            }
            return $redirect->with('failed_memos', $failed)->with('updated_memos', $updatedIds);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sending Memo Pembayaran: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengirim Memo Pembayaran']);
        }
    }

    public function preview(MemoPembayaran $memoPembayaran)
    {
        try {
            Log::info('MemoPembayaran Preview - Starting preview for Memo:', [
                'memo_id' => $memoPembayaran->id,
                'user_id' => Auth::id()
            ]);

            $memoPembayaran->load(['department', 'purchaseOrder', 'purchaseOrders', 'supplier', 'bank', 'creator', 'approver']);

            $tanggal = $memoPembayaran->tanggal
                ? Carbon::parse($memoPembayaran->tanggal)->isoFormat('D MMMM Y')
                : '-';

            Log::info('MemoPembayaran Preview - Data prepared, rendering view');

            return view('memo_pembayaran_preview', [
                'memo' => $memoPembayaran,
                'tanggal' => $tanggal,
                'logoSrc' => asset('images/company-logo.png'),
                'signatureSrc' => asset('images/signature.png'),
                'approvedSrc' => asset('images/approved.png'),
            ]);
        } catch (\Exception $e) {
            Log::error('MemoPembayaran Preview - Failed to render preview', [
                'memo_id' => $memoPembayaran->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to generate preview: ' . $e->getMessage()], 500);
        }
    }

    public function download(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeDownloaded()) {
            return back()->withErrors(['error' => 'Memo Pembayaran tidak dapat diunduh']);
        }
            $memoPembayaran->load([
                'department',
                'supplier',
                'bank',
                'creator',
                'approver',
                // Memo's own bank supplier account + bank
                'bankSupplierAccount.bank',
                // Memo-level termin (if memo has direct termin_id)
                'termin',
                // Single selected PO and its bank supplier account + bank
                'purchaseOrder',
                'purchaseOrder.termin',
                'purchaseOrder.bankSupplierAccount.bank',
                // For safety, also load many-to-many POs if used in the view
                'purchaseOrders',
                'purchaseOrders.termin',
                'purchaseOrders.bankSupplierAccount.bank',
            ]);

            // Build termin data (prefer memo->termin, fallback to PO termin)
            $terminData = null;
            try {
                $termin = $memoPembayaran->termin;
                if (!$termin) {
                    $po = $memoPembayaran->purchaseOrder ?: ($memoPembayaran->purchaseOrders->first() ?? null);
                    $termin = $po?->termin;
                }
                if ($termin) {
                    $jumlahTotal = (int) ($termin->jumlah_termin ?? 0);
                    // Nomor termin = 1 + jumlah memo (kecuali Canceled/Rejected) SEBELUM memo ini untuk PO/Termin yang sama
                    try {
                        $po = $memoPembayaran->purchaseOrder ?: ($memoPembayaran->purchaseOrders->first() ?? null);
                        $poId = $po?->id;
                        $terminId = $termin->id ?? null;

                        $baseQ = \App\Models\MemoPembayaran::query()
                            ->where('id', '!=', $memoPembayaran->id)
                            ->whereNotIn('status', ['Canceled','Rejected']);

                        if ($poId) {
                            $baseQ->where('purchase_order_id', $poId);
                        } elseif ($terminId) {
                            $baseQ->where('termin_id', $terminId);
                        }

                        if (!empty($memoPembayaran->created_at)) {
                            $createdAt = $memoPembayaran->created_at;
                            $currentId = $memoPembayaran->id;
                            $baseQ->where(function($q) use ($createdAt, $currentId) {
                                $q->where('created_at', '<', $createdAt)
                                  ->orWhere(function($qq) use ($createdAt, $currentId) {
                                      $qq->where('created_at', '=', $createdAt)
                                         ->where('id', '<', $currentId);
                                  });
                            });
                        } else {
                            $baseQ->where('id', '<', $memoPembayaran->id);
                        }

                        $priorCount = $baseQ->count();
                        $terminKe = $priorCount + 1;

                        // Hitung jumlah_cicilan kumulatif sebelum memo ini
                        $priorSum = (float) $baseQ->clone()->sum('cicilan');
                        $nominalCicilan = (float) ($memoPembayaran->cicilan ?? 0);

                        // Total tagihan termin (fallback ke PO total jika perlu)
                        $totalTagihan = (float) ($termin->grand_total ?? ($po?->total ?? ($memoPembayaran->grand_total ?? $memoPembayaran->total ?? 0)));

                        $jumlahCicilan = $priorSum + $nominalCicilan;
                        $sisa = max($totalTagihan - $jumlahCicilan, 0);
                    } catch (\Throwable $eIgnored) {
                        $terminKe = 1;
                        $nominalCicilan = (float) ($memoPembayaran->cicilan ?? 0);
                        $jumlahCicilan = $nominalCicilan;
                        $totalTagihan = (float) ($termin->grand_total ?? 0);
                        $sisa = max($totalTagihan - $jumlahCicilan, 0);
                    }
                    if ($jumlahTotal > 0) { $terminKe = min($terminKe, $jumlahTotal); }
                    $terminData = [
                        'termin_no' => $terminKe,
                        'nominal_cicilan' => $nominalCicilan,
                        'jumlah_cicilan' => $jumlahCicilan,
                        'total_cicilan' => $jumlahCicilan, // backward-compat: use cumulative for display
                        'no_referensi' => $termin->no_referensi ?? null,
                        'jumlah_termin' => $jumlahTotal ?: null,
                        'sisa_pembayaran' => $sisa,
                    ];
                }
            } catch (\Throwable $e) {
                // ignore errors deriving termin data for PDF
            }

            $pdf = Pdf::loadView('memo_pembayaran_pdf', [
                'memo' => $memoPembayaran,
                'tanggal' => $memoPembayaran->tanggal ? Carbon::parse($memoPembayaran->tanggal)->isoFormat('D MMMM Y') : '-',
                'terminData' => $terminData,
                'logoSrc' => $this->getBase64Image('images/company-logo.png'),
                'signatureSrc' => $this->getBase64Image('images/signature.png'),
                'approvedSrc' => $this->getBase64Image('images/approved.png'),
            ])
            ->setOptions(config('dompdf.options'))
            ->setPaper([0, 0, 595.28, 935.43], 'portrait');

            // Clean filename to avoid invalid characters like "/" and "\\"
            $cleanNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $memoPembayaran->no_mb ?? 'Draft');
            $filename = 'Memo_Pembayaran_' . $cleanNumber . '.pdf';

            return $pdf->stream($filename);
    }

    // Helper method to convert image to base64 Data URI for PDF embedding
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

    public function log(MemoPembayaran $memoPembayaran)
    {
        // Guard: Staff Toko & Staff Digital Marketing hanya bisa melihat log dokumen miliknya
        // $user = Auth::user();
        // $roleLower = strtolower($user->role->name ?? '');
        // if (in_array($roleLower, ['staff toko','staff digital marketing'], true) && (int)$memoPembayaran->created_by !== (int)$user->id) {
        //     abort(403, 'Unauthorized');
        // }
        $logs = $memoPembayaran->logs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('memo-pembayaran/Log', [
            'memoPembayaran' => $memoPembayaran,
            'logs' => $logs,
        ]);
    }

    public function getPreviewNumber(Request $request)
    {
        // department_id optional; default to current user's department
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $department = $request->filled('department_id')
            ? Department::find($request->department_id)
            : Auth::user()?->department;

        if (!$department) {
            return response()->json(['error' => 'Department tidak valid'], 422);
        }

        $departmentAlias = $department->alias ?? substr($department->name ?? '', 0, 3);

        // Use form preview generator to exclude drafts and include current draft context
        $previewNumber = DocumentNumberService::generateFormPreviewNumber('Memo Pembayaran', null, $department->id, $departmentAlias);

        return response()->json(['preview_number' => $previewNumber]);
    }

    // ==================== APPROVAL METHODS ====================

    /**
     * Verify a Memo Pembayaran
     */
    public function verify(Request $request, MemoPembayaran $memoPembayaran)
    {
        $user = Auth::user();

        // Check if user can verify this memo
        if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'verify')) {
            return response()->json(['error' => 'Unauthorized to verify this memo'], 403);
        }

        try {
            DB::beginTransaction();

            $memoPembayaran->update([
                'status' => 'Verified',
                'verified_by' => $user->id,
                'verified_at' => now(),
                'approval_notes' => $request->input('notes', '')
            ]);

            // Log verification activity
            $this->logApprovalActivity($user, $memoPembayaran, 'verified');

            DB::commit();

            return response()->json([
                'message' => 'Memo Pembayaran verified successfully',
                'memo_pembayaran' => $memoPembayaran->fresh(['verifier'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to verify Memo Pembayaran', [
                'memo_id' => $memoPembayaran->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to verify Memo Pembayaran'], 500);
        }
    }

    /**
     * Validate a Memo Pembayaran
     */
    public function validateMemo(Request $request, MemoPembayaran $memoPembayaran)
    {
        $user = Auth::user();

        // Check if user can validate this memo
        if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'validate')) {
            return response()->json(['error' => 'Unauthorized to validate this memo'], 403);
        }

        try {
            DB::beginTransaction();

            $memoPembayaran->update([
                'status' => 'Validated',
                'validated_by' => $user->id,
                'validated_at' => now(),
                'approval_notes' => $request->input('notes', '')
            ]);

            // Log validation activity
            $this->logApprovalActivity($user, $memoPembayaran, 'validated');

            DB::commit();

            return response()->json([
                'message' => 'Memo Pembayaran validated successfully',
                'memo_pembayaran' => $memoPembayaran->fresh(['validator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to validate Memo Pembayaran', [
                'memo_id' => $memoPembayaran->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to validate Memo Pembayaran'], 500);
        }
    }

    /**
     * Approve a Memo Pembayaran
     */
    public function approve(Request $request, MemoPembayaran $memoPembayaran)
    {
        $user = Auth::user();

        // Check if user can approve this memo
        if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'approve')) {
            return response()->json(['error' => 'Unauthorized to approve this memo'], 403);
        }

        try {
            DB::beginTransaction();

            $memoPembayaran->update([
                'status' => 'Approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $request->input('notes', '')
            ]);

            // Log approval activity
            $this->logApprovalActivity($user, $memoPembayaran, 'approved');

            DB::commit();

            return response()->json([
                'message' => 'Memo Pembayaran approved successfully',
                'memo_pembayaran' => $memoPembayaran->fresh(['approver'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve Memo Pembayaran', [
                'memo_id' => $memoPembayaran->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to approve Memo Pembayaran'], 500);
        }
    }

    /**
     * Reject a Memo Pembayaran
     */
    public function reject(Request $request, MemoPembayaran $memoPembayaran)
    {
        $user = Auth::user();

        // Check if user can reject this memo
        if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'reject')) {
            return response()->json(['error' => 'Unauthorized to reject this memo'], 403);
        }

        try {
            DB::beginTransaction();

            $memoPembayaran->update([
                'status' => 'Rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $request->input('reason', '')
            ]);

            // Log rejection activity
            $this->logApprovalActivity($user, $memoPembayaran, 'rejected');

            DB::commit();

            return response()->json([
                'message' => 'Memo Pembayaran rejected successfully',
                'memo_pembayaran' => $memoPembayaran->fresh(['rejecter'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject Memo Pembayaran', [
                'memo_id' => $memoPembayaran->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to reject Memo Pembayaran'], 500);
        }
    }

    /**
     * Get approval progress for a Memo Pembayaran
     */
    public function approvalProgress(MemoPembayaran $memoPembayaran)
    {
        $progress = $this->approvalWorkflowService->getApprovalProgressForMemoPembayaran($memoPembayaran);

        return response()->json([
            'progress' => $progress,
            'current_status' => $memoPembayaran->status
        ]);
    }

    /**
     * Log approval activity
     */
    private function logApprovalActivity($user, $memoPembayaran, $action)
    {
        $description = $this->getActionDescription($action, $memoPembayaran, $user);

        MemoPembayaranLog::create([
            'memo_pembayaran_id' => $memoPembayaran->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'old_values' => null,
            'new_values' => null,
        ]);
    }

    private function getActionDescription(string $action, $memoPembayaran, $user): string
    {
        $userName = $user->name ?? 'Unknown User';
        $documentNumber = $memoPembayaran->no_memo ?? 'N/A';

        switch ($action) {
            case 'verified':
                return "{$userName} verified Memo Pembayaran {$documentNumber}";
            case 'validated':
                return "{$userName} validated Memo Pembayaran {$documentNumber}";
            case 'approved':
                return "{$userName} approved Memo Pembayaran {$documentNumber}";
            case 'rejected':
                return "{$userName} rejected Memo Pembayaran {$documentNumber}";
            default:
                return "{$userName} performed {$action} on Memo Pembayaran {$documentNumber}";
        }
    }
}
