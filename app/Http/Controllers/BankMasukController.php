<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\BankAccount;
use App\Models\Department;
use App\Models\Role;
use App\Services\DepartmentService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\BankMasukLog;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BankMasukController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Set memory limit for this request
            ini_set('memory_limit', '1G');

            // Debug: Log incoming request parameters
            Log::info('BankMasuk Index Request', [
                'all_params' => $request->all(),
                'filters' => [
                    'no_bm' => $request->input('no_bm'),
                    'no_pv' => $request->input('no_pv'),
                    'department_id' => $request->input('department_id'),
                    'bank_account_id' => $request->input('bank_account_id'),
                    'terima_dari' => $request->input('terima_dari'),
                    'start' => $request->input('start'),
                    'end' => $request->input('end'),
                    'search' => $request->input('search'),
                    'sortBy' => $request->input('sortBy'),
                    'sortDirection' => $request->input('sortDirection'),
                    'per_page' => $request->input('per_page'),
                ],
                'is_reset' => $request->input('start') === '' && $request->input('end') === '' && $request->input('no_bm') === '' && $request->input('no_pv') === '' && $request->input('department_id') === '' && $request->input('bank_account_id') === '' && $request->input('terima_dari') === '' && $request->input('search') === ''
            ]);

            // Filter dinamis dengan scope yang dioptimasi
            $query = BankMasuk::active();

            // Filter lain
            if ($request->filled('no_bm')) {
                $query->where('no_bm', 'like', '%' . $request->no_bm . '%');
}
            if ($request->filled('no_pv')) {
                $query->where('purchase_order_id', $request->no_pv);
}
            if ($request->filled('department_id')) {
                $query->whereHas('bankAccount', function($q) use ($request) {
                    $q->where('department_id', $request->department_id);
});
}
            if ($request->filled('bank_account_id')) {
                $query->where('bank_account_id', $request->bank_account_id);
}
            if ($request->filled('terima_dari')) {
                $query->byTerimaDari($request->terima_dari);
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
            $allowedSorts = ['no_bm', 'purchase_order_id', 'tanggal', 'match_date', 'note', 'nilai', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
} else {
                $query->orderByDesc('created_at');
}

            // Rows per page (support entriesPerPage dari frontend)
            $perPage = $request->input('per_page', $request->input('entriesPerPage', 10));

            // Check if this is a reset request (all filters are empty)
            $isResetRequest = $request->input('start') === '' &&
                             $request->input('end') === '' &&
                             $request->input('no_bm') === '' &&
                             $request->input('no_pv') === '' &&
                             $request->input('department_id') === '' &&
                             $request->input('bank_account_id') === '' &&
                             $request->input('terima_dari') === '' &&
                             $request->input('search') === '';

            // Optimize eager loading - only load what's needed
            $query->with([
                'bankAccount' => function($q) {
                    $q->select('id', 'no_rekening', 'bank_id', 'department_id');
},
                'bankAccount.bank' => function($q) {
                    $q->select('id', 'nama_bank', 'singkatan', 'currency');
},
                'bankAccount.department' => function($q) {
                    $q->select('id', 'name', 'alias');
},
                'arPartner' => function($q) {
                    $q->select('id', 'nama_ap');
}
            ]);

            // Use withQueryString only if not resetting, to avoid empty parameters in URL
            if ($isResetRequest) {
                $bankMasuks = $query->paginate($perPage);
                Log::info('BankMasuk: Reset request detected, not using withQueryString');
} else {
                $bankMasuks = $query->paginate($perPage)->withQueryString();
                Log::info('BankMasuk: Normal request, using withQueryString');
}

            // Debug: Log query results
            Log::info('BankMasuk Query Results', [
                'total_count' => $bankMasuks->total(),
                'current_page' => $bankMasuks->currentPage(),
                'per_page' => $bankMasuks->perPage(),
                'last_page' => $bankMasuks->lastPage(),
                'query_sql' => $query->toSql(),
                'query_bindings' => $query->getBindings(),
            ]);

            // Cache bank accounts data for better performance
            $bankAccounts = cache()->remember('bank_accounts_active', 3600, function() {
                $accounts = BankAccount::with(['bank', 'department'])->where('status', 'active')->orderBy('no_rekening')->get();

                // Debug: Log each account's data
                foreach ($accounts as $acc) {
                    Log::info("Bank Account Debug - ID: {$acc->id
}, Dept ID: {$acc->department_id
}, Bank: " . ($acc->bank ? $acc->bank->singkatan : 'N/A') . ", Dept: " . ($acc->department ? $acc->department->name : 'N/A'));
}

                return $accounts;
});

            // Get department options based on user permissions
            $departments = DepartmentService::getOptionsForFilter();

            // Calculate summary data based on current filters
            // We'll use a fresh DB query builder to avoid DepartmentScope conflicts

            // Get summary data with currency grouping
            try {
                // Use raw SQL to completely avoid DepartmentScope conflicts
                $baseQuery = "
                    SELECT
                        COUNT(*) as total_count,
                        SUM(CASE WHEN banks.currency = 'IDR' THEN bank_masuks.nilai ELSE 0 END) as total_idr,
                        SUM(CASE WHEN banks.currency = 'USD' THEN bank_masuks.nilai ELSE 0 END) as total_usd,
                        SUM(CASE WHEN bank_masuks.match_date IS NOT NULL THEN 1 ELSE 0 END) as total_matched
                    FROM bank_masuks
                    INNER JOIN bank_accounts ON bank_masuks.bank_account_id = bank_accounts.id
                    INNER JOIN banks ON bank_accounts.bank_id = banks.id
                    WHERE bank_masuks.status = 'aktif'
                ";

                $whereConditions = [];
                $bindings = [];

                // Apply the same filters for summary calculation
                if ($request->filled('no_bm')) {
                    $whereConditions[] = "bank_masuks.no_bm LIKE ?";
                    $bindings[] = '%' . $request->no_bm . '%';
}
                if ($request->filled('no_pv')) {
                    $whereConditions[] = "bank_masuks.purchase_order_id = ?";
                    $bindings[] = $request->no_pv;
}
                if ($request->filled('department_id')) {
                    $whereConditions[] = "bank_accounts.department_id = ?";
                    $bindings[] = $request->department_id;
}
                if ($request->filled('bank_account_id')) {
                    $whereConditions[] = "bank_masuks.bank_account_id = ?";
                    $bindings[] = $request->bank_account_id;
}
                if ($request->filled('terima_dari')) {
                    $whereConditions[] = "bank_masuks.terima_dari = ?";
                    $bindings[] = $request->terima_dari;
}
                if ($request->filled('search')) {
                    $searchTerm = $request->input('search');
                    $whereConditions[] = "(
                        bank_masuks.no_bm LIKE ? OR
                        bank_masuks.purchase_order_id LIKE ? OR
                        bank_masuks.tanggal LIKE ? OR
                        bank_masuks.note LIKE ? OR
                        bank_masuks.nilai LIKE ?
                    )";
                    $bindings[] = "%$searchTerm%";
                    $bindings[] = "%$searchTerm%";
                    $bindings[] = "%$searchTerm%";
                    $bindings[] = "%$searchTerm%";
                    $bindings[] = "%$searchTerm%";
}
                if ($request->filled('start') && $request->filled('end')) {
                    $whereConditions[] = "bank_masuks.tanggal BETWEEN ? AND ?";
                    $bindings[] = $request->start;
                    $bindings[] = $request->end;
} elseif ($request->filled('start')) {
                    $whereConditions[] = "bank_masuks.tanggal >= ?";
                    $bindings[] = $request->start;
} elseif ($request->filled('end')) {
                    $whereConditions[] = "bank_masuks.tanggal <= ?";
                    $bindings[] = $request->end;
}

                // Apply DepartmentScope manually if needed
                if (!Auth::user()->departments->contains('name', 'All')) {
                    $departmentIds = Auth::user()->departments->pluck('id')->toArray();
                    if (!empty($departmentIds)) {
                        $activeDepartment = request()->get('activeDepartment');
                        if ($activeDepartment && in_array($activeDepartment, $departmentIds)) {
                            $whereConditions[] = "bank_masuks.department_id = ?";
                            $bindings[] = $activeDepartment;
} else {
                            $placeholders = str_repeat('?,', count($departmentIds) - 1) . '?';
                            $whereConditions[] = "bank_masuks.department_id IN ($placeholders)";
                            $bindings = array_merge($bindings, $departmentIds);
}
}
}

                // Build the final query
                if (!empty($whereConditions)) {
                    $baseQuery .= " AND " . implode(" AND ", $whereConditions);
}

                // Debug: Log the final SQL query and bindings
                Log::info('Final Summary SQL Query: ' . $baseQuery);
                Log::info('Summary Query Bindings: ' . json_encode($bindings));

                $summaryData = DB::select($baseQuery, $bindings);
                $summaryData = $summaryData[0] ?? null;

                $summary = [
                    'total_count' => (int) ($summaryData->total_count ?? 0),
                    'total_idr' => (float) ($summaryData->total_idr ?? 0),
                    'total_usd' => (float) ($summaryData->total_usd ?? 0),
                    'total_matched' => (int) ($summaryData->total_matched ?? 0),
                ];

                // Debug: Log the raw summary data
                Log::info('Raw Summary Data: ' . json_encode($summaryData));
                Log::info('Processed Summary: ' . json_encode($summary));
} catch (\Exception $e) {
                Log::error('Summary calculation error: ' . $e->getMessage());
                $summary = [
                    'total_count' => 0,
                    'total_idr' => 0,
                    'total_usd' => 0,
                    'total_matched' => 0,
                ];
}

            // Debug logging
            Log::info('Bank Masuk Index - Data being sent to frontend', [
                'bankAccounts_count' => $bankAccounts->count(),
                'departments_count' => count($departments),
                'departments_data' => $departments,
                'summary_data' => $summary,
                'bankAccounts_sample' => $bankAccounts->take(3)->map(function($acc) {
                    return [
                        'id' => $acc->id,
                        'department_id' => $acc->department_id,
                        'bank_singkatan' => $acc->bank ? $acc->bank->singkatan : 'N/A',
                        'department_name' => $acc->department ? $acc->department->name : 'N/A'
                    ];
}),
                'bankAccounts_full_sample' => $bankAccounts->take(2)->map(function($acc) {
                    return [
                        'id' => $acc->id,
                        'department_id' => $acc->department_id,
                        'no_rekening' => $acc->no_rekening,
                        'bank_id' => $acc->bank_id,
                        'status' => $acc->status,
                        'bank' => $acc->bank ? [
                            'id' => $acc->bank->id,
                            'nama_bank' => $acc->bank->nama_bank,
                            'singkatan' => $acc->bank->singkatan,
                            'currency' => $acc->bank->currency
                        ] : null,
                        'department' => $acc->department ? [
                            'id' => $acc->department->id,
                            'name' => $acc->department->name,
                            'status' => $acc->department->status
                        ] : null
                    ];
})
            ]);

            return Inertia::render('bank-masuk/Index', [
                'bankMasuks' => $bankMasuks,
                'bankAccounts' => $bankAccounts,
                'departments' => $departments,
                'filters' => $request->all(),
                'summary' => $summary,
            ]);
} catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Bank Masuk Index Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return a user-friendly error response
            return Inertia::render('bank-masuk/Index', [
                'bankMasuks' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                'bankAccounts' => [],
                'departments' => [],
                'filters' => $request->all(),
                'summary' => [
                    'total_count' => 0,
                    'total_idr' => 0,
                    'total_usd' => 0,
                    'total_matched' => 0,
                ],
                'error' => 'Terjadi kesalahan saat memuat data. Silakan coba lagi.'
            ]);
}
}

    public function create()
    {
        // Redirect to index page with form modal
        return redirect()->route('bank-masuk.index');
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'match_date' => 'nullable|date',
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'terima_dari' => 'required|in:Customer,Karyawan,Penjualan Toko,Lainnya',
            'nilai' => 'required|numeric|min:0',
            'selisih_penambahan' => 'nullable|numeric|min:0',
            'selisih_pengurangan' => 'nullable|numeric|min:0',
            'nominal_akhir' => 'nullable|numeric|min:0',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'department_id' => 'required|exists:departments,id',
            'note' => 'nullable|string',
            'purchase_order_id' => 'nullable|integer',
            'input_lainnya' => 'nullable|string',
            'ar_partner_id' => 'nullable|exists:ar_partners,id',
        ], [
            'tanggal.required' => 'Tanggal wajib diisi',
            'tipe_po.required' => 'Tipe PO wajib diisi',
            'terima_dari.required' => 'Terima Dari wajib diisi',
            'nilai.required' => 'Nominal wajib diisi',
            'nilai.numeric' => 'Nominal harus berupa angka',
            'selisih_penambahan.numeric' => 'Selisih penambahan harus berupa angka',
            'selisih_penambahan.min' => 'Selisih penambahan tidak boleh negatif',
            'selisih_pengurangan.numeric' => 'Selisih pengurangan harus berupa angka',
            'selisih_pengurangan.min' => 'Selisih pengurangan tidak boleh negatif',
            'nominal_akhir.numeric' => 'Nominal akhir harus berupa angka',
            'nominal_akhir.min' => 'Nominal akhir tidak boleh negatif',
            'bank_account_id.required' => 'Rekening wajib diisi',
            'bank_account_id.exists' => 'Rekening tidak valid',
            'department_id.required' => 'Departemen wajib diisi',
            'department_id.exists' => 'Departemen tidak valid',
            'ar_partner_id.exists' => 'AR Partner tidak valid',
        ]);

        // Generate no BM otomatis menggunakan service universal
        $bankAccount = \App\Models\BankAccount::with('department')->find($validated['bank_account_id']);
        if (!$bankAccount || !$bankAccount->department || !$bankAccount->department->alias) {
            return redirect()->back()->withErrors(['bank_account_id' => 'Rekening atau department tidak valid']);
}

        $validated['no_bm'] = DocumentNumberService::generateNumberForDate(
            'Bank Masuk',
            $validated['tipe_po'] ?? null,
            $bankAccount->department->id,
            $bankAccount->department->alias,
            Carbon::parse($validated['tanggal'])
        );
        $validated['status'] = 'aktif';
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        // Set default match_date jika terima_dari adalah Penjualan Toko dan match_date tidak diberikan
        if (($validated['terima_dari'] ?? null) === 'Penjualan Toko') {
            $validated['match_date'] = $validated['match_date'] ?? $validated['tanggal'];
}

        $bankMasuk = BankMasuk::create($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Membuat dokumen Bank Masuk',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil disimpan.');
}

    public function show(BankMasuk $bankMasuk)
    {
        // Bypass DepartmentScope for detail view to ensure all data is loaded
        $bankMasuk = BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with(['bankAccount.bank', 'bankAccount.department', 'department', 'creator', 'updater', 'arPartner'])
            ->findOrFail($bankMasuk->id);

        // Debug: Log the bankMasuk data to see what's being loaded
        Log::info('BankMasuk Detail Debug', [
            'bankMasuk_id' => $bankMasuk->id,
            'bankMasuk_no_bm' => $bankMasuk->no_bm,
            'department_id' => $bankMasuk->department_id,
            'has_department' => $bankMasuk->relationLoaded('department'),
            'department_data' => $bankMasuk->department ? [
                'id' => $bankMasuk->department->id,
                'name' => $bankMasuk->department->name,
            ] : null,
            'bank_account_id' => $bankMasuk->bank_account_id,
            'has_bankAccount' => $bankMasuk->relationLoaded('bankAccount'),
            'bankAccount_data' => $bankMasuk->bankAccount ? [
                'id' => $bankMasuk->bankAccount->id,
                'department_id' => $bankMasuk->bankAccount->department_id,
                'has_department' => $bankMasuk->bankAccount->relationLoaded('department'),
                'department_data' => $bankMasuk->bankAccount->department ? [
                    'id' => $bankMasuk->bankAccount->department->id,
                    'name' => $bankMasuk->bankAccount->department->name,
                ] : null,
            ] : null,
        ]);

        $bankAccounts = BankAccount::with(['bank', 'department'])->where('status', 'active')->orderBy('no_rekening')->get();
        $departments = DepartmentService::getOptionsForForm();
        $arPartners = \App\Models\ArPartner::orderBy('nama_ap')->get();
        return Inertia::render('bank-masuk/Detail', [
            'bankMasuk' => $bankMasuk,
            'bankAccounts' => $bankAccounts,
            'departments' => $departments,
            'arPartners' => $arPartners,
        ]);
}

    public function edit(BankMasuk $bankMasuk)
    {
        // Redirect to index page with form modal
        return redirect()->route('bank-masuk.index');
}

    public function update(Request $request, BankMasuk $bankMasuk)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'match_date' => 'nullable|date',
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'terima_dari' => 'required|in:Customer,Karyawan,Penjualan Toko,Lainnya',
            'nilai' => 'required|numeric|min:0',
            'selisih_penambahan' => 'nullable|numeric|min:0',
            'selisih_pengurangan' => 'nullable|numeric|min:0',
            'nominal_akhir' => 'nullable|numeric|min:0',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'department_id' => 'required|exists:departments,id',
            'note' => 'nullable|string',
            'purchase_order_id' => 'nullable|integer',
            'input_lainnya' => 'nullable|string',
            'ar_partner_id' => 'nullable|exists:ar_partners,id',
        ], [
            'tanggal.required' => 'Tanggal wajib diisi',
            'tipe_po.required' => 'Tipe PO wajib diisi',
            'terima_dari.required' => 'Terima Dari wajib diisi',
            'nilai.required' => 'Nominal wajib diisi',
            'nilai.numeric' => 'Nominal harus berupa angka',
            'selisih_penambahan.numeric' => 'Selisih penambahan harus berupa angka',
            'selisih_penambahan.min' => 'Selisih penambahan tidak boleh negatif',
            'selisih_pengurangan.numeric' => 'Selisih pengurangan harus berupa angka',
            'selisih_pengurangan.min' => 'Selisih pengurangan tidak boleh negatif',
            'nominal_akhir.numeric' => 'Nominal akhir harus berupa angka',
            'nominal_akhir.min' => 'Nominal akhir tidak boleh negatif',
            'bank_account_id.required' => 'Rekening wajib diisi',
            'bank_account_id.exists' => 'Rekening tidak valid',
            'department_id.required' => 'Departemen wajib diisi',
            'department_id.exists' => 'Departemen tidak valid',
            'ar_partner_id.exists' => 'AR Partner tidak valid',
        ]);

        // Set default match_date hanya jika terima_dari adalah Penjualan Toko dan match_date tidak diberikan
        // Untuk update, jangan override match_date yang sudah ada
        if (($validated['terima_dari'] ?? null) === 'Penjualan Toko' && empty($validated['match_date'])) {
            // Hanya set default jika match_date benar-benar kosong (null) dan bukan dari update
            // Untuk update, jika match_date dikirim sebagai null, berarti user ingin menghapusnya
            // Jika match_date tidak dikirim sama sekali, berarti user tidak mengubahnya
            if (!array_key_exists('match_date', $validated)) {
                $validated['match_date'] = $validated['tanggal'];
}
}

        // Explicitly remove no_bm from validated data
        unset($validated['no_bm']);

        // Nomor BM hanya berubah jika berpindah departemen (bank account) atau berganti bulan/tahun (berdasarkan tanggal dokumen)
        $oldDt = \Carbon\Carbon::parse($bankMasuk->tanggal);
        $newDt = \Carbon\Carbon::parse($validated['tanggal']);
        $departmentChanged = $bankMasuk->bank_account_id != $validated['bank_account_id'];
        $sameMonthYear = ($oldDt->month == $newDt->month) && ($oldDt->year == $newDt->year);
        $shouldRegenerateNoBm = $departmentChanged || !$sameMonthYear;

        // Jangan update no_bm jika tidak ada perubahan pemicu
        if (!$shouldRegenerateNoBm) {
            unset($validated['no_bm']);
} else {
            // Regenerate no_bm hanya jika perlu
            $bankAccount = \App\Models\BankAccount::with('department')->find($validated['bank_account_id']);
            $deptId = $bankAccount && $bankAccount->department ? $bankAccount->department->id : null;
            $deptAlias = $bankAccount && $bankAccount->department ? $bankAccount->department->alias : null;

            $oldParsed = DocumentNumberService::parseDocumentNumber($bankMasuk->no_bm);

            // Jika hanya pindah departemen dalam bulan/tahun yang sama, pertahankan nomor urut dan struktur lama (dengan/ tanpa tipe)
            if ($departmentChanged && $sameMonthYear && !empty($oldParsed)) {
                $nomorUrut = $oldParsed['nomor_urut'] ?? '0001';
                $dokumen = DocumentNumberService::getDocumentCode('Bank Masuk');
                $bulanRomawi = $this->bulanRomawi($newDt->format('n'));
                $tahun = $newDt->format('Y');
                $tipeCodeOld = $oldParsed['tipe_code'] ?? null;
                if ($tipeCodeOld) {
                    $validated['no_bm'] = "{$dokumen
}/{$tipeCodeOld
}/{$deptAlias
}/{$bulanRomawi
}/{$tahun
}/{$nomorUrut
}";
} else {
                    $validated['no_bm'] = "{$dokumen
}/{$deptAlias
}/{$bulanRomawi
}/{$tahun
}/{$nomorUrut
}";
}
} else {
                // Bulan/tahun berubah (atau sekaligus pindah departemen): generate nomor berdasarkan tanggal dokumen dan pertahankan struktur lama
                $tipeNameFromOld = null;
                if (!empty($oldParsed) && !empty($oldParsed['tipe_code'])) {
                    $tipeNameFromOld = DocumentNumberService::getTipeName($oldParsed['tipe_code']);
}
                $validated['no_bm'] = DocumentNumberService::generateNumberForDate(
                    'Bank Masuk',
                    $tipeNameFromOld,
                    $deptId ?? $bankMasuk->department_id,
                    $deptAlias ?? ($bankMasuk->bankAccount->department->alias ?? 'XXX'),
                    $newDt
                );
}
}

        $validated['updated_by'] = Auth::id();
        $bankMasuk->update($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => 'Mengubah dokumen Bank Masuk' . ($shouldRegenerateNoBm ? ' (dengan perubahan nomor BM)' : ''),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil diupdate.');
}

    public function destroy(BankMasuk $bankMasuk)
    {
        // Log aktivitas sebelum dihapus
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Menghapus dokumen Bank Masuk',
            'ip_address' => request()->ip(),
        ]);

        // Hapus data secara permanen
        $bankMasuk->delete();

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil dihapus.');
}

    public function download(BankMasuk $bankMasuk)
    {
        // TODO: Implementasi download PDF/Excel
        return response()->json(['message' => 'Fitur download belum tersedia.']);
}

    public function log(BankMasuk $bankMasuk, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $bankMasuk = \App\Models\BankMasuk::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->with([
                'department',
                'bankAccount' => function($query) {
                    $query->withoutGlobalScope(\App\Scopes\DepartmentScope::class);
                },
                'bankAccount.bank'
            ])
            ->findOrFail($bankMasuk->id);

        // Force load the relationships to ensure they're available
        $bankMasuk->load([
            'department',
            'bankAccount' => function($query) {
                $query->withoutGlobalScope(\App\Scopes\DepartmentScope::class);
            },
            'bankAccount.bank'
        ]);

        // Build logs query with relationships for frontend usage
        $logsQuery = \App\Models\BankMasukLog::with(['user.department', 'user.role'])
            ->where('bank_masuk_id', $bankMasuk->id);

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

        $roleOptions = Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = \App\Models\BankMasukLog::where('bank_masuk_id', $bankMasuk->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        $filters = $request->only(['search', 'action', 'role', 'department', 'date', 'per_page']);

        if ($request->wantsJson()) {
            return response()->json([
                'bankMasuk' => $bankMasuk,
                'logs' => $logs,
                'filters' => $filters,
                'roleOptions' => $roleOptions,
                'departmentOptions' => $departmentOptions,
                'actionOptions' => $actionOptions,
            ]);
}

        // Debug: Log the bankMasuk data to see what's being loaded
        Log::info('BankMasuk Log Debug', [
            'bankMasuk_id' => $bankMasuk->id,
            'bankMasuk_no_bm' => $bankMasuk->no_bm,
            'bank_account_id' => $bankMasuk->bank_account_id,
            'department_id' => $bankMasuk->department_id,
            'has_bankAccount' => $bankMasuk->relationLoaded('bankAccount'),
            'bankAccount_data' => $bankMasuk->bankAccount ? [
                'id' => $bankMasuk->bankAccount->id,
                'no_rekening' => $bankMasuk->bankAccount->no_rekening,
                'bank_id' => $bankMasuk->bankAccount->bank_id,
            ] : null,
            'department_data' => $bankMasuk->department ? [
                'id' => $bankMasuk->department->id,
                'name' => $bankMasuk->department->name,
            ] : null,
        ]);

        // Prepare bankMasuk data with explicit relationship data
        $bankMasukData = [
            'id' => $bankMasuk->id,
            'no_bm' => $bankMasuk->no_bm,
            'bank_account_id' => $bankMasuk->bank_account_id,
            'department_id' => $bankMasuk->department_id,
            'department' => $bankMasuk->department ? [
                'id' => $bankMasuk->department->id,
                'name' => $bankMasuk->department->name,
            ] : null,
            'bankAccount' => $bankMasuk->bankAccount ? [
                'id' => $bankMasuk->bankAccount->id,
                'no_rekening' => $bankMasuk->bankAccount->no_rekening,
                'bank_id' => $bankMasuk->bankAccount->bank_id,
                'bank' => $bankMasuk->bankAccount->bank ? [
                    'id' => $bankMasuk->bankAccount->bank->id,
                    'nama_bank' => $bankMasuk->bankAccount->bank->nama_bank,
                ] : null,
            ] : null,
        ];

        return Inertia::render('bank-masuk/Log', [
            'bankMasuk' => $bankMasukData,
            'logs' => $logs,
            'filters' => $filters,
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
}

    public function getNextNumber(Request $request)
    {
        $bank_account_id = $request->query('bank_account_id');
        $tanggal = $request->query('tanggal');
        $exclude_id = $request->query('exclude_id'); // Untuk mode edit
        $current_no_bm = $request->query('current_no_bm'); // Nomor BM saat ini untuk mode edit
        $tipe_po = $request->query('tipe_po'); // Sertakan tipe

        if (!$bank_account_id || !$tanggal) {
            return response()->json(['no_bm' => 'BM/TYP/DPT/I/2025/XXXX']);
}

        $bankAccount = \App\Models\BankAccount::with('department')->find($bank_account_id);
        if (!$bankAccount || !$bankAccount->department || !$bankAccount->department->alias) {
            return response()->json(['no_bm' => 'BM/TYP/DPT/I/2025/XXXX']);
}

        // Jika dalam mode edit dan ada current_no_bm, cek apakah hanya departemen yang berubah
        if ($exclude_id && $current_no_bm) {
            // Ambil data asli untuk perbandingan
            $originalData = \App\Models\BankMasuk::find($exclude_id);
            if ($originalData) {
                $originalDt = \Carbon\Carbon::parse($originalData->tanggal);
                $originalMonth = $originalDt->month;
                $originalYear = $originalDt->year;

                $newDt = \Carbon\Carbon::parse($tanggal);
                $newMonth = $newDt->month;
                $newYear = $newDt->year;

                // Jika bulan dan tahun sama (hanya departemen yang berubah), pertahankan nomor urut
                if ($originalMonth == $newMonth && $originalYear == $newYear) {
                    // Parse nomor urut dari current_no_bm (format 5 atau 6 bagian)
                    $parsed = DocumentNumberService::parseDocumentNumber($current_no_bm);
                    if (!empty($parsed)) {
                        $nomorUrut = $parsed['nomor_urut'] ?? '0001';
                        $dokumen = DocumentNumberService::getDocumentCode('Bank Masuk');
                        // Gunakan tipe dari nomor lama jika ada, fallback ke tipe dari request
                        $tipeCodeExisting = $parsed['tipe_code'] ?? null;
                        $tipeCodeFromReq = $tipe_po ? DocumentNumberService::getTipeCode($tipe_po) : null;
                        $tipeCode = $tipeCodeExisting ?: $tipeCodeFromReq;
                        $bulanRomawi = $this->bulanRomawi($newMonth);
                        $tahun = $newYear;
                        $deptAlias = $bankAccount->department->alias;
                        if ($tipeCode) {
                            $no_bm = "{$dokumen
}/{$tipeCode
}/{$deptAlias
}/{$bulanRomawi
}/{$tahun
}/{$nomorUrut
}";
} else {
                            $no_bm = "{$dokumen
}/{$deptAlias
}/{$bulanRomawi
}/{$tahun
}/{$nomorUrut
}";
}
                        return response()->json(['no_bm' => $no_bm]);
}
}
}
}

        // Generate nomor baru menggunakan service universal berbasis tanggal pilihan
        $no_bm = DocumentNumberService::generatePreviewNumberForDate(
            'Bank Masuk',
            $tipe_po ?? null,
            $bankAccount->department->id,
            $bankAccount->department->alias,
            \Carbon\Carbon::parse($tanggal)
        );

        return response()->json(['no_bm' => $no_bm]);
}

    public function getBankAccountsByDepartment(Request $request)
    {
        $department_id = $request->query('department_id');

        if (!$department_id) {
            return response()->json(['bankAccounts' => []]);
}

        $bankAccounts = BankAccount::with(['bank', 'department'])
            ->where('department_id', $department_id)
            ->where('status', 'active')
            ->orderBy('no_rekening')
            ->get();

        return response()->json(['bankAccounts' => $bankAccounts]);
}

    public function exportExcel(Request $request)
    {
        $ids = $request->input('ids', []);
        $fields = $request->input('fields', []);
        if (empty($ids) || empty($fields)) {
            return response()->json(['message' => 'Pilih data dan kolom yang ingin diexport.'], 422);
}

        $query = BankMasuk::with(['bankAccount.bank', 'bankAccount.department', 'creator', 'updater', 'arPartner'])
            ->whereIn('id', $ids);
        $rows = $query->get();

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $col = 'A';
        foreach ($fields as $field) {
            $sheet->setCellValue($col . '1', $field);
            $col++;
}

        // Set data
        $row = 2;
        foreach ($rows as $dataRow) {
            $col = 'A';
            foreach ($fields as $field) {
                $cellValue = '';
                $cellFormat = null;

                switch ($field) {
                    case 'bank_account':
                        $cellValue = $dataRow->bankAccount && $dataRow->bankAccount->department ? $dataRow->bankAccount->department->name : '';
                        break;
                    case 'no_rekening':
                        $cellValue = $dataRow->bankAccount ? $dataRow->bankAccount->no_rekening : '';
                        break;
                    case 'customer':
                        $cellValue = $dataRow->arPartner ? $dataRow->arPartner->nama_ap : '';
                        break;
                    case 'nilai':
                        $cellValue = $dataRow->nilai;
                        // Set format number dengan pemisah ribuan
                        $cellFormat = '#,##0.00';
                        break;
                    case 'created_by':
                        $cellValue = $dataRow->creator ? $dataRow->creator->name : '';
                        break;
                    case 'updated_by':
                        $cellValue = $dataRow->updater ? $dataRow->updater->name : '';
                        break;
                    default:
                        $cellValue = $dataRow->{$field
} ?? '';
}

                $sheet->setCellValue($col . $row, $cellValue);

                // Set format untuk kolom nilai
                if ($field === 'nilai' && $cellFormat) {
                    $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode($cellFormat);
}

                $col++;
}
            $row++;
}

        // Auto size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
}

        // Create Excel file
        $writer = new Xlsx($spreadsheet);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="bank_masuk_export.xlsx"',
        ];

        $callback = function() use ($writer) {
            $writer->save('php://output');
};

        return new StreamedResponse($callback, 200, $headers);
}

    public function getArPartners(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);
            $departmentId = $request->input('department_id');

            $query = \App\Models\ArPartner::select('id', 'nama_ap')
                ->orderBy('nama_ap');

            if ($search) {
                $query->where('nama_ap', 'like', "%{$search
}%");
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
                'message' => 'Terjadi kesalahan saat memuat data AR Partners'
            ], 500);
}
}

    // Fungsi bantu bulan ke romawi
    private function bulanRomawi($bulan) {
        $romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romawi[intval($bulan)] ?? $bulan;
}
}
