<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\NirwanaInvoice;
use App\Models\AutoMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BankMatchingExport;
use App\Models\Department;

class BankMatchingController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $departmentId = $request->input('department_id', '');

        // Check if this is an explicit search request (user clicked Match button)
        $isSearchRequest = $request->has('perform_match') || $request->input('perform_match') === 'true';

        Log::info('Bank Matching Index Request', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'department_id' => $departmentId,
            'is_search_request' => $isSearchRequest,
        ]);

        // Only perform matching if user explicitly requested it
        if ($isSearchRequest) {
            // Perform matching on explicit request only

            // Test koneksi database pgsql_nirwana
            try {
                $nirwanaConnection = DB::connection('pgsql_nirwana')->getPdo();
                Log::info('PostgreSQL Nirwana database connection successful');
            } catch (\Exception $e) {
                Log::error('PostgreSQL Nirwana database connection failed', ['error' => $e->getMessage()]);
                return Inertia::render('bank-matching/Index', [
                    'matchingResults' => [],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'department_id' => $departmentId,
                    ],
                    'error' => 'Koneksi database PostgreSQL Nirwana gagal: ' . $e->getMessage()
                ]);
            }

            // Execute bank matching logic
            Log::info('Executing bank matching logic', ['start_date' => $startDate, 'end_date' => $endDate]);

            // Ambil data invoice dari database pgsql_nirwana
            $invoiceList = NirwanaInvoice::getInvoiceData($startDate, $endDate);
            $invoiceList = collect($invoiceList);

            Log::info('Invoice data retrieved', ['count' => $invoiceList->count()]);

            // Ambil data bank masuk dari database sefti
            // Hanya untuk Penjualan Toko dan gunakan match_date sebagai tanggal acuan
            $bankMasukQuery = BankMasuk::query()
                ->where('status', 'aktif')
                ->where('terima_dari', 'Penjualan Toko')
                ->whereBetween('match_date', [$startDate, $endDate]);

            // Apply department filter if specified
            if ($departmentId) {
                $bankMasukQuery->where('department_id', $departmentId);
            }

            $bankMasukList = $bankMasukQuery->orderBy('match_date')->orderBy('created_at')->get();

            Log::info('BankMasuk data retrieved', ['count' => $bankMasukList->count()]);

            // Lakukan matching berdasarkan rules baru
            $matchingResults = $this->performBankMatching($invoiceList, $bankMasukList);

            Log::info('Bank matching completed', ['results_count' => count($matchingResults)]);
        } else {
            // No explicit search request, return empty results
            // Return empty results if no explicit search request
            $matchingResults = [];
        }

        return Inertia::render('bank-matching/Index', [
            'matchingResults' => $matchingResults,
            'departments' => Department::where('status', 'active')->orderBy('name')->get(['id', 'name', 'status']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'department_id' => $departmentId,
                'tab' => $request->input('tab', 'auto-matching'),
            ],
        ]);
    }

    /**
     * Get cabang mapping from Nirwana to SEFTI department
     */
    protected function getCabangMap()
    {
        return [
            'HSD09' => 4,  // Nirwana Textile Hasanudin
            'BKR92' => 5,  // Nirwana Textile Bkr
            'HOS199' => 6, // Nirwana Textile Yogyakarta HOS Cokro
            'BALI292' => 7, // Nirwana Textile Bali
            'SBY299' => 8,  // Nirwana Textile Surabaya
        ];
    }

    public function performBankMatching($invoiceList, $bankMasukList)
    {
        $results = [];
        $usedBankMasukIds = [];

        Log::info('Starting performBankMatching', [
            'total_invoices' => $invoiceList->count(),
            'total_bank_masuk' => $bankMasukList->count()
        ]);

        // Get already matched data from auto_matches table (database sefti) - for reference only
        $alreadyMatchedInvoiceIds = AutoMatch::pluck('sj_no')->toArray(); // Using sj_no field for invoice_id
        $alreadyMatchedBankMasukIds = AutoMatch::pluck('bank_masuk_id')->toArray();

        Log::info('Already matched data', [
            'already_matched_invoice_count' => count($alreadyMatchedInvoiceIds),
            'already_matched_bm_count' => count($alreadyMatchedBankMasukIds),
        ]);

        // Filter out already matched invoice data
        $availableInvoiceList = collect($invoiceList)->filter(function($invoice) use ($alreadyMatchedInvoiceIds) {
            return !in_array($invoice->faktur_id, $alreadyMatchedInvoiceIds);
        });

        // Filter out already matched Bank Masuk data
        $availableBankMasukList = collect($bankMasukList)->filter(function($bankMasuk) use ($alreadyMatchedBankMasukIds) {
            return !in_array($bankMasuk->id, $alreadyMatchedBankMasukIds);
        });

        Log::info('Available data after filtering', [
            'available_invoice_count' => $availableInvoiceList->count(),
            'available_bm_count' => $availableBankMasukList->count(),
        ]);

        // Group invoices dan bank masuk berdasarkan tanggal, nominal, dan cabang/departemen (tanpa nama)
        $invoiceByMatchKey = [];
        $bankMasukByMatchKey = [];
        $cabangMap = $this->getCabangMap();

        foreach ($availableInvoiceList as $invoice) {
            $tanggal = $invoice->tanggal;
            $tanggalFormatted = $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : '';
            $nominal = (float) ($invoice->nominal ?? $invoice->amount ?? 0);
            // Map cabang Nirwana ke department_id SEFTI
            $mappedDepartmentId = isset($invoice->cabang) && isset($cabangMap[$invoice->cabang])
                ? (string) $cabangMap[$invoice->cabang]
                : '';
            // Normalisasi nominal ke 2 desimal
            $formattedNominal = number_format($nominal, 2, '.', '');
            $key = $tanggalFormatted . '_' . $formattedNominal . '_' . $mappedDepartmentId;

            if (!isset($invoiceByMatchKey[$key])) {
                $invoiceByMatchKey[$key] = [];
            }
            $invoiceByMatchKey[$key][] = $invoice;
        }

        foreach ($availableBankMasukList as $bankMasuk) {
            $tanggal = $bankMasuk->match_date;
            $tanggalFormatted = $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : '';
            $nilai = (float) $bankMasuk->nilai;
            // Gunakan department_id langsung
            $departmentId = (string) ($bankMasuk->department_id ?? '');
            // Normalisasi nilai ke 2 desimal
            $formattedNilai = number_format($nilai, 2, '.', '');
            $key = $tanggalFormatted . '_' . $formattedNilai . '_' . $departmentId;

            if (!isset($bankMasukByMatchKey[$key])) {
                $bankMasukByMatchKey[$key] = [];
            }
            $bankMasukByMatchKey[$key][] = $bankMasuk;
        }

        Log::info('Grouped data', [
            'invoice_groups_count' => count($invoiceByMatchKey),
            'bm_groups_count' => count($bankMasukByMatchKey),
        ]);

        // Removed verbose unmatched group logging to reduce noise

        // Lakukan matching berdasarkan tanggal, nominal, customer name, dan cabang/departemen yang sama
        foreach ($invoiceByMatchKey as $matchKey => $invoiceGroup) {
            if (isset($bankMasukByMatchKey[$matchKey])) {
                $bankMasukGroup = $bankMasukByMatchKey[$matchKey];

                // Matching group found

                // Sort berdasarkan urutan waktu pembuatan
                $sortedInvoices = collect($invoiceGroup)->sortBy('faktur_id');
                $sortedBankMasuk = collect($bankMasukGroup)->sortBy('created_at');

                // Match berdasarkan aturan 1 Invoice = 1 BM
                // Gunakan jumlah yang lebih kecil untuk memastikan 1:1 matching
                $minCount = min(count($invoiceGroup), count($bankMasukGroup));

                for ($i = 0; $i < $minCount; $i++) {
                    $invoice = $invoiceGroup[$i];
                    $bankMasuk = $bankMasukGroup[$i];

                    // Pastikan bank masuk belum digunakan
                    if (!in_array($bankMasuk->id, $usedBankMasukIds)) {

                        // Ambil nama departemen dari database lokal
                        $departmentName = null;
                        $departmentId = null;
                        if (!empty($invoice->cabang)) {
                            $departmentId = $cabangMap[$invoice->cabang] ?? null;
                            if ($departmentId) {
                                $department = \App\Models\Department::find($departmentId);
                                $departmentName = $department ? $department->name : null;
                            }
                        }

                        $results[] = [
                            'no_invoice' => $invoice->faktur_id,
                            'tanggal_invoice' => $invoice->tanggal ? Carbon::parse($invoice->tanggal)->format('Y-m-d') : null,
                            'nilai_invoice' => (float) ($invoice->nominal ?? $invoice->amount ?? 0),
                            'customer_name' => $invoice->nama_customer ?? null,
                            'cabang' => $departmentName ?? $invoice->cabang ?? null, // nama departemen dari database lokal
                            'department_id' => $departmentId, // tambahkan department_id untuk mapping
                            'no_bank_masuk' => $bankMasuk->no_bm,
                            'tanggal_bank_masuk' => $bankMasuk->match_date ? Carbon::parse($bankMasuk->match_date)->format('Y-m-d') : null,
                            'nilai_bank_masuk' => (float) $bankMasuk->nilai,
                            'nama_ap' => null,
                            'alias' => (string) ($bankMasuk->department_id ?? ''), // gunakan department_id untuk mapping
                            'is_matched' => true,
                            'sj_no' => $invoice->faktur_id, // Using faktur_id as sj_no for compatibility
                            'bank_masuk_id' => (int) $bankMasuk->id,
                        ];

                        $usedBankMasukIds[] = $bankMasuk->id;
                    }
                }
            }
        }

        Log::info('Matching completed', [
            'total_matches_found' => count($results),
            'used_bank_masuk_ids' => $usedBankMasukIds
        ]);

        return $results;
    }

    public function store(Request $request)
    {
        Log::info('Bank Matching Store called', [
            'method' => $request->method(),
            'matches_count' => count($request->input('matches', [])),
        ]);

        try {
            $validated = $request->validate([
                'matches' => 'required|array|min:1',
                'matches.*.sj_no' => 'required|string|max:255',
                'matches.*.bank_masuk_id' => 'required|integer|exists:bank_masuks,id',
                'matches.*.no_invoice' => 'required|string|max:255',
                'matches.*.tanggal_invoice' => 'required|date_format:Y-m-d',
                'matches.*.nilai_invoice' => 'required|numeric|min:0',
                'matches.*.customer_name' => 'nullable|string|max:255',
                'matches.*.cabang' => 'nullable|string|max:255',
                'matches.*.department_id' => 'nullable|integer|exists:departments,id',
                'matches.*.no_bank_masuk' => 'required|string|max:255',
                'matches.*.tanggal_bank_masuk' => 'required|date_format:Y-m-d',
                'matches.*.nilai_bank_masuk' => 'required|numeric|min:0',
                'matches.*.nama_ap' => 'nullable|string|max:255',
                'matches.*.alias' => 'nullable|string|max:255',
            ], [
                'matches.required' => 'Data matches wajib diisi.',
                'matches.array' => 'Data matches harus berupa array.',
                'matches.min' => 'Minimal harus ada 1 data untuk disimpan.',
                'matches.*.sj_no.required' => 'SJ number wajib diisi.',
                'matches.*.sj_no.string' => 'SJ number harus berupa string.',
                'matches.*.bank_masuk_id.required' => 'Bank masuk ID wajib diisi.',
                'matches.*.bank_masuk_id.integer' => 'Bank masuk ID harus berupa angka.',
                'matches.*.bank_masuk_id.exists' => 'Bank masuk ID tidak ditemukan.',
                'matches.*.no_invoice.required' => 'No invoice wajib diisi.',
                'matches.*.no_invoice.string' => 'No invoice harus berupa string.',
                'matches.*.tanggal_invoice.required' => 'Tanggal invoice wajib diisi.',
                'matches.*.tanggal_invoice.date_format' => 'Format tanggal invoice tidak valid.',
                'matches.*.nilai_invoice.required' => 'Nilai invoice wajib diisi.',
                'matches.*.nilai_invoice.numeric' => 'Nilai invoice harus berupa angka.',
                'matches.*.nilai_invoice.min' => 'Nilai invoice minimal 0.',
                'matches.*.department_id.integer' => 'Department ID harus berupa angka.',
                'matches.*.department_id.exists' => 'Department ID tidak ditemukan.',
                'matches.*.no_bank_masuk.required' => 'No bank masuk wajib diisi.',
                'matches.*.no_bank_masuk.string' => 'No bank masuk harus berupa string.',
                'matches.*.tanggal_bank_masuk.required' => 'Tanggal bank masuk wajib diisi.',
                'matches.*.tanggal_bank_masuk.date_format' => 'Format tanggal bank masuk tidak valid.',
                'matches.*.nilai_bank_masuk.required' => 'Nilai bank masuk wajib diisi.',
                'matches.*.nilai_bank_masuk.numeric' => 'Nilai bank masuk harus berupa angka.',
                'matches.*.nilai_bank_masuk.min' => 'Nilai bank masuk minimal 0.',
            ]);

            Log::info('Validation passed', ['validated_count' => count($validated['matches'])]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                // do not log full request payload to reduce noise
            ]);
            throw $e;
        }

        DB::beginTransaction();
        try {
            foreach ($validated['matches'] as $match) {
                // Check if bank_masuk_id exists
                $bankMasuk = BankMasuk::find($match['bank_masuk_id']);
                if (!$bankMasuk) {
                    Log::error('Bank Masuk not found', ['bank_masuk_id' => $match['bank_masuk_id']]);
                    throw new \Exception("Bank Masuk dengan ID {$match['bank_masuk_id']} tidak ditemukan.");
                }

                // Test if we can create the record
                try {
                    // Find department by department_id, cabang, or alias
                    $departmentId = null;
                    if (isset($match['department_id'])) {
                        $departmentId = $match['department_id'];
                    } elseif (isset($match['cabang'])) {
                        $department = Department::where('name', $match['cabang'])
                            ->orWhere('alias', $match['cabang'])
                            ->first();
                        if ($department) {
                            $departmentId = $department->id;
                        }
                    }

                    // If not found by cabang, try alias
                    if (!$departmentId && isset($match['alias'])) {
                        $department = Department::where('name', $match['alias'])
                            ->orWhere('alias', $match['alias'])
                            ->first();
                        if ($department) {
                            $departmentId = $department->id;
                        }
                    }

                    $autoMatch = AutoMatch::create([
                        'bank_masuk_id' => $match['bank_masuk_id'],
                        'sj_no' => $match['no_invoice'],
                        'sj_tanggal' => $match['tanggal_invoice'],
                        'sj_nilai' => $match['nilai_invoice'],
                        'invoice_customer_name' => null,
                        'department_id' => $departmentId,
                        'bm_no' => $match['no_bank_masuk'],
                        'bm_tanggal' => $match['tanggal_bank_masuk'],
                        'bm_nilai' => $match['nilai_bank_masuk'],
                        'bank_masuk_customer_name' => null,
                        'status' => 'confirmed',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    // Created successfully
                } catch (\Exception $createError) {
                    Log::error('Failed to create AutoMatch record', [
                        'error' => $createError->getMessage(),
                        'data' => $match
                    ]);
                    throw $createError;
                }
            }

            DB::commit();
            Log::info('Bank Matching Store committed', ['records' => count($validated['matches'])]);

            // Redirect back to the same page with the same filters and perform match again
            $redirectUrl = route('bank-matching.index', [
                'start_date' => $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
                'end_date' => $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
                'perform_match' => 'true'
            ]);

            return redirect($redirectUrl)->with('success', 'Data bank matching berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bank Matching Store Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            Log::info('Bank Matching Export started', ['start_date' => $startDate, 'end_date' => $endDate]);

            // Ambil data invoice dari database pgsql_nirwana
            $invoiceList = NirwanaInvoice::getInvoiceData($startDate, $endDate);
            $invoiceList = collect($invoiceList);

            Log::info('Invoice data retrieved for export', ['count' => $invoiceList->count()]);

            // Ambil data bank masuk dari database sefti
            $bankMasukList = BankMasuk::where('status', 'aktif')
                ->where('terima_dari', 'Penjualan Toko')
                ->whereBetween('match_date', [$startDate, $endDate])
                ->orderBy('match_date')
                ->orderBy('created_at')
                ->get();

            Log::info('BankMasuk data retrieved for export', ['count' => $bankMasukList->count()]);

            // Get already matched data from auto_matches table
            $alreadyMatchedInvoiceIds = AutoMatch::pluck('sj_no')->toArray(); // Using sj_no field for invoice_id

            // Filter hanya data yang belum dimatch (invoice yang belum memiliki bank masuk)
            $unmatchedInvoiceData = collect($invoiceList)->filter(function($invoice) use ($alreadyMatchedInvoiceIds) {
                return !in_array($invoice->faktur_id, $alreadyMatchedInvoiceIds);
            })->map(function($invoice) {
                return [
                    'no_invoice' => $invoice->faktur_id,
                    'tanggal_invoice' => $invoice->tanggal ? Carbon::parse($invoice->tanggal)->format('Y-m-d') : null,
                    'nilai_invoice' => (float) $invoice->nominal,
                    'customer_name' => $invoice->nama_customer,
                    'cabang' => $invoice->cabang,
                    'status_match' => 'Belum Dimatch'
                ];
            });

            Log::info('Export data prepared', [
                'total_invoice_records' => $invoiceList->count(),
                'already_matched_records' => count($alreadyMatchedInvoiceIds),
                'unmatched_records' => $unmatchedInvoiceData->count(),
            ]);

            $filename = "bank_matching_unmatched_invoices_{$startDate}_{$endDate}.xlsx";

            Log::info('Export file prepared', ['filename' => $filename, 'record_count' => $unmatchedInvoiceData->count()]);

            return Excel::download(new BankMatchingExport($unmatchedInvoiceData), $filename);
        } catch (\Exception $e) {
            Log::error('Bank Matching Export Excel Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'start_date' => $startDate ?? null,
                'end_date' => $endDate ?? null
            ]);

            return response()->json(['message' => 'Terjadi kesalahan saat export: ' . $e->getMessage()], 500);
        }
    }

    public function getUnmatchedInvoices(Request $request)
    {
        try {
            $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

            // Ambil data invoice dari database pgsql_nirwana
            $invoiceList = NirwanaInvoice::getInvoiceData($startDate, $endDate);
            $invoiceList = collect($invoiceList);

            // Get already matched data from auto_matches table
            $alreadyMatchedInvoiceIds = AutoMatch::pluck('sj_no')->toArray(); // Using sj_no field for invoice_id

            // Filter hanya data yang belum dimatch (invoice yang belum memiliki bank masuk)
            $unmatchedInvoices = collect($invoiceList)->filter(function($invoice) use ($alreadyMatchedInvoiceIds) {
                return !in_array($invoice->faktur_id, $alreadyMatchedInvoiceIds);
            })->values();

            return response()->json($unmatchedInvoices);
        } catch (\Exception $e) {
            Log::error('Get Unmatched Invoices Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'start_date' => $startDate ?? null,
                'end_date' => $endDate ?? null
            ]);
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil data invoice yang belum dimatch: ' . $e->getMessage()], 500);
        }
    }

    public function getMatchedData(Request $request)
    {
        try {
            $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $search = $request->query('search', '');
            $perPage = $request->query('per_page', 10);
            $departmentId = $request->query('department_id', '');

            Log::info('Get Matched Data request', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'per_page' => $perPage,
                'department_id' => $departmentId,
            ]);

            // Ambil data AutoMatch dengan relasi bankMasuk
            $query = AutoMatch::with(['bankMasuk'])
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('sj_no', 'like', "%$search%")
                      ->orWhere('bm_no', 'like', "%$search%")
                      ->orWhere('invoice_customer_name', 'like', "%$search%")
                      ->orWhere('bank_masuk_customer_name', 'like', "%$search%");
                });
            }

            // Apply department filter if specified
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }

            // Sort by created_at descending (newest first)
            $matchedData = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Ambil customer name dari invoice untuk setiap record
            $cabangMap = $this->getCabangMap();
            $matchedData->getCollection()->transform(function ($match) use ($cabangMap) {
                // Ambil customer name dari invoice berdasarkan sj_no
                try {
                    $invoiceData = NirwanaInvoice::getInvoiceData(
                        $match->sj_tanggal,
                        $match->sj_tanggal
                    );

                    $invoice = collect($invoiceData)->firstWhere('faktur_id', $match->sj_no);
                    $match->invoice_customer_name = $invoice ? $invoice->nama_customer : null;

                    // Ambil nama departemen dari relasi department
                    $match->department_name = null;
                    if ($match->department_id) {
                        $department = \App\Models\Department::find($match->department_id);
                        if ($department) {
                            $match->department_name = $department->name;
                        }
                    }

                    // Jika masih kosong, coba ambil dari relasi bankMasuk
                    if (empty($match->department_name) && $match->bankMasuk && $match->bankMasuk->department_id) {
                        $department = \App\Models\Department::find($match->bankMasuk->department_id);
                        if ($department) {
                            $match->department_name = $department->name;
                        }
                    }


                } catch (\Exception $e) {
                    Log::warning('Failed to get invoice customer name or department', [
                        'sj_no' => $match->sj_no,
                        'error' => $e->getMessage()
                    ]);
                    $match->invoice_customer_name = null;
                    $match->department_name = null;
                }

                return $match;
            });

            Log::info('Matched data retrieved', [
                'total' => $matchedData->total(),
                'current_page' => $matchedData->currentPage(),
                'per_page' => $matchedData->perPage(),
                'last_page' => $matchedData->lastPage(),
            ]);

            return response()->json($matchedData);
        } catch (\Exception $e) {
            Log::error('Get Matched Data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'start_date' => $startDate ?? null,
                'end_date' => $endDate ?? null
            ]);
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil data yang sudah dimatch: ' . $e->getMessage()], 500);
        }
    }

    public function getAllInvoices(Request $request)
    {
        try {
            $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $search = $request->query('search', '');
            $perPage = $request->query('per_page', 10);
            $departmentId = $request->query('department_id', '');

            // Mapping cabang_id dari Nirwana ke department_id di SEFTI
            $cabangMap = $this->getCabangMap();

            Log::info('Get All Invoices request', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'per_page' => $perPage,
                'department_id' => $departmentId,
            ]);

            // Test koneksi database pgsql_nirwana
            $nirwanaAvailable = false;
            try {
                $connection = DB::connection('pgsql_nirwana');
                $pdo = $connection->getPdo();
                $nirwanaAvailable = true;
            } catch (\Exception $e) {
                Log::error('PostgreSQL Nirwana Database connection failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't return error immediately, try to continue with fallback
            }

            // Ambil data invoice dari database pgsql_nirwana
            $invoiceList = collect([]); // Default empty collection
            if ($nirwanaAvailable) {
                try {
                    // Use model method
                    $invoiceRaw = NirwanaInvoice::getInvoiceData($startDate, $endDate);

                    // Convert raw results to collection
                    $invoiceList = collect($invoiceRaw);
                    Log::info('Invoice data retrieved', ['count' => $invoiceList->count()]);
                } catch (\Exception $e) {
                    Log::error('Failed to retrieve Invoice data from PostgreSQL Nirwana', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with empty collection
                }
            }

            // Get already matched data from auto_matches table (database sefti)
            $alreadyMatchedInvoiceIds = [];
            try {
                $alreadyMatchedInvoiceIds = AutoMatch::pluck('sj_no')->toArray(); // Using sj_no field for invoice_id
                Log::info('AutoMatch data retrieved', ['count' => count($alreadyMatchedInvoiceIds)]);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve matched data from SEFTI', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with empty array if we can't get matched data
            }

            // Transform data dan tambahkan status matched
            $invoiceData = collect($invoiceList)->map(function($item) use ($alreadyMatchedInvoiceIds) {
                return [
                    'faktur_id' => $item->faktur_id ?? null,
                    'tanggal' => $item->tanggal ? Carbon::parse($item->tanggal)->format('Y-m-d') : null,
                    'nominal' => (float) ($item->nominal ?? 0),
                    'nama_customer' => $item->nama_customer ?? null,
                    'cabang' => $item->cabang ?? null,
                    'is_matched' => in_array($item->faktur_id ?? '', $alreadyMatchedInvoiceIds)
                ];
            });

            // Optionally inspect distribution during debugging only (removed)

            // Apply search filter
            if ($search) {
                $invoiceData = $invoiceData->filter(function($invoice) use ($search) {
                    return str_contains(strtolower($invoice['faktur_id'] ?? ''), strtolower($search)) ||
                           str_contains(strtolower($invoice['nama_customer'] ?? ''), strtolower($search)) ||
                           str_contains(strtolower($invoice['cabang'] ?? ''), strtolower($search));
                });
            }

            // Apply department filter
            if ($departmentId) {
                $invoiceData = $invoiceData->filter(function($invoice) use ($departmentId, $cabangMap) {
                    // Cari cabang yang sesuai dengan department_id
                    $matchingCabang = array_search($departmentId, $cabangMap);
                    return $invoice['cabang'] == $matchingCabang;
                });
            }

            // Manual pagination
            $total = $invoiceData->count();
            $currentPage = (int) $request->query('page', 1);
            $perPage = (int) $perPage;
            $offset = ($currentPage - 1) * $perPage;

            $paginatedData = $invoiceData->slice($offset, $perPage)->values();
            $lastPage = ceil($total / $perPage);

            Log::info('All Invoices response prepared', [
                'total_records' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'matched_count' => $invoiceData->where('is_matched', true)->count(),
                'unmatched_count' => $invoiceData->where('is_matched', false)->count(),
                'nirwana_available' => $nirwanaAvailable,
                'department_filter_applied' => !empty($departmentId),
                'department_id' => $departmentId,
            ]);

            return response()->json([
                'data' => $paginatedData,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
                'nirwana_available' => $nirwanaAvailable,
                'message' => $nirwanaAvailable ? null : 'Database PostgreSQL Nirwana tidak tersedia, menampilkan data kosong'
            ]);
        } catch (\Exception $e) {
            Log::error('Get All Invoices Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'start_date' => $startDate ?? null,
                'end_date' => $endDate ?? null
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data invoice: ' . $e->getMessage(),
                'error_details' => $e->getMessage(),
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'total' => 0,
                'from' => 0,
                'to' => 0
            ], 500);
        }
    }

    public function test(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        try {
            // Test database connections
            $seftiConnection = DB::connection()->getPdo();
            $nirwanaConnection = DB::connection('pgsql_nirwana')->getPdo();

            // Test NirwanaInvoice connection
            $invoiceList = NirwanaInvoice::getInvoiceData($startDate, $endDate);
            $invoiceList = collect($invoiceList);

            // Test BankMasuk connection
            $bankMasukList = BankMasuk::query()
                ->where('status', 'aktif')
                ->where('terima_dari', 'Penjualan Toko')
                ->whereBetween('match_date', [$startDate, $endDate])
                ->orderBy('match_date')
                ->orderBy('created_at')
                ->get();

            // Test matching
            $matchingResults = $this->performBankMatching($invoiceList, $bankMasukList);

            return response()->json([
                'success' => true,
                'connections' => [
                    'sefti' => 'Connected',
                    'pgsql_nirwana' => 'Connected',
                ],
                'invoice_count' => $invoiceList->count(),
                'bank_masuk_count' => $bankMasukList->count(),
                'matching_results_count' => count($matchingResults),
                'sample_invoices' => $invoiceList->take(3)->map(function($item) {
                    return [
                        'faktur_id' => $item->faktur_id,
                        'tanggal' => $item->tanggal,
                        'nominal' => $item->nominal,
                        'nama_customer' => $item->nama_customer,
                        'cabang' => $item->cabang,
                    ];
                }),
                'sample_bank_masuk' => $bankMasukList->take(3)->map(function($item) {
                    return [
                        'id' => $item->id,
                        'no_bm' => $item->no_bm,
                        'match_date' => $item->match_date,
                        'nilai' => $item->nilai,
                        'department_id' => $item->department_id,
                    ];
                }),
                'sample_matches' => array_slice($matchingResults, 0, 3),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function testStore(Request $request)
    {
        Log::info('=== TEST STORE METHOD CALLED ===');

        try {
            // Test with minimal data
            $testData = [
                'matches' => [
                    [
                        'doc_number' => 'TEST_001',
                        'bank_masuk_id' => 1,
                        'no_invoice' => 'TEST_INV_001',
                        'tanggal_invoice' => '2025-07-30',
                        'nilai_invoice' => 1000,
                        'no_bank_masuk' => 'TEST_BM_001',
                        'tanggal_bank_masuk' => '2025-07-30',
                        'nilai_bank_masuk' => 1000,
                    ]
                ]
            ];

            $request->merge($testData);

            // Call the actual store method
            return $this->store($request);

        } catch (\Exception $e) {
            Log::error('Test store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testDatabaseConnection(Request $request)
    {
        try {
            Log::info('Testing database connections...');

            // Test SEFTI database
            try {
                $seftiConnection = DB::connection()->getPdo();
                Log::info('SEFTI Database connection successful');
                $seftiStatus = 'Connected';
            } catch (\Exception $e) {
                Log::error('SEFTI Database connection failed', [
                    'error' => $e->getMessage()
                ]);
                $seftiStatus = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL Nirwana database
            try {
                $nirwanaConnection = DB::connection('pgsql_nirwana')->getPdo();
                Log::info('PostgreSQL Nirwana Database connection successful');
                $nirwanaStatus = 'Connected';
            } catch (\Exception $e) {
                Log::error('PostgreSQL Nirwana Database connection failed', [
                    'error' => $e->getMessage()
                ]);
                $nirwanaStatus = 'Failed: ' . $e->getMessage();
            }

            // Test NirwanaInvoice model
            try {
                $invoiceData = NirwanaInvoice::getInvoiceData();
                $invoiceCount = count($invoiceData);
                Log::info('NirwanaInvoice model test successful', ['count' => $invoiceCount]);
                $invoiceStatus = 'Connected - Count: ' . $invoiceCount;
            } catch (\Exception $e) {
                Log::error('NirwanaInvoice model test failed', [
                    'error' => $e->getMessage()
                ]);
                $invoiceStatus = 'Failed: ' . $e->getMessage();
            }

            // Test AutoMatch model
            try {
                $autoMatchCount = AutoMatch::count();
                Log::info('AutoMatch model test successful', ['count' => $autoMatchCount]);
                $autoMatchStatus = 'Connected - Count: ' . $autoMatchCount;
            } catch (\Exception $e) {
                Log::error('AutoMatch model test failed', [
                    'error' => $e->getMessage()
                ]);
                $autoMatchStatus = 'Failed: ' . $e->getMessage();
            }

            return response()->json([
                'sefti_database' => $seftiStatus,
                'pgsql_nirwana_database' => $nirwanaStatus,
                'nirwana_invoice_model' => $invoiceStatus,
                'auto_match_model' => $autoMatchStatus,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Database connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testSimple(Request $request)
    {
        try {
            Log::info('Simple test started');
            $results = [];

            try {
                $seftiConnection = DB::connection()->getPdo();
                $results['sefti'] = 'Connected';
            } catch (\Exception $e) {
                $results['sefti'] = 'Failed: ' . $e->getMessage();
                Log::error('SEFTI database failed: ' . $e->getMessage());
            }

            try {
                $nirwanaConnection = DB::connection('pgsql_nirwana')->getPdo();
                $results['pgsql_nirwana'] = 'Connected';
            } catch (\Exception $e) {
                $results['pgsql_nirwana'] = 'Failed: ' . $e->getMessage();
                Log::error('PostgreSQL Nirwana database failed: ' . $e->getMessage());
            }

            try {
                $autoMatchCount = AutoMatch::count();
                $results['auto_match_count'] = $autoMatchCount;
            } catch (\Exception $e) {
                $results['auto_match_count'] = 'Failed: ' . $e->getMessage();
                Log::error('AutoMatch query failed: ' . $e->getMessage());
            }

            try {
                $invoiceData = NirwanaInvoice::getInvoiceData();
                $invoiceCount = count($invoiceData);
                $results['nirwana_invoice_count'] = $invoiceCount;
            } catch (\Exception $e) {
                $results['nirwana_invoice_count'] = 'Failed: ' . $e->getMessage();
                Log::error('NirwanaInvoice query failed: ' . $e->getMessage());
            }

            Log::info('Simple test completed');

            return response()->json([
                'status' => 'success',
                'message' => 'Test completed',
                'results' => $results,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Simple test failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(Request $request)
    {
        try {
            Log::info('Testing GJTRADING3 database connection...');

            // Test koneksi langsung ke database GJTRADING3
            $connection = DB::connection('gjtrading3');
            $pdo = $connection->getPdo();

            Log::info('GJTRADING3 connection successful');

            // Test query sederhana dengan raw query
            $result = $connection->select('SELECT COUNT(*) as count FROM v_sj_new');
            $count = $result[0]->count ?? 0;

            Log::info('v_sj_new view accessible, count: ' . $count);

            // Test query dengan date range
            $startDate = '2025-07-01';
            $endDate = '2025-07-31';

            $dateResult = $connection->select("
                SELECT COUNT(*) as count
                FROM v_sj_new
                WHERE date BETWEEN ? AND ?
            ", [$startDate, $endDate]);

            $dateCount = $dateResult[0]->count ?? 0;

            Log::info('v_sj_new with date range, count: ' . $dateCount);

            // Test sample data
            $sampleData = $connection->select("
                SELECT doc_number, date, total, name, currency
                FROM v_sj_new
                WHERE date BETWEEN ? AND ?
                ORDER BY date DESC
                LIMIT 5
            ", [$startDate, $endDate]);

            Log::info('Sample data retrieved, count: ' . count($sampleData));

            return response()->json([
                'status' => 'success',
                'message' => 'Database connection successful',
                'data' => [
                    'total_records' => $count,
                    'date_range_records' => $dateCount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'sample_data' => $sampleData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('GJTRADING3 connection test failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    public function testBasic(Request $request)
    {
        try {
            Log::info('Basic test started');

            // Test 1: Basic response
            $response = [
                'status' => 'success',
                'message' => 'Basic test working',
                'timestamp' => now()->toISOString()
            ];

            // Test 2: Database connection
            try {
                $connection = DB::connection('gjtrading3');
                $pdo = $connection->getPdo();
                $response['database'] = 'Connected';
                Log::info('Database connection successful');
            } catch (\Exception $e) {
                $response['database'] = 'Failed: ' . $e->getMessage();
                Log::error('Database connection failed: ' . $e->getMessage());
            }

            // Test 3: Direct query
            if (isset($response['database']) && $response['database'] === 'Connected') {
                try {
                    $result = DB::connection('gjtrading3')->select('SELECT COUNT(*) as count FROM v_sj_new');
                    $count = $result[0]->count ?? 0;
                    $response['query'] = 'Success - Count: ' . $count;
                    Log::info('Query successful, count: ' . $count);
                } catch (\Exception $e) {
                    $response['query'] = 'Failed: ' . $e->getMessage();
                    Log::error('Query failed: ' . $e->getMessage());
                }
            }

            Log::info('Basic test completed');
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Basic test failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
