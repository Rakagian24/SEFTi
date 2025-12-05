<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\PelunasanAp;
use App\Models\Supplier;
use App\Models\Department;
use App\Models\BankKeluar;
use App\Models\BankMutasi;
use App\Models\PaymentVoucher;
use App\Models\PelunasanApItem;
use App\Models\PelunasanApLog;
use App\Scopes\DepartmentScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\DocumentNumberService;

class PelunasanApController extends Controller
{
    protected $documentNumberService;

    public function __construct(DocumentNumberService $documentNumberService)
    {
        $this->documentNumberService = $documentNumberService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRoleName = $user->role->name ?? '';
        $userRole = strtolower($userRoleName);

        // Base query with eager loads
        $with = [
            'department',
            'supplier',
            'creator',
            'bankKeluar',
            'bankMutasi',
            'items' => function ($q) {
                $q->with('paymentVoucher');
            },
        ];

        // DepartmentScope policy
        if ($userRoleName === 'Admin') {
            $query = PelunasanAp::withoutGlobalScope(DepartmentScope::class)
                ->with($with);
        } else {
            $query = PelunasanAp::query()->with($with);
        }

        // Default: show current month data if no date filter provided
        if (!$request->filled('tanggal_start') && !$request->filled('tanggal_end')) {
            $now = Carbon::now();
            $query->whereMonth('tanggal', $now->month)
                  ->whereYear('tanggal', $now->year)
                  ->orWhere(function ($q) use ($now) {
                      $q->where('status', 'Draft')
                        ->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year);
                  });
        } else {
            // Date range filter
            if ($request->filled('tanggal_start') || $request->filled('tanggal_end')) {
                $start = $request->filled('tanggal_start') ? $request->tanggal_start : null;
                $end = $request->filled('tanggal_end') ? $request->tanggal_end : null;

                $query->where(function ($q) use ($start, $end) {
                    // Non-Draft: filter by 'tanggal'
                    $q->where(function ($nonDraft) use ($start, $end) {
                        $nonDraft->where('status', '!=', 'Draft');
                        if ($start && $end) {
                            $nonDraft->whereBetween('tanggal', [$start, $end]);
                        } elseif ($start) {
                            $nonDraft->whereDate('tanggal', '>=', $start);
                        } elseif ($end) {
                            $nonDraft->whereDate('tanggal', '<=', $end);
                        }
                    })
                    // Draft: filter by created_at
                    ->orWhere(function ($draft) use ($start, $end) {
                        $draft->where('status', 'Draft')
                              ->where(function ($dt) use ($start, $end) {
                                  if ($start && $end) {
                                      $dt->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
                                  } elseif ($start) {
                                      $dt->whereDate('created_at', '>=', $start);
                                  } elseif ($end) {
                                      $dt->whereDate('created_at', '<=', $end);
                                  }
                              });
                    });
                });
            }
        }

        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $pelunasanAps = $query->paginate($perPage);

        // Get filter options
        $suppliers = Supplier::orderBy('nama_supplier')->get();
        $departments = Department::orderBy('name')->get();
        $statuses = ['Draft', 'In Progress', 'Approved', 'Rejected', 'Canceled'];

