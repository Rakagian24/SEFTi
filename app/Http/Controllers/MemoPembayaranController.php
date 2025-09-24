<?php

namespace App\Http\Controllers;

use App\Models\MemoPembayaran;
use App\Models\Department;
use App\Models\Perihal;
use App\Models\PurchaseOrder;
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

        // Use DepartmentScope (do NOT bypass) so 'All' access works and multi-department users are respected
        $query = MemoPembayaran::query()->with([
            'department',
            'purchaseOrder.perihal',
            'purchaseOrder.supplier',
            'supplier',
            'bank',
            'pph',
            'creator'
        ]);

        // Batasi visibilitas Draft: hanya untuk role Staff (Toko, Digital Marketing, Akunting & Finance)
        $userRoleName = $user->role->name ?? '';
        $staffRolesAllowedDraft = ['Staff Toko', 'Staff Digital Marketing', 'Staff Akunting & Finance', 'Admin'];
        if (!in_array($userRoleName, $staffRolesAllowedDraft, true)) {
            $query->where('status', '!=', 'Draft');
        }

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
                                    $subQ->where('nama_perihal', 'like', '%'.$search.'%');
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

        // Default filter: current month data (only if no date filters are applied)
        if (!$request->filled('tanggal_start') && !$request->filled('tanggal_end')) {
            $query->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year);
        }

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
            ->whereHas('perihal', function ($query) {
                $query->where('nama', 'Permintaan Pembayaran Jasa');
            })
            ->with(['perihal', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->get();

        $banks = Bank::where('status', 'active')
            ->orderBy('nama_bank')
            ->get();

        return Inertia::render('memo-pembayaran/Create', [
            'purchaseOrders' => $purchaseOrders,
            'banks' => $banks,
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
            ->with(['perihal', 'supplier', 'department'])
            ->whereHas('perihal', function ($q) {
                $q->where('nama', 'Permintaan Pembayaran Jasa');
            });

        // Exclude PO that already used in Memo Pembayaran (kecuali Memo status Canceled)
        $usedPoIds = DB::table('memo_pembayarans')
            ->where('status', '!=', 'Canceled')
            ->whereNotNull('purchase_order_id')
            ->pluck('purchase_order_id')
            ->toArray();

        if (!empty($usedPoIds)) {
            $query->whereNotIn('id', $usedPoIds);
        }

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

        $purchaseOrders = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(function($po) {
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
                    'metode_pembayaran' => $po->metode_pembayaran,
                    'bank_id' => $po->bank_id,
                    'nama_rekening' => $po->nama_rekening,
                    'no_rekening' => $po->no_rekening,
                    'no_giro' => $po->no_giro,
                    'no_kartu_kredit' => $po->no_kartu_kredit,
                    'keterangan' => $po->keterangan,
                    'status' => $po->status,
                    'department' => $po->department ? [
                        'id' => $po->department->id,
                        'nama' => $po->department->name,
                    ] : null,
                    'supplier' => $po->supplier ? [
                        'id' => $po->supplier->id,
                        'nama_supplier' => $po->supplier->nama_supplier,
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
            ->whereNotNull('no_giro')
            ->where('no_giro', '!=', '')
            ->where(function ($q) {
                $q->where('metode_pembayaran', 'Cek/Giro')
                ->orWhere('metode_pembayaran', 'Cek / Giro')
                ->orWhere('metode_pembayaran', 'Giro')
                ->orWhere('metode_pembayaran', 'like', '%Giro%');
            })
            ->whereHas('perihal', function ($q) {
                $q->where('nama', 'Permintaan Pembayaran Jasa');
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
            'keterangan' => 'nullable|string|max:65535',
            'action' => 'required|in:draft,send',
        ];

        if ($request->input('action') === 'send') {
            $baseRules = array_merge($baseRules, [
                'total' => 'required|numeric|min:0',
                'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
                // Transfer-only
                'bank_id' => 'required_if:metode_pembayaran,Transfer|nullable|exists:banks,id',
                'nama_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
                'no_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
                // Cek/Giro-only
                'no_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|string',
                'tanggal_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
                'tanggal_cair' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
                // Kredit-only
                'no_kartu_kredit' => 'required_if:metode_pembayaran,Kredit|nullable|string',
            ]);
        } else {
            // Draft boleh kosong
            $baseRules = array_merge($baseRules, [
                'total' => 'nullable|numeric|min:0',
                'metode_pembayaran' => 'nullable|in:Transfer,Cek/Giro,Kredit',
                'bank_id' => 'nullable|exists:banks,id',
                'nama_rekening' => 'nullable|string',
                'no_rekening' => 'nullable|string',
                'no_giro' => 'nullable|string',
                'tanggal_giro' => 'nullable|date',
                'tanggal_cair' => 'nullable|date',
                'no_kartu_kredit' => 'nullable|string',
            ]);
        }

        $request->validate($baseRules);

        // Custom validation for purchase order
        if ($request->input('action') === 'send' && $request->purchase_order_id) {
            // Check if purchase order is already used in other memo pembayaran (exclude Canceled)
            $usedPO = DB::table('memo_pembayarans')
                ->where('purchase_order_id', $request->purchase_order_id)
                ->where('status', '!=', 'Canceled')
                ->first();

            if ($usedPO) {
                $poNumber = PurchaseOrder::find($request->purchase_order_id)->no_po ?? 'Unknown';
                return back()->withErrors([
                    'purchase_order_id' => 'Purchase Order ' . $poNumber . ' sudah digunakan dalam Memo Pembayaran lain'
                ])->withInput();
            }

            // Check if purchase order matches the selected metode pembayaran
            $po = PurchaseOrder::find($request->purchase_order_id);
            if ($po && $po->metode_pembayaran !== $request->metode_pembayaran) {
                return back()->withErrors([
                    'purchase_order_id' => 'Purchase Order ' . $po->no_po . ' tidak sesuai dengan metode pembayaran yang dipilih'
                ])->withInput();
            }

            // Check if purchase order has Approved status
            if ($po && $po->status !== 'Approved') {
                return back()->withErrors([
                    'purchase_order_id' => 'Purchase Order ' . $po->no_po . ' belum disetujui'
                ])->withInput();
            }

            // Check if purchase order matches the selected supplier/giro/kredit criteria
            if ($request->metode_pembayaran === 'Transfer' && $request->bank_id) {
                // For Transfer, we need to check if PO has the same supplier
                // Get supplier from bank account
                $bankAccount = DB::table('bank_accounts')
                    ->where('bank_id', $request->bank_id)
                    ->where('no_rekening', $request->no_rekening)
                    ->first();

                if ($bankAccount && $po && $po->supplier_id != $bankAccount->supplier_id) {
                    return back()->withErrors([
                        'purchase_order_id' => 'Purchase Order ' . $po->no_po . ' tidak sesuai dengan Supplier yang dipilih'
                    ])->withInput();
                }
            } elseif ($request->metode_pembayaran === 'Cek/Giro' && $request->no_giro) {
                if ($po && $po->no_giro !== $request->no_giro) {
                    return back()->withErrors([
                        'purchase_order_id' => 'Purchase Order ' . $po->no_po . ' tidak sesuai dengan No. Cek/Giro yang dipilih'
                    ])->withInput();
                }
            } elseif ($request->metode_pembayaran === 'Kredit' && $request->no_kartu_kredit) {
                if ($po && $po->no_kartu_kredit !== $request->no_kartu_kredit) {
                    return back()->withErrors([
                        'purchase_order_id' => 'Purchase Order ' . $po->no_po . ' tidak sesuai dengan Kartu Kredit yang dipilih'
                    ])->withInput();
                }
            }

            // Check if total purchase order matches the input total
            if ($po && $po->total != $request->total) {
                return back()->withErrors([
                    'purchase_order_id' => 'Total Purchase Order (' . number_format($po->total, 0, ',', '.') . ') tidak sama dengan nominal yang diinput (' . number_format($request->total, 0, ',', '.') . ')'
                ])->withInput();
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
            if ($request->purchase_order_id) {
                $po = PurchaseOrder::select('department_id', 'supplier_id')->find($request->purchase_order_id);
                if ($po) {
                    if ($po->department_id) {
                        $departmentId = $po->department_id;
                    }
                    if ($po->supplier_id) {
                        $supplierId = $po->supplier_id;
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

            $memoPembayaran = MemoPembayaran::create([
                'no_mb' => $noMb,
                'department_id' => $departmentId,
                'purchase_order_id' => $request->purchase_order_id,
                'supplier_id' => $supplierId,
                'total' => $request->total,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank_id' => $request->bank_id,
                'nama_rekening' => $request->nama_rekening,
                'no_rekening' => $request->no_rekening,
                'no_giro' => $request->no_giro,
                'no_kartu_kredit' => $request->no_kartu_kredit,
                'tanggal_giro' => $request->tanggal_giro,
                'tanggal_cair' => $request->tanggal_cair,
                'keterangan' => $request->keterangan,
                'diskon' => 0,
                'ppn' => false,
                'ppn_nominal' => 0,
                'pph_nominal' => 0,
                'grand_total' => $request->total,
                'tanggal' => $tanggal,
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
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
            $memoPembayaran->load(['department', 'purchaseOrder.perihal', 'supplier', 'bank', 'pph', 'creator', 'updater', 'canceler', 'approver', 'rejecter']);

            return Inertia::render('memo-pembayaran/Detail', [
                'memoPembayaran' => $memoPembayaran,
            ]);
        }

    public function edit(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeEdited()) {
            return redirect()->route('memo-pembayaran.index')
                ->with('error', 'Memo Pembayaran tidak dapat diedit');
        }

        $purchaseOrders = PurchaseOrder::where('status', 'Approved')
            ->whereHas('perihal', function ($query) {
                $query->where('nama', 'Permintaan Pembayaran Jasa');
            })
            ->with(['perihal', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->get();

        $banks = Bank::where('status', 'active')
            ->orderBy('nama_bank')
            ->get();

        $memoPembayaran->load(['department', 'purchaseOrder', 'bank']);

        return Inertia::render('memo-pembayaran/Edit', [
            'memoPembayaran' => $memoPembayaran,
            'purchaseOrders' => $purchaseOrders,
            'banks' => $banks,
        ]);
    }


    public function update(Request $request, MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeEdited()) {
            return redirect()->route('memo-pembayaran.index')->with('error', 'Memo Pembayaran tidak dapat diedit');
        }

        // Base rules
        $rules = [
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
            'keterangan' => 'nullable|string|max:65535',
            'action' => 'required|in:draft,send',
        ];

        // Kondisional total
        if ($request->input('action') === 'send') {
            $rules['total'] = 'required|numeric|min:0';
        } else {
            $rules['total'] = 'nullable|numeric|min:0';
        }

        // Kondisional field lain
        if ($request->input('action') === 'send') {
            $rules = array_merge($rules, [
                'bank_id' => 'required_if:metode_pembayaran,Transfer|nullable|exists:banks,id',
                'nama_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
                'no_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
                'no_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|string',
                'tanggal_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
                'tanggal_cair' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
                'no_kartu_kredit' => 'required_if:metode_pembayaran,Kredit|nullable|string',
            ]);
        } else {
            $rules = array_merge($rules, [
                'bank_id' => 'nullable|exists:banks,id',
                'nama_rekening' => 'nullable|string',
                'no_rekening' => 'nullable|string',
                'no_giro' => 'nullable|string',
                'tanggal_giro' => 'nullable|date',
                'tanggal_cair' => 'nullable|date',
                'no_kartu_kredit' => 'nullable|string',
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            // Tentukan status
            $status = $request->action === 'send' ? 'In Progress' : 'Draft';
            $noMb = $memoPembayaran->no_mb;
            $tanggal = $memoPembayaran->tanggal;

            // Kalau kirim pertama kali dari Draft → generate nomor baru
            if ($request->action === 'send' && $memoPembayaran->status === 'Draft') {
                $department = $memoPembayaran->department;
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);
                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                $tanggal = now()->toDateString();
            }

            // Update memo
            $memoPembayaran->update([
                'no_mb' => $noMb,
                'purchase_order_id' => $request->purchase_order_id,
                'total' => $request->total ?? 0,
                'grand_total' => $request->total ?? 0,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank_id' => $request->bank_id,
                'nama_rekening' => $request->nama_rekening,
                'no_rekening' => $request->no_rekening,
                'no_giro' => $request->no_giro,
                'no_kartu_kredit' => $request->no_kartu_kredit,
                'tanggal_giro' => $request->tanggal_giro,
                'tanggal_cair' => $request->tanggal_cair,
                'keterangan' => $request->keterangan,
                'diskon' => 0,
                'ppn' => false,
                'ppn_nominal' => 0,
                // Pastikan PPh dibersihkan saat edit (misal user meng-uncheck PPh)
                'pph_id' => null,
                'pph_nominal' => 0,
                'tanggal' => $tanggal,
                'status' => $status,
                'updated_by' => Auth::id(),
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
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui Memo Pembayaran']);
        }
    }


    public function destroy(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeDeleted()) {
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

            return redirect()->route('memo-pembayaran.index')->with('success', 'Memo Pembayaran berhasil dibatalkan');
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
                ->where('status', 'Draft')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($memoPembayarans->isEmpty()) {
                return back()->withErrors(['error' => 'Tidak ada Memo Pembayaran yang dapat dikirim']);
}
            // Validate mandatory fields similar to store/update when action = send
            $failed = [];
            $validMemos = [];
            foreach ($memoPembayarans as $memo) {
                $missing = [];
                if (empty($memo->total) || $memo->total <= 0) {
                    $missing[] = 'Total';
                }
                if (!in_array($memo->metode_pembayaran, ['Transfer', 'Cek/Giro', 'Kredit'], true)) {
                    $missing[] = 'Metode Pembayaran';
                } else {
                    if ($memo->metode_pembayaran === 'Transfer') {
                        if (empty($memo->bank_id)) $missing[] = 'Bank';
                        if (empty($memo->nama_rekening)) $missing[] = 'Nama Rekening';
                        if (empty($memo->no_rekening)) $missing[] = 'No. Rekening';
                    } elseif ($memo->metode_pembayaran === 'Cek/Giro') {
                        if (empty($memo->no_giro)) $missing[] = 'No. Giro';
                        if (empty($memo->tanggal_giro)) $missing[] = 'Tanggal Giro';
                        if (empty($memo->tanggal_cair)) $missing[] = 'Tanggal Cair';
                    } elseif ($memo->metode_pembayaran === 'Kredit') {
                        if (empty($memo->no_kartu_kredit)) $missing[] = 'No. Kartu Kredit';
                    }
                }

                if (!empty($missing)) {
                    $failed[] = [
                        'id' => $memo->id,
                        'no_mb' => $memo->no_mb,
                        'errors' => array_map(function ($m) { return $m; }, $missing),
                    ];
                } else {
                    $validMemos[] = $memo;
                }
            }

            $updatedIds = [];
            foreach ($validMemos as $memoPembayaran) {
                // Generate document number
                $department = $memoPembayaran->department;
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);

                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                // Guard uniqueness in case of concurrent bulk operations
                if (!DocumentNumberService::isNumberUnique($noMb)) {
                    $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                }

                $memoPembayaran->update([
                    'no_mb' => $noMb,
                    'tanggal' => now(),
                    'status' => 'In Progress',
                    'updated_by' => Auth::id(),
                ]);

                $updatedIds[] = $memoPembayaran->id;

                // Log the send action
                MemoPembayaranLog::create([
                    'memo_pembayaran_id' => $memoPembayaran->id,
                    'action' => 'sent',
                    'description' => 'Memo Pembayaran dikirim dengan nomor ' . $noMb,
                    'user_id' => Auth::id(),
                    'new_values' => ['status' => 'In Progress', 'no_mb' => $noMb, 'tanggal' => now()],
                ]);
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

    public function download(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeDownloaded()) {
            return back()->withErrors(['error' => 'Memo Pembayaran tidak dapat diunduh']);
        }
            $memoPembayaran->load(['department', 'purchaseOrder', 'supplier', 'bank', 'pph', 'creator', 'approver']);

            $pdf = Pdf::loadView('memo_pembayaran_pdf', [
                'memo' => $memoPembayaran,
                'tanggal' => $memoPembayaran->tanggal ? Carbon::parse($memoPembayaran->tanggal)->isoFormat('D MMMM Y') : '-',
                'logoSrc' => $this->getBase64Image('images/company-logo.png'),
                'signatureSrc' => $this->getBase64Image('images/signature.png'),
                'approvedSrc' => $this->getBase64Image('images/approved.png'),
            ])
            ->setOptions(config('dompdf.options'))
            ->setPaper([0, 0, 595.28, 935.43], 'portrait');

            // Clean filename to avoid invalid characters like "/" and "\\"
            $cleanNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $memoPembayaran->no_mb ?? 'Draft');
            $filename = 'Memo_Pembayaran_' . $cleanNumber . '.pdf';

            return $pdf->download($filename);
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
