<?php

namespace App\Http\Controllers;

use App\Models\BankKeluar;
use App\Models\BankKeluarLog;
use App\Models\Department;
use App\Models\Supplier;
use App\Models\BisnisPartner;
use App\Models\Bank;
use App\Models\BankSupplierAccount;
use App\Models\CreditCard;
use App\Models\PaymentVoucher;
use App\Models\Realisasi;
use App\Models\RealisasiLog;
use App\Models\Perihal;
use App\Services\DocumentNumberService;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BankKeluarController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Set memory limit for this request
            ini_set('memory_limit', '1G');

            // Cache ID department "All" secara statis agar bisa digunakan di seluruh method
            static $allDepartmentId = null;
            if ($allDepartmentId === null) {
                $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
            }

            // Debug: Log incoming request parameters
            Log::info('BankKeluar Index Request', [
                'all_params' => $request->all(),
                'filters' => [
                    'no_bk' => $request->input('no_bk'),
                    'department_id' => $request->input('department_id'),
                    'supplier_id' => $request->input('supplier_id'),
                    'start' => $request->input('start'),
                    'end' => $request->input('end'),
                    'search' => $request->input('search'),
                    'sortBy' => $request->input('sortBy'),
                    'sortDirection' => $request->input('sortDirection'),
                    'per_page' => $request->input('per_page'),
                ],
                'is_reset' => $request->input('start') === '' && $request->input('end') === '' && $request->input('no_bk') === '' && $request->input('no_pv') === '' && $request->input('department_id') === '' && $request->input('supplier_id') === '' && $request->input('search') === ''
            ]);

            // Filter dinamis dengan scope yang dioptimasi
            $query = BankKeluar::active();

            // Filter lain
            if ($request->filled('no_bk')) {
                $query->where('no_bk', 'like', '%' . $request->no_bk . '%');
            }
            if ($request->filled('department_id')) {
                $departmentId = $request->department_id;

                $query->where(function ($subQuery) use ($departmentId, $allDepartmentId) {
                    // Prefix with table name to avoid ambiguity when joins are applied
                    $subQuery->where('bank_keluars.department_id', $departmentId);
                    if ($allDepartmentId) {
                        $subQuery->orWhere('bank_keluars.department_id', $allDepartmentId);
                    }
                });
            }
            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            // Search bebas - optimize with better indexing
            if ($request->filled('search')) {
                // Use optimized search method for better performance
                $query->searchOptimized($request->input('search'));
            }

            // Filter rentang tanggal
            if ($request->filled('start') && $request->filled('end')) {
                $query->byDateRange($request->start, $request->end);
            } elseif ($request->filled('start')) {
                $query->where('tanggal', '>=', $request->start);
            } elseif ($request->filled('end')) {
                $query->where('tanggal', '<=', $request->end);
            }

            // Sorting
            $sortBy = $request->input('sortBy');
            $sortDirection = $request->input('sortDirection', 'asc');
            $allowedSorts = ['no_bk', 'tanggal', 'note', 'nominal', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $query->orderByDesc('created_at');
            }

            // Rows per page (support entriesPerPage dari frontend)
            $perPage = $request->input('per_page', 10);
            $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

            // Eager load relationships
            $query->with([
                'department',
                'supplier',
                'bisnisPartner',
                'bank',
                'creator',
                'updater',
                // For displaying Bisnis Partner coming from Anggaran Payment Voucher
                'paymentVoucher.poAnggaran.bisnisPartner',
            ]);

            // Paginate results
            $bankKeluars = $query->paginate($perPage);

            // Get filter options
            $departments = DepartmentService::getOptionsForFilter();

            $suppliers = Supplier::select('id', 'nama_supplier', 'department_id')
                ->orderBy('nama_supplier')
                ->get()
                ->map(function ($s) use ($allDepartmentId) {
                    return [
                        'id' => $s->id,
                        'nama_supplier' => $s->nama_supplier,
                        'department_id' => $s->department_id,
                        'is_all' => $allDepartmentId && (int) $s->department_id === (int) $allDepartmentId,
                    ];
                })->values();

            return Inertia::render('bank-keluar/Index', [
                'bankKeluars' => $bankKeluars,
                'filters' => [
                    'no_bk' => $request->input('no_bk', ''),
                    'department_id' => $request->input('department_id', ''),
                    'supplier_id' => $request->input('supplier_id', ''),
                    'start' => $request->input('start', ''),
                    'end' => $request->input('end', ''),
                    'search' => $request->input('search', ''),
                ],
                'departments' => $departments,
                'suppliers' => $suppliers,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in BankKeluarController@index', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memuat data Bank Keluar.']);
        }
    }

    public function create()
    {
        static $allDepartmentId = null;
        if ($allDepartmentId === null) {
            $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
        }

        $departments = Department::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')
            ->select(['id','nama_supplier','department_id'])
            ->get()
            ->map(function($s) use ($allDepartmentId) {
                return [
                    'id' => $s->id,
                    'nama_supplier' => $s->nama_supplier,
                    'department_id' => $s->department_id,
                    'is_all' => $allDepartmentId && (int)$s->department_id === (int)$allDepartmentId,
                ];
            })->values();
        $bisnisPartners = BisnisPartner::with(['departments:id,name'])->get();
        $banks = Bank::where('status', 'active')->get();

        $bankSupplierAccounts = BankSupplierAccount::with(['supplier', 'bank'])
            ->get(['id', 'supplier_id', 'bank_id', 'nama_rekening', 'no_rekening']);

        $creditCards = CreditCard::active()->with('bank')
            ->get(['id', 'bank_id', 'no_kartu_kredit', 'nama_pemilik', 'department_id'])
            ->map(function($c) use ($allDepartmentId) {
                return [
                    'id' => $c->id,
                    'bank_id' => $c->bank_id,
                    'no_kartu_kredit' => $c->no_kartu_kredit,
                    'nama_pemilik' => $c->nama_pemilik,
                    'department_id' => $c->department_id,
                    'is_all' => $allDepartmentId && (int)$c->department_id === (int)$allDepartmentId,
                ];
            })->values();

        // Realisasi yang dapat digunakan sebagai sumber Bank Keluar (mode Realisasi)
        // Hanya Realisasi dengan status Approved dan total_realisasi melebihi total_anggaran
        $realisasis = Realisasi::query()
            ->where('status', 'Approved')
            ->whereColumn('total_realisasi', '>', 'total_anggaran')
            ->with(['department', 'poAnggaran', 'bisnisPartner'])
            ->orderByDesc('id')
            ->get(['id', 'no_realisasi', 'department_id', 'po_anggaran_id', 'bisnis_partner_id', 'total_anggaran', 'total_realisasi', 'status']);

        // Payment Vouchers yang bisa dipilih untuk Bank Keluar
        // Hanya PV yang sudah Approved dan masih memiliki remaining_nominal (atau belum di-track, remaining_nominal null)
        $paymentVouchers = PaymentVoucher::query()
            ->where('status', 'Approved')
            ->where(function ($query) {
                $query->whereNull('remaining_nominal')
                      ->orWhere('remaining_nominal', '>', 0);
            })
            ->with([
                'department',
                'perihal',
                'supplier',
                'bankSupplierAccount.bank',
                'creditCard.bank',
                'poAnggaran.bisnisPartner.bank',
                'poAnggaran.bank',
                'purchaseOrder.bankSupplierAccount.bank',
                'memoPembayaran.bankSupplierAccount.bank',
                'bpbAllocations.bpb',
                'memoAllocations.memo',
            ])
            ->orderByDesc('id')
            ->get();

        $perihals = Perihal::query()->select(['id', 'nama'])->orderBy('nama')->get();

        return Inertia::render('bank-keluar/Create', [
            'departments' => $departments,
            'paymentVouchers' => $paymentVouchers,
            'perihals' => $perihals,
            'suppliers' => $suppliers,
            'bisnisPartners' => $bisnisPartners,
            'banks' => $banks,
            'bankSupplierAccounts' => $bankSupplierAccounts,
            'creditCards' => $creditCards,
            'realisasis' => $realisasis,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_bk' => 'required|in:Reguler,Anggaran,Lainnya,Pajak,Manual,DP',
            'source_type' => 'nullable|in:PV,Non PV,Realisasi',
            'department_id' => 'required|exists:departments,id',
            'nominal' => 'required|numeric|min:0.01',
            'metode_bayar' => 'required|string',
            'payment_voucher_id' => 'nullable|exists:payment_vouchers,id',
            'realisasi_id' => 'nullable|exists:realisasis,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'nama_pemilik_rekening' => 'nullable|string|max:255',
            'no_rekening' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $sourceType = $validated['source_type'] ?? 'PV';

            // Validasi kombinasi bisnis rules untuk supplier / bisnis partner / realisasi
            $extraErrors = [];

            if ($sourceType === 'PV') {
                if (in_array($validated['tipe_bk'], ['Reguler', 'Lainnya', 'Pajak', 'Manual', 'DP'], true) && empty($validated['supplier_id'])) {
                    $extraErrors['supplier_id'] = 'Supplier wajib diisi untuk tipe BK ini.';
                }
                if ($validated['tipe_bk'] === 'Anggaran' && empty($validated['bisnis_partner_id'])) {
                    $extraErrors['bisnis_partner_id'] = 'Bisnis Partner wajib diisi untuk tipe BK Anggaran.';
                }
            } elseif ($sourceType === 'Non PV') {
                if (empty($validated['bisnis_partner_id'])) {
                    $extraErrors['bisnis_partner_id'] = 'Bisnis Partner wajib diisi untuk Bank Keluar Non PV.';
                }
            } elseif ($sourceType === 'Realisasi') {
                if (empty($validated['bisnis_partner_id'])) {
                    $extraErrors['bisnis_partner_id'] = 'Bisnis Partner wajib diisi untuk Bank Keluar Realisasi.';
                }
                if (empty($validated['realisasi_id'])) {
                    $extraErrors['realisasi_id'] = 'Realisasi wajib dipilih untuk Bank Keluar Realisasi.';
                }
            }

            if (!empty($extraErrors)) {
                DB::rollBack();
                return back()->withErrors($extraErrors)->withInput();
            }

            // Validasi tambahan & update sisa nominal PV (jika ada)
            $pv = null;
            if ($sourceType === 'PV' && !empty($validated['payment_voucher_id'])) {
                /** @var \App\Models\PaymentVoucher|null $pv */
                $pv = PaymentVoucher::lockForUpdate()->find($validated['payment_voucher_id']);
                if ($pv) {
                    $maxNominal = (float) ($pv->remaining_nominal ?? $pv->nominal ?? 0);
                    if ($maxNominal > 0 && (float) $validated['nominal'] > $maxNominal) {
                        DB::rollBack();
                        return back()
                            ->withErrors(['nominal' => 'Nominal Bank Keluar tidak boleh melebihi nominal Payment Voucher.'])
                            ->withInput();
                    }
                }
            }

            $department = Department::findOrFail($validated['department_id']);

            // Generate document number
            $no_bk = DocumentNumberService::generateNumber('Bank Keluar', $validated['tipe_bk'] ?? null, $department->id, $department->alias);

            $bankKeluar = BankKeluar::create([
                'no_bk' => $no_bk,
                'tanggal' => $validated['tanggal'],
                'source_type' => $sourceType,
                'tipe_bk' => $validated['tipe_bk'] ?? null,
                'payment_voucher_id' => $validated['payment_voucher_id'] ?? null,
                'realisasi_id' => $validated['realisasi_id'] ?? null,
                'department_id' => $validated['department_id'],
                'nominal' => $validated['nominal'],
                'metode_bayar' => $validated['metode_bayar'],
                'supplier_id' => $validated['tipe_bk'] === 'Anggaran' ? null : $validated['supplier_id'],
                'bisnis_partner_id' => $validated['tipe_bk'] === 'Anggaran' ? $validated['bisnis_partner_id'] : null,
                'bank_id' => $validated['bank_id'],
                'bank_supplier_account_id' => $validated['bank_supplier_account_id'] ?? null,
                'credit_card_id' => $validated['credit_card_id'] ?? null,
                'nama_pemilik_rekening' => $validated['nama_pemilik_rekening'],
                'no_rekening' => $validated['no_rekening'],
                'note' => $validated['note'],
                'status' => 'aktif',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Jika sumber BK adalah Realisasi, tutup Realisasi terkait dan catat log
            if ($sourceType === 'Realisasi' && !empty($validated['realisasi_id'])) {
                /** @var \App\Models\Realisasi|null $realisasi */
                $realisasi = Realisasi::lockForUpdate()->find($validated['realisasi_id']);
                if ($realisasi && $realisasi->status !== 'Closed') {
                    $realisasi->status = 'Closed';
                    $realisasi->save();

                    RealisasiLog::create([
                        'realisasi_id' => $realisasi->id,
                        'action' => 'closed',
                        'meta' => ['reason' => 'Bank Keluar created (auto-close)'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);
                }
            }

            // Create log entry
            BankKeluarLog::create([
                'bank_keluar_id' => $bankKeluar->id,
                'user_id' => Auth::id(),
                'action' => 'create',
                'description' => 'Membuat Bank Keluar baru',
                'ip_address' => $request->ip(),
            ]);

            // Kurangi remaining_nominal PV jika ada
            if ($pv) {
                $pv->remaining_nominal = max(0, (float) ($pv->remaining_nominal ?? $pv->nominal ?? 0) - (float) $validated['nominal']);
                $pv->save();
            }

            DB::commit();

            if ($request->boolean('stay')) {
                return redirect()->back()->with('success', 'Bank Keluar berhasil dibuat.');
            }

            return redirect()->route('bank-keluar.index')->with('success', 'Bank Keluar berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in BankKeluarController@store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan Bank Keluar.']);
        }
    }

    public function show(BankKeluar $bankKeluar)
    {
        $bankKeluar->load([
            'department',
            'supplier',
            'bisnisPartner',
            'bisnisPartner.bank',
            'bank',
            'creditCard.bank',
            'bankSupplierAccount.bank',
            'creator',
            'updater',
            // Payment Voucher & related data for richer detail view
            'paymentVoucher',
            'paymentVoucher.department',
            'paymentVoucher.perihal',
            'paymentVoucher.supplier',
            'paymentVoucher.bankSupplierAccount.bank',
            'paymentVoucher.creditCard.bank',
            'paymentVoucher.poAnggaran.bisnisPartner.bank',
            'paymentVoucher.poAnggaran.bank',
            'paymentVoucher.purchaseOrder.bankSupplierAccount.bank',
            'paymentVoucher.memoPembayaran.bankSupplierAccount.bank',
            'paymentVoucher.bpbAllocations.bpb',
            'paymentVoucher.memoAllocations.memo',
        ]);

        return Inertia::render('bank-keluar/Detail', [
            'bankKeluar' => $bankKeluar,
        ]);
    }

    public function edit(BankKeluar $bankKeluar)
    {
        $bankKeluar->load([
            'department',
            'supplier',
            'bisnisPartner',
            'bank',
        ]);

        static $allDepartmentId = null;
        if ($allDepartmentId === null) {
            $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
        }

        $departments = Department::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')
            ->select(['id', 'nama_supplier', 'department_id'])
            ->get()
            ->map(function ($s) use ($allDepartmentId) {
                return [
                    'id' => $s->id,
                    'nama_supplier' => $s->nama_supplier,
                    'department_id' => $s->department_id,
                    'is_all' => $allDepartmentId && (int) $s->department_id === (int) $allDepartmentId,
                ];
            })->values();
        $bisnisPartners = BisnisPartner::with(['departments:id,name'])->get();
        $banks = Bank::where('status', 'active')->get();

        $bankSupplierAccounts = BankSupplierAccount::with(['supplier', 'bank'])
            ->get(['id', 'supplier_id', 'bank_id', 'nama_rekening', 'no_rekening']);

        $creditCards = CreditCard::active()->with('bank')
            ->get(['id', 'bank_id', 'no_kartu_kredit', 'nama_pemilik', 'department_id']);

        // Realisasi yang dapat digunakan sebagai sumber Bank Keluar (mode Realisasi)
        // Hanya Realisasi dengan status Approved dan total_realisasi melebihi total_anggaran
        $realisasis = Realisasi::query()
            ->where('status', 'Approved')
            ->whereColumn('total_realisasi', '>', 'total_anggaran')
            ->with(['department', 'poAnggaran', 'bisnisPartner'])
            ->orderByDesc('id')
            ->get(['id', 'no_realisasi', 'department_id', 'po_anggaran_id', 'bisnis_partner_id', 'total_anggaran', 'total_realisasi', 'status']);

        // Payment Vouchers untuk halaman edit:
        // - Hanya PV Approved yang masih memiliki remaining_nominal (atau remaining_nominal null)
        // - Selalu termasuk PV yang saat ini terpilih pada dokumen BK yang sedang diedit
        $paymentVouchers = PaymentVoucher::query()
            ->where('status', 'Approved')
            ->where(function ($query) use ($bankKeluar) {
                // PV dengan remaining_nominal masih boleh dipilih
                $query->where(function ($inner) {
                    $inner->whereNull('remaining_nominal')
                          ->orWhere('remaining_nominal', '>', 0);
                });

                // Selalu sertakan PV yang sudah terpilih pada BK ini, meskipun remaining_nominal sudah 0
                if ($bankKeluar->payment_voucher_id) {
                    $query->orWhere('id', $bankKeluar->payment_voucher_id);
                }
            })
            ->with([
                'department',
                'perihal',
                'supplier',
                'bankSupplierAccount.bank',
                'creditCard.bank',
                'poAnggaran.bisnisPartner.bank',
                'poAnggaran.bank',
                'memoPembayaran',
                'bpbAllocations.bpb',
                'memoAllocations.memo',
            ])
            ->orderByDesc('id')
            ->get();

        $perihals = Perihal::query()->select(['id', 'nama'])->orderBy('nama')->get();

        return Inertia::render('bank-keluar/Edit', [
            'bankKeluar' => $bankKeluar,
            'departments' => $departments,
            'suppliers' => $suppliers,
            'bisnisPartners' => $bisnisPartners,
            'banks' => $banks,
            'bankSupplierAccounts' => $bankSupplierAccounts,
            'creditCards' => $creditCards,
            'realisasis' => $realisasis,
            'paymentVouchers' => $paymentVouchers,
        ]);
    }

    public function update(Request $request, BankKeluar $bankKeluar)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_bk' => 'required|in:Reguler,Anggaran,Lainnya,Pajak,Manual,DP',
            'source_type' => 'nullable|in:PV,Non PV,Realisasi',
            'department_id' => 'required|exists:departments,id',
            'perihal_id' => 'nullable|exists:perihals,id',
            'nominal' => 'required|numeric|min:0.01',
            'metode_bayar' => 'required|string',
            'payment_voucher_id' => 'nullable|exists:payment_vouchers,id',
            'realisasi_id' => 'nullable|exists:realisasis,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'nama_pemilik_rekening' => 'nullable|string|max:255',
            'no_rekening' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $sourceType = $validated['source_type'] ?? $bankKeluar->source_type ?? 'PV';

            // Validasi kombinasi bisnis rules untuk supplier / bisnis partner / realisasi
            $extraErrors = [];

            if ($sourceType === 'PV') {
                if (in_array($validated['tipe_bk'], ['Reguler', 'Lainnya', 'Pajak', 'Manual', 'DP'], true) && empty($validated['supplier_id'])) {
                    $extraErrors['supplier_id'] = 'Supplier wajib diisi untuk tipe BK ini.';
                }
                if ($validated['tipe_bk'] === 'Anggaran' && empty($validated['bisnis_partner_id'])) {
                    $extraErrors['bisnis_partner_id'] = 'Bisnis Partner wajib diisi untuk tipe BK Anggaran.';
                }
            } elseif ($sourceType === 'Non PV') {
                if (empty($validated['bisnis_partner_id'])) {
                    $extraErrors['bisnis_partner_id'] = 'Bisnis Partner wajib diisi untuk Bank Keluar Non PV.';
                }
            } elseif ($sourceType === 'Realisasi') {
                if (empty($validated['bisnis_partner_id'])) {
                    $extraErrors['bisnis_partner_id'] = 'Bisnis Partner wajib diisi untuk Bank Keluar Realisasi.';
                }
                if (empty($validated['realisasi_id']) && empty($bankKeluar->realisasi_id)) {
                    $extraErrors['realisasi_id'] = 'Realisasi wajib dipilih untuk Bank Keluar Realisasi.';
                }
            }

            if (!empty($extraErrors)) {
                DB::rollBack();
                return back()->withErrors($extraErrors)->withInput();
            }

            // Simpan nilai lama sebelum update, untuk perhitungan ulang remaining_nominal
            $oldNominal = (float) $bankKeluar->nominal;
            $oldPvId = $bankKeluar->payment_voucher_id;

            // Validasi tambahan & update sisa nominal PV (jika ada)
            $pv = null;
            if ($sourceType === 'PV' && !empty($validated['payment_voucher_id'])) {
                /** @var \App\Models\PaymentVoucher|null $pv */
                $pv = PaymentVoucher::lockForUpdate()->find($validated['payment_voucher_id']);
                if ($pv) {
                    $baseRemaining = (float) ($pv->remaining_nominal ?? $pv->nominal ?? 0);

                    // Jika masih menggunakan PV yang sama, izinkan nominal baru sampai remaining + nominal lama BK ini
                    if ($oldPvId && (int) $oldPvId === (int) $validated['payment_voucher_id']) {
                        $maxNominal = $baseRemaining + $oldNominal;
                    } else {
                        // Jika pindah ke PV lain, gunakan remaining dari PV baru apa adanya
                        $maxNominal = $baseRemaining;
                    }

                    if ($maxNominal > 0 && (float) $validated['nominal'] > $maxNominal) {
                        DB::rollBack();
                        return back()
                            ->withErrors(['nominal' => 'Nominal Bank Keluar tidak boleh melebihi nominal Payment Voucher.'])
                            ->withInput();
                    }
                }
            }

            // Update Bank Keluar

            $bankKeluar->update([
                'tanggal' => $validated['tanggal'],
                'tipe_bk' => $validated['tipe_bk'],
                'source_type' => $sourceType,
                'payment_voucher_id' => $validated['payment_voucher_id'] ?? null,
                'realisasi_id' => $validated['realisasi_id'] ?? $bankKeluar->realisasi_id,
                'department_id' => $validated['department_id'],
                'nominal' => $validated['nominal'],
                'metode_bayar' => $validated['metode_bayar'],
                'supplier_id' => $validated['tipe_bk'] === 'Anggaran' ? null : $validated['supplier_id'],
                'bisnis_partner_id' => $validated['tipe_bk'] === 'Anggaran' ? $validated['bisnis_partner_id'] : null,
                'bank_id' => $validated['bank_id'],
                'bank_supplier_account_id' => $validated['bank_supplier_account_id'] ?? null,
                'credit_card_id' => $validated['credit_card_id'] ?? null,
                'nama_pemilik_rekening' => $validated['nama_pemilik_rekening'],
                'no_rekening' => $validated['no_rekening'],
                'note' => $validated['note'],
                'updated_by' => Auth::id(),
            ]);

            // Sinkronkan remaining_nominal pada Payment Voucher (jika ada perubahan)
            $newNominal = (float) $bankKeluar->nominal;
            $newPvId = $bankKeluar->payment_voucher_id;

            // Hanya sinkronkan remaining_nominal jika mode PV
            if ($sourceType === 'PV' && $oldPvId && $newPvId && (int) $oldPvId === (int) $newPvId) {
                /** @var \App\Models\PaymentVoucher|null $pvForUpdate */
                $pvForUpdate = PaymentVoucher::lockForUpdate()->find($newPvId);
                if ($pvForUpdate) {
                    $currentRemaining = (float) ($pvForUpdate->remaining_nominal ?? $pvForUpdate->nominal ?? 0);
                    $delta = $newNominal - $oldNominal; // positif berarti BK membesar, remaining berkurang
                    $pvForUpdate->remaining_nominal = max(0, $currentRemaining - $delta);
                    $pvForUpdate->save();
                }
            } elseif ($sourceType === 'PV') {
                // Jika PV berubah atau baru di-set, pulihkan ke PV lama lalu kurangi dari PV baru
                if ($oldPvId) {
                    /** @var \App\Models\PaymentVoucher|null $oldPv */
                    $oldPv = PaymentVoucher::lockForUpdate()->find($oldPvId);
                    if ($oldPv) {
                        $currentRemaining = (float) ($oldPv->remaining_nominal ?? $oldPv->nominal ?? 0);
                        $restoreAmount = $oldNominal;
                        $oldPv->remaining_nominal = min(
                            (float) ($oldPv->nominal ?? $currentRemaining + $restoreAmount),
                            $currentRemaining + $restoreAmount
                        );
                        $oldPv->save();
                    }
                }

                if ($newPvId) {
                    /** @var \App\Models\PaymentVoucher|null $newPv */
                    $newPv = PaymentVoucher::lockForUpdate()->find($newPvId);
                    if ($newPv) {
                        $base = (float) ($newPv->remaining_nominal ?? $newPv->nominal ?? 0);
                        $newPv->remaining_nominal = max(0, $base - $newNominal);
                        $newPv->save();
                    }
                }
            }

            // Create log entry
            BankKeluarLog::create([
                'bank_keluar_id' => $bankKeluar->id,
                'user_id' => Auth::id(),
                'action' => 'update',
                'description' => 'Mengubah data Bank Keluar',
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('bank-keluar.index')->with('success', 'Bank Keluar berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in BankKeluarController@update', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui Bank Keluar.']);
        }
    }

    public function destroy(Request $request, BankKeluar $bankKeluar)
    {
        try {
            DB::beginTransaction();

            // Pulihkan remaining_nominal Payment Voucher (jika ada)
            if ($bankKeluar->payment_voucher_id) {
                /** @var \App\Models\PaymentVoucher|null $pv */
                $pv = PaymentVoucher::lockForUpdate()->find($bankKeluar->payment_voucher_id);

                if ($pv) {
                    $currentRemaining = (float) ($pv->remaining_nominal ?? $pv->nominal ?? 0);
                    $restoreAmount = (float) $bankKeluar->nominal;

                    // Tambahkan kembali nominal BK ke remaining_nominal, maksimal sampai nominal PV awal
                    $pv->remaining_nominal = min(
                        (float) ($pv->nominal ?? $currentRemaining + $restoreAmount),
                        $currentRemaining + $restoreAmount
                    );

                    $pv->save();
                }
            }

            // Update status to 'batal'
            $bankKeluar->update([
                'status' => 'batal',
                'updated_by' => Auth::id(),
            ]);

            // Create log entry
            BankKeluarLog::create([
                'bank_keluar_id' => $bankKeluar->id,
                'user_id' => Auth::id(),
                'action' => 'cancel',
                'description' => 'Membatalkan Bank Keluar',
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('bank-keluar.index')->with('success', 'Bank Keluar berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in BankKeluarController@destroy', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat membatalkan Bank Keluar.']);
        }
    }

    public function log(BankKeluar $bankKeluar)
    {
        $logs = BankKeluarLog::with('user')
            ->where('bank_keluar_id', $bankKeluar->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('bank-keluar/Log', [
            'bankKeluar' => $bankKeluar->load('department', 'supplier'),
            'logs' => $logs,
        ]);
    }

    public function getNextNumber(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'tipe_bk' => 'nullable|string'
        ]);

        $department = Department::findOrFail($request->input('department_id'));
        $no = DocumentNumberService::generatePreviewNumber('Bank Keluar', $request->input('tipe_bk'), $department->id, $department->alias);
        return response()->json(['no_bk' => $no]);
    }

    public function exportExcel(Request $request)
    {
        try {
            // Create a new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $sheet->setCellValue('A1', 'No. BK');
            $sheet->setCellValue('B1', 'No. PV');
            $sheet->setCellValue('C1', 'Tanggal');
            $sheet->setCellValue('D1', 'Departemen');
            $sheet->setCellValue('E1', 'Perihal (PV)');
            $sheet->setCellValue('F1', 'Nominal');
            $sheet->setCellValue('G1', 'Metode Bayar');
            $sheet->setCellValue('H1', 'Supplier');
            $sheet->setCellValue('I1', 'Bank');
            $sheet->setCellValue('J1', 'Nama Pemilik Rekening');
            $sheet->setCellValue('K1', 'No. Rekening');
            $sheet->setCellValue('L1', 'Note');

            // Apply filters
            $query = BankKeluar::with(['department', 'paymentVoucher.perihal', 'supplier', 'bank']);

            if ($request->filled('no_bk')) {
                $query->where('no_bk', 'like', '%' . $request->no_bk . '%');
            }
            if ($request->filled('no_pv')) {
                $query->whereHas('paymentVoucher', function($q) use ($request) {
                    $q->where('no_pv', 'like', '%' . $request->no_pv . '%');
                });
            }
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }
            if ($request->filled('start') && $request->filled('end')) {
                $query->byDateRange($request->start, $request->end);
            } elseif ($request->filled('start')) {
                $query->where('tanggal', '>=', $request->start);
            } elseif ($request->filled('end')) {
                $query->where('tanggal', '<=', $request->end);
            }

            $bankKeluars = $query->get();

            // Fill data
            $row = 2;
            foreach ($bankKeluars as $bankKeluar) {
                $sheet->setCellValue('A' . $row, $bankKeluar->no_bk);
                $sheet->setCellValue('B' . $row, $bankKeluar->paymentVoucher->no_pv ?? '-');
                $sheet->setCellValue('C' . $row, $bankKeluar->tanggal->format('d/m/Y'));
                $sheet->setCellValue('D' . $row, $bankKeluar->department->name ?? '-');
                $sheet->setCellValue('E' . $row, optional($bankKeluar->paymentVoucher->perihal)->name ?? '-');
                $sheet->setCellValue('F' . $row, $bankKeluar->nominal);
                $sheet->setCellValue('G' . $row, $bankKeluar->metode_bayar);
                $sheet->setCellValue('H' . $row, $bankKeluar->supplier->nama ?? '-');
                $sheet->setCellValue('I' . $row, $bankKeluar->bank->nama ?? '-');
                $sheet->setCellValue('J' . $row, $bankKeluar->nama_pemilik_rekening ?? '-');
                $sheet->setCellValue('K' . $row, $bankKeluar->no_rekening ?? '-');
                $sheet->setCellValue('L' . $row, $bankKeluar->note ?? '-');
                $row++;
            }

            // Format columns
            $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            // Auto size columns
            foreach (range('A', 'L') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'Bank_Keluar_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Create response
            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            // Set headers
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;
        } catch (\Exception $e) {
            Log::error('Error in BankKeluarController@exportExcel', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengekspor data.']);
        }
    }
}