        return Inertia::render('pelunasan-ap/Index', [
            'pelunasanAps' => $pelunasanAps,
            'filters' => [
                'tanggal_start' => $request->tanggal_start,
                'tanggal_end' => $request->tanggal_end,
                'supplier_id' => $request->supplier_id,
                'department_id' => $request->department_id,
                'status' => $request->status,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'suppliers' => $suppliers,
            'departments' => $departments,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('nama')->get();
        $departments = Department::orderBy('nama')->get();
        $bankKeluars = BankKeluar::where('status', 'Approved')
            ->orderBy('created_at', 'desc')
            ->get();
        $bankMutasis = BankMutasi::orderBy('created_at', 'desc')->get();

        return Inertia::render('pelunasan-ap/Create', [
            'suppliers' => $suppliers,
            'departments' => $departments,
            'bankKeluars' => $bankKeluars,
            'bankMutasis' => $bankMutasis,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_pelunasan' => 'required|in:Bank Keluar,Mutasi,Retur',
            'bank_keluar_id' => 'nullable|exists:bank_keluars,id',
            'bank_mutasi_id' => 'nullable|exists:bank_mutasis,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'nilai_dokumen_referensi' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.payment_voucher_id' => 'required|exists:payment_vouchers,id',
            'items.*.nilai_pelunasan' => 'required|numeric|min:0',
            'pembulatan_minus' => 'nullable|numeric|min:0',
            'pembulatan_plus' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $pelunasanAp = new PelunasanAp();
            $pelunasanAp->tanggal = $validated['tanggal'];
            $pelunasanAp->tipe_pelunasan = $validated['tipe_pelunasan'];
            $pelunasanAp->bank_keluar_id = $validated['bank_keluar_id'] ?? null;
            $pelunasanAp->bank_mutasi_id = $validated['bank_mutasi_id'] ?? null;
            $pelunasanAp->supplier_id = $validated['supplier_id'];
            $pelunasanAp->nilai_dokumen_referensi = $validated['nilai_dokumen_referensi'];
            $pelunasanAp->keterangan = $validated['keterangan'] ?? null;
            $pelunasanAp->status = 'Draft';
            $pelunasanAp->department_id = $user->department_id;
            $pelunasanAp->creator_id = $user->id;
            $pelunasanAp->save();

            // Add items
            foreach ($validated['items'] as $item) {
                $pv = PaymentVoucher::find($item['payment_voucher_id']);

                PelunasanApItem::create([
                    'pelunasan_ap_id' => $pelunasanAp->id,
                    'payment_voucher_id' => $item['payment_voucher_id'],
                    'nilai_pv' => $pv->nominal ?? 0,
                    'outstanding' => $pv->nominal ?? 0,
                    'nilai_pelunasan' => $item['nilai_pelunasan'],
                    'sisa' => ($pv->nominal ?? 0) - $item['nilai_pelunasan'],
                ]);
            }

            // Log activity
            PelunasanApLog::create([
                'pelunasan_ap_id' => $pelunasanAp->id,
                'user_id' => $user->id,
                'action' => 'created',
                'description' => 'Dokumen pelunasan dibuat sebagai draft',
            ]);

            DB::commit();

            return redirect()->route('pelunasan-ap.show', $pelunasanAp->id)
                ->with('success', 'Dokumen pelunasan berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating pelunasan: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membuat dokumen pelunasan']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PelunasanAp $pelunasanAp)
    {
        $pelunasanAp->load([
            'department',
            'supplier',
            'creator',
            'bankKeluar',
            'bankMutasi',
            'items' => function ($q) {
                $q->with('paymentVoucher');
            },
        ]);

        return Inertia::render('pelunasan-ap/Detail', [
            'pelunasanAp' => $pelunasanAp,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PelunasanAp $pelunasanAp)
    {
        if ($pelunasanAp->status !== 'Draft') {
            return back()->withErrors(['error' => 'Hanya dokumen draft yang dapat diedit']);
        }

        $pelunasanAp->load([
            'items' => function ($q) {
                $q->with('paymentVoucher');
            },
        ]);

        $suppliers = Supplier::orderBy('nama')->get();
        $departments = Department::orderBy('nama')->get();
        $bankKeluars = BankKeluar::where('status', 'Approved')
            ->orderBy('created_at', 'desc')
            ->get();
        $bankMutasis = BankMutasi::orderBy('created_at', 'desc')->get();

        return Inertia::render('pelunasan-ap/Edit', [
            'pelunasanAp' => $pelunasanAp,
            'suppliers' => $suppliers,
            'departments' => $departments,
            'bankKeluars' => $bankKeluars,
            'bankMutasis' => $bankMutasis,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PelunasanAp $pelunasanAp)
    {
        if ($pelunasanAp->status !== 'Draft') {
            return back()->withErrors(['error' => 'Hanya dokumen draft yang dapat diedit']);
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_pelunasan' => 'required|in:Bank Keluar,Mutasi,Retur',
            'bank_keluar_id' => 'nullable|exists:bank_keluars,id',
            'bank_mutasi_id' => 'nullable|exists:bank_mutasis,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'nilai_dokumen_referensi' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.payment_voucher_id' => 'required|exists:payment_vouchers,id',
            'items.*.nilai_pelunasan' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $pelunasanAp->update([
                'tanggal' => $validated['tanggal'],
                'tipe_pelunasan' => $validated['tipe_pelunasan'],
                'bank_keluar_id' => $validated['bank_keluar_id'] ?? null,
                'bank_mutasi_id' => $validated['bank_mutasi_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'nilai_dokumen_referensi' => $validated['nilai_dokumen_referensi'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            // Delete old items
            $pelunasanAp->items()->delete();

            // Add new items
            foreach ($validated['items'] as $item) {
                $pv = PaymentVoucher::find($item['payment_voucher_id']);

                PelunasanApItem::create([
                    'pelunasan_ap_id' => $pelunasanAp->id,
                    'payment_voucher_id' => $item['payment_voucher_id'],
                    'nilai_pv' => $pv->nominal ?? 0,
                    'outstanding' => $pv->nominal ?? 0,
                    'nilai_pelunasan' => $item['nilai_pelunasan'],
                    'sisa' => ($pv->nominal ?? 0) - $item['nilai_pelunasan'],
                ]);
            }

            // Log activity
            PelunasanApLog::create([
                'pelunasan_ap_id' => $pelunasanAp->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Dokumen pelunasan diperbarui',
            ]);

            DB::commit();

            return redirect()->route('pelunasan-ap.show', $pelunasanAp->id)
                ->with('success', 'Dokumen pelunasan berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating pelunasan: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal memperbarui dokumen pelunasan']);
        }
    }

    /**
     * Send pelunasanAp for approval
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:pelunasan_aps,id',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $now = Carbon::now();

            foreach ($validated['ids'] as $id) {
                $pelunasanAp = PelunasanAp::find($id);

                if ($pelunasanAp->status !== 'Draft') {
                    continue;
                }

                // Generate document number
                $noPl = $this->documentNumberService->generateNumber(
                    'PL',
                    'AP',
                    $pelunasanAp->department->alias ?? 'DEPT',
                    $now->month,
                    $now->year
                );

                $pelunasanAp->update([
                    'no_pl' => $noPl,
                    'tanggal' => $now->toDateString(),
                    'status' => 'In Progress',
                ]);

                // Log activity
                PelunasanApLog::create([
                    'pelunasan_ap_id' => $pelunasanAp->id,
                    'user_id' => $user->id,
                    'action' => 'sent',
                    'description' => 'Dokumen pelunasan dikirim untuk persetujuan',
                ]);
            }

            DB::commit();

            return back()->with('success', 'Dokumen pelunasan berhasil dikirim');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sending pelunasanAp: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal mengirim dokumen pelunasan']);
        }
    }

    /**
     * Cancel pelunasanAp
     */
    public function cancel(Request $request, PelunasanAp $pelunasanAp)
    {
        if (!in_array($pelunasanAp->status, ['Draft', 'In Progress'])) {
            return back()->withErrors(['error' => 'Hanya dokumen draft atau in progress yang dapat dibatalkan']);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $pelunasanAp->update([
                'status' => 'Canceled',
                'canceled_by' => $user->id,
                'canceled_at' => now(),
                'cancellation_reason' => $request->reason ?? null,
            ]);

            // Log activity
            PelunasanApLog::create([
                'pelunasan_ap_id' => $pelunasanAp->id,
                'user_id' => $user->id,
                'action' => 'canceled',
                'description' => 'Dokumen pelunasan dibatalkan',
            ]);

            DB::commit();

            return back()->with('success', 'Dokumen pelunasan berhasil dibatalkan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error canceling pelunasanAp: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membatalkan dokumen pelunasan']);
        }
    }

    /**
     * Get logs for pelunasanAp
     */
    public function logs(PelunasanAp $pelunasanAp)
    {
        $logs = $pelunasanAp->logs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('pelunasan-ap/Log', [
            'pelunasanAp' => $pelunasanAp,
            'logs' => $logs,
        ]);
    }

    /**
     * Get bank keluars for selection modal
     */
    public function getBankKeluars(Request $request)
    {
        $query = BankKeluar::where('status', 'Approved');

        if ($request->filled('tanggal_start') || $request->filled('tanggal_end')) {
            $start = $request->tanggal_start;
            $end = $request->tanggal_end;

            if ($start && $end) {
                $query->whereBetween('tanggal', [$start, $end]);
            } elseif ($start) {
                $query->whereDate('tanggal', '>=', $start);
            } elseif ($end) {
                $query->whereDate('tanggal', '<=', $end);
            }
        }

        $bankKeluars = $query->with('department')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($bankKeluars);
    }

    /**
     * Get payment vouchers for selection modal
     */
    public function getPaymentVouchers(Request $request)
    {
        $query = PaymentVoucher::where('status', 'Approved');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('tanggal_start') || $request->filled('tanggal_end')) {
            $start = $request->tanggal_start;
            $end = $request->tanggal_end;

            if ($start && $end) {
                $query->whereBetween('tanggal', [$start, $end]);
            } elseif ($start) {
                $query->whereDate('tanggal', '>=', $start);
            } elseif ($end) {
                $query->whereDate('tanggal', '<=', $end);
            }
        }

        $paymentVouchers = $query->with('supplier')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($paymentVouchers);
    }
}
