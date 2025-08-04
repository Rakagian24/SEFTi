<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\SjNew;
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

class BankMatchingController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Check if this is an explicit search request (user clicked Match button)
        $isSearchRequest = $request->has('perform_match') || $request->input('perform_match') === 'true';

        Log::info('Bank Matching Index Request', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_search_request' => $isSearchRequest,
            'perform_match' => $request->input('perform_match'),
            'tab' => $request->input('tab'),
            'all_params' => $request->all()
        ]);

        // Only perform matching if user explicitly requested it
        if ($isSearchRequest) {
            Log::info('Performing bank matching...');

            // Test koneksi database gjtrading3
            try {
                $gjtrading3Connection = DB::connection('gjtrading3')->getPdo();
                Log::info('GJTRADING3 database connection successful');
            } catch (\Exception $e) {
                Log::error('GJTRADING3 database connection failed', ['error' => $e->getMessage()]);
                return Inertia::render('bank-matching/Index', [
                    'matchingResults' => [],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'error' => 'Koneksi database GJTRADING3 gagal: ' . $e->getMessage()
                ]);
            }

            // Execute bank matching logic directly without caching for debugging
            Log::info('Executing bank matching logic', ['start_date' => $startDate, 'end_date' => $endDate]);

            // Ambil data dari v_sj_new view dari database gjtrading3
            $sjNewList = SjNew::byDateRange($startDate, $endDate)
                ->orderBy('date')
                ->orderBy('doc_number')
                ->get();

            Log::info('SjNew data retrieved', ['count' => $sjNewList->count()]);

            // Ambil data bank masuk dari database sefti
            $bankMasukList = BankMasuk::where('status', 'aktif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();

            Log::info('BankMasuk data retrieved', ['count' => $bankMasukList->count()]);

            // Lakukan matching berdasarkan rules
            $matchingResults = $this->performBankMatching($sjNewList, $bankMasukList);

            Log::info('Bank matching completed', ['results_count' => count($matchingResults)]);
        } else {
            Log::info('No explicit search request, returning empty results');
            // Return empty results if no explicit search request
            $matchingResults = [];
        }

        Log::info('Returning bank matching response', [
            'matching_results_count' => count($matchingResults),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'tab' => $request->input('tab', 'auto-matching'),
            ]
        ]);

        return Inertia::render('bank-matching/Index', [
            'matchingResults' => $matchingResults,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'tab' => $request->input('tab', 'auto-matching'),
            ],
        ]);
    }

    public function performBankMatching($sjNewList, $bankMasukList)
    {
        $results = [];
        $usedBankMasukIds = [];

        Log::info('Starting performBankMatching', [
            'total_sj_new' => $sjNewList->count(),
            'total_bank_masuk' => $bankMasukList->count()
        ]);

        // Get already matched data from auto_matches table (database sefti) - for reference only
        $alreadyMatchedSjNos = AutoMatch::pluck('sj_no')->toArray();
        $alreadyMatchedBankMasukIds = AutoMatch::pluck('bank_masuk_id')->toArray();

        Log::info('Already matched data', [
            'already_matched_sj_count' => count($alreadyMatchedSjNos),
            'already_matched_bm_count' => count($alreadyMatchedBankMasukIds),
            'sample_already_matched_sj' => array_slice($alreadyMatchedSjNos, 0, 5),
            'sample_already_matched_bm_ids' => array_slice($alreadyMatchedBankMasukIds, 0, 5)
        ]);

        // Filter out already matched SJ data
        $availableSjNewList = collect($sjNewList)->filter(function($sjNew) use ($alreadyMatchedSjNos) {
            return !in_array($sjNew->getDocNumber(), $alreadyMatchedSjNos);
        });

        // Filter out already matched Bank Masuk data
        $availableBankMasukList = collect($bankMasukList)->filter(function($bankMasuk) use ($alreadyMatchedBankMasukIds) {
            return !in_array($bankMasuk->id, $alreadyMatchedBankMasukIds);
        });

        Log::info('Available data after filtering', [
            'available_sj_count' => $availableSjNewList->count(),
            'available_bm_count' => $availableBankMasukList->count(),
            'sample_available_sj' => $availableSjNewList->take(3)->map(function($item) {
                return [
                    'doc_number' => $item->getDocNumber(),
                    'date' => $item->getDateValue(),
                    'total' => $item->getTotalValue()
                ];
            })->toArray(),
            'sample_available_bm' => $availableBankMasukList->take(3)->map(function($item) {
                return [
                    'id' => $item->id,
                    'no_bm' => $item->no_bm,
                    'tanggal' => $item->tanggal,
                    'nilai' => $item->nilai
                ];
            })->toArray()
        ]);

        // Group sj_new dan bank masuk berdasarkan tanggal dan nilai
        $sjNewByDateValue = [];
        $bankMasukByDateValue = [];

        foreach ($availableSjNewList as $sjNew) {
            $tanggal = $sjNew->getDateValue();
            $tanggalFormatted = $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : '';
            $totalValue = (float) $sjNew->getTotalValue();
            // Format value to exact precision - preserve original decimal places
            $formattedValue = (string) $totalValue;
            $key = $tanggalFormatted . '_' . $formattedValue;
            if (!isset($sjNewByDateValue[$key])) {
                $sjNewByDateValue[$key] = [];
            }
            $sjNewByDateValue[$key][] = $sjNew;
        }

        foreach ($availableBankMasukList as $bankMasuk) {
            $value = (float) $bankMasuk->nilai;
            // Format value to exact precision - preserve original decimal places
            $formattedValue = (string) $value;
            $key = Carbon::parse($bankMasuk->tanggal)->format('Y-m-d') . '_' . $formattedValue;
            if (!isset($bankMasukByDateValue[$key])) {
                $bankMasukByDateValue[$key] = [];
            }
            $bankMasukByDateValue[$key][] = $bankMasuk;
        }

        Log::info('Grouped data', [
            'sj_groups_count' => count($sjNewByDateValue),
            'bm_groups_count' => count($bankMasukByDateValue),
            'sample_sj_groups' => array_slice(array_keys($sjNewByDateValue), 0, 5),
            'sample_bm_groups' => array_slice(array_keys($bankMasukByDateValue), 0, 5)
        ]);

        // Debug: Show all keys to see if they match
        Log::info('All SJ keys:', array_keys($sjNewByDateValue));
        Log::info('All BM keys:', array_keys($bankMasukByDateValue));

        // Lakukan matching berdasarkan tanggal dan nilai yang sama (exact match)
        foreach ($sjNewByDateValue as $dateValueKey => $sjNewGroup) {
            if (isset($bankMasukByDateValue[$dateValueKey])) {
                $bankMasukGroup = $bankMasukByDateValue[$dateValueKey];

                Log::info('Found matching group', [
                    'date_value_key' => $dateValueKey,
                    'sj_count' => count($sjNewGroup),
                    'bm_count' => count($bankMasukGroup)
                ]);

                // Sort berdasarkan urutan waktu pembuatan
                $sortedSjNew = collect($sjNewGroup)->sortBy(function($item) {
                    return $item->getKey();
                });
                $sortedBankMasuk = collect($bankMasukGroup)->sortBy('created_at');

                // Match berdasarkan aturan 1 SJ = 1 BM
                // Gunakan jumlah yang lebih kecil untuk memastikan 1:1 matching
                $minCount = min(count($sjNewGroup), count($bankMasukGroup));

                for ($i = 0; $i < $minCount; $i++) {
                    $sjNew = $sjNewGroup[$i];
                    $bankMasuk = $bankMasukGroup[$i];

                    // Pastikan bank masuk belum digunakan
                    if (!in_array($bankMasuk->id, $usedBankMasukIds)) {

                        $results[] = [
                            'no_invoice' => $sjNew->getDocNumber(),
                            'tanggal_invoice' => $sjNew->getDateValue() ? Carbon::parse($sjNew->getDateValue())->format('Y-m-d') : null,
                            'nilai_invoice' => (float) $sjNew->getTotalValue(),
                            'customer_name' => $sjNew->getCustomerName(),
                            'kontrabon' => $sjNew->getKontrabonValue(),
                            'currency' => $sjNew->getCurrency(),
                            'no_bank_masuk' => $bankMasuk->no_bm,
                            'tanggal_bank_masuk' => $bankMasuk->tanggal ? Carbon::parse($bankMasuk->tanggal)->format('Y-m-d') : null,
                            'nilai_bank_masuk' => (float) $bankMasuk->nilai,
                            'is_matched' => true,
                            'sj_no' => $sjNew->getDocNumber(), // Surat Jalan number
                            'bank_masuk_id' => (int) $bankMasuk->id,
                        ];

                        $usedBankMasukIds[] = $bankMasuk->id;
                    }
                }
            } else {
                Log::info('No matching BM group found for SJ key', ['sj_key' => $dateValueKey]);
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
        // Add detailed logging to see if request reaches here
        Log::info('=== BANK MATCHING STORE METHOD CALLED ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Request URL: ' . $request->url());
        Log::info('Request headers: ' . json_encode($request->headers->all()));

        // Add debugging to see what data is being received
        Log::info('Bank Matching Store Request', [
            'request_data' => $request->all(),
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
                'matches.*.no_bank_masuk' => 'required|string|max:255',
                'matches.*.tanggal_bank_masuk' => 'required|date_format:Y-m-d',
                'matches.*.nilai_bank_masuk' => 'required|numeric|min:0',
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
                'matches.*.no_bank_masuk.required' => 'No bank masuk wajib diisi.',
                'matches.*.no_bank_masuk.string' => 'No bank masuk harus berupa string.',
                'matches.*.tanggal_bank_masuk.required' => 'Tanggal bank masuk wajib diisi.',
                'matches.*.tanggal_bank_masuk.date_format' => 'Format tanggal bank masuk tidak valid.',
                'matches.*.nilai_bank_masuk.required' => 'Nilai bank masuk wajib diisi.',
                'matches.*.nilai_bank_masuk.numeric' => 'Nilai bank masuk harus berupa angka.',
                'matches.*.nilai_bank_masuk.min' => 'Nilai bank masuk minimal 0.',
            ]);

            Log::info('Validation passed successfully', [
                'validated_count' => count($validated['matches'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            throw $e;
        }

        DB::beginTransaction();
        try {
            foreach ($validated['matches'] as $match) {
                // Log the data being inserted
                Log::info('Creating AutoMatch record', $match);

                // Check if bank_masuk_id exists
                $bankMasuk = BankMasuk::find($match['bank_masuk_id']);
                if (!$bankMasuk) {
                    Log::error('Bank Masuk not found', ['bank_masuk_id' => $match['bank_masuk_id']]);
                    throw new \Exception("Bank Masuk dengan ID {$match['bank_masuk_id']} tidak ditemukan.");
                }

                // Test if we can create the record
                try {
                    $autoMatch = AutoMatch::create([
                        'bank_masuk_id' => $match['bank_masuk_id'],
                        'sj_no' => $match['no_invoice'],
                        'sj_tanggal' => $match['tanggal_invoice'],
                        'sj_nilai' => $match['nilai_invoice'],
                        'bm_no' => $match['no_bank_masuk'],
                        'bm_tanggal' => $match['tanggal_bank_masuk'],
                        'bm_nilai' => $match['nilai_bank_masuk'],
                        'match_date' => now(),
                        'status' => 'confirmed',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    Log::info('AutoMatch created successfully', ['id' => $autoMatch->id]);
                } catch (\Exception $createError) {
                    Log::error('Failed to create AutoMatch record', [
                        'error' => $createError->getMessage(),
                        'data' => $match
                    ]);
                    throw $createError;
                }
            }

            DB::commit();

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

            Log::info('Bank Matching Export Started', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Ambil data dari v_sj_new view dari database gjtrading3
            $sjNewList = SjNew::byDateRange($startDate, $endDate)
                ->orderBy('date')
                ->orderBy('doc_number')
                ->get();

            Log::info('SjNew data retrieved for export', [
                'count' => $sjNewList->count(),
                'sample_data' => $sjNewList->take(3)->map(function($item) {
                    return [
                        'doc_number' => $item->getDocNumber(),
                        'date' => $item->getDateValue(),
                        'total' => $item->getTotalValue(),
                        'name' => $item->getCustomerName(),
                    ];
                })->toArray(),
            ]);

            // Ambil data bank masuk dari database sefti
            $bankMasukList = BankMasuk::where('status', 'aktif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();

            Log::info('BankMasuk data retrieved for export', [
                'count' => $bankMasukList->count(),
                'sample_data' => $bankMasukList->take(3)->map(function($item) {
                    return [
                        'id' => $item->id,
                        'no_bm' => $item->no_bm,
                        'tanggal' => $item->tanggal,
                        'nilai' => $item->nilai,
                    ];
                })->toArray(),
            ]);

            // Get already matched data from auto_matches table
            $alreadyMatchedSjNos = AutoMatch::pluck('sj_no')->toArray();

            // Filter hanya data yang belum dimatch (invoice yang belum memiliki bank masuk)
            $unmatchedSjNewData = collect($sjNewList)->filter(function($sjNew) use ($alreadyMatchedSjNos) {
                return !in_array($sjNew->getDocNumber(), $alreadyMatchedSjNos);
            })->map(function($sjNew) {
                return [
                    'no_invoice' => $sjNew->getDocNumber(),
                    'tanggal_invoice' => $sjNew->getDateValue() ? Carbon::parse($sjNew->getDateValue())->format('Y-m-d') : null,
                    'nilai_invoice' => (float) $sjNew->getTotalValue(),
                    'customer_name' => $sjNew->getCustomerName(),
                    'kontrabon' => $sjNew->getKontrabonValue(),
                    'currency' => $sjNew->getCurrency(),
                    'status_match' => 'Belum Dimatch'
                ];
            });

            Log::info('Export data prepared', [
                'total_sj_new_records' => $sjNewList->count(),
                'already_matched_records' => count($alreadyMatchedSjNos),
                'unmatched_records' => $unmatchedSjNewData->count(),
                'sample_export_data' => $unmatchedSjNewData->take(3)->toArray(),
            ]);

            $filename = "bank_matching_unmatched_invoices_{$startDate}_{$endDate}.xlsx";

            Log::info('Export file created successfully', [
                'filename' => $filename,
                'record_count' => $unmatchedSjNewData->count()
            ]);

            return Excel::download(new BankMatchingExport($unmatchedSjNewData), $filename);
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

            // Ambil data dari v_sj_new view dari database gjtrading3
            $sjNewList = SjNew::byDateRange($startDate, $endDate)
                ->orderBy('date')
                ->orderBy('doc_number')
                ->get();

            // Ambil data bank masuk dari database sefti
            $bankMasukList = BankMasuk::where('status', 'aktif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();

            // Get already matched data from auto_matches table
            $alreadyMatchedSjNos = AutoMatch::pluck('sj_no')->toArray();

            // Filter hanya data yang belum dimatch (invoice yang belum memiliki bank masuk)
            $unmatchedInvoices = collect($sjNewList)->filter(function($sjNew) use ($alreadyMatchedSjNos) {
                return !in_array($sjNew->getDocNumber(), $alreadyMatchedSjNos);
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

            Log::info('Get Matched Data - Starting request', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'per_page' => $perPage
            ]);

            $query = AutoMatch::query()
                ->whereBetween('match_date', [$startDate, $endDate]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('sj_no', 'like', "%$search%")
                      ->orWhere('bm_no', 'like', "%$search%");
                });
            }

            // Sort by match_date descending (newest first), then by created_at descending
            $matchedData = $query->orderBy('match_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            Log::info('Matched data retrieved successfully', [
                'total' => $matchedData->total(),
                'current_page' => $matchedData->currentPage(),
                'per_page' => $matchedData->perPage(),
                'last_page' => $matchedData->lastPage(),
                'sample_data' => $matchedData->items() ? array_slice($matchedData->items(), 0, 3) : []
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

            Log::info('Get All Invoices - Starting request', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'per_page' => $perPage
            ]);

            // Test koneksi database gjtrading3
            $gjtrading3Available = false;
            try {
                Log::info('Testing GJTRADING3 connection...');
                $connection = DB::connection('gjtrading3');
                $pdo = $connection->getPdo();
                Log::info('GJTRADING3 Database connection successful');
                $gjtrading3Available = true;
            } catch (\Exception $e) {
                Log::error('GJTRADING3 Database connection failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't return error immediately, try to continue with fallback
            }

            // Ambil data dari v_sj_new view dari database gjtrading3
            $sjNewList = collect([]); // Default empty collection
            if ($gjtrading3Available) {
                try {
                    Log::info('Querying SjNew data...');

                    // Gunakan raw query untuk menghindari prepared statement issues
                    $query = "SELECT * FROM v_sj_new WHERE date BETWEEN ? AND ? ORDER BY date DESC, doc_number";
                    $sjNewRaw = DB::connection('gjtrading3')->select($query, [$startDate, $endDate]);

                    // Convert raw results to collection
                    $sjNewList = collect($sjNewRaw);

                    Log::info('SjNew data retrieved successfully', [
                        'count' => $sjNewList->count()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to retrieve SjNew data from GJTRADING3', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with empty collection
                }
            }

            // Get already matched data from auto_matches table (database sefti)
            $alreadyMatchedSjNos = [];
            try {
                Log::info('Querying AutoMatch data...');
                $alreadyMatchedSjNos = AutoMatch::pluck('sj_no')->toArray();

                Log::info('AutoMatch data retrieved successfully', [
                    'count' => count($alreadyMatchedSjNos)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve matched data from SEFTI', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with empty array if we can't get matched data
            }

            // Transform data dan tambahkan status matched
            Log::info('Transforming data...');
            $invoiceData = collect($sjNewList)->map(function($item) use ($alreadyMatchedSjNos) {
                return [
                    'doc_number' => $item->doc_number ?? null,
                    'date' => $item->date ? Carbon::parse($item->date)->format('Y-m-d') : null,
                    'total' => (float) ($item->total ?? 0),
                    'name' => $item->name ?? null,
                    'kontrabon' => $item->kontrabon ?? null,
                    'currency' => $item->currency ?? null,
                    'is_matched' => in_array($item->doc_number ?? '', $alreadyMatchedSjNos)
                ];
            });

            // Apply search filter
            if ($search) {
                Log::info('Applying search filter...');
                $invoiceData = $invoiceData->filter(function($invoice) use ($search) {
                    return str_contains(strtolower($invoice['doc_number'] ?? ''), strtolower($search)) ||
                           str_contains(strtolower($invoice['name'] ?? ''), strtolower($search));
                });
            }

            // Manual pagination
            Log::info('Applying pagination...');
            $total = $invoiceData->count();
            $currentPage = (int) $request->query('page', 1);
            $perPage = (int) $perPage;
            $offset = ($currentPage - 1) * $perPage;

            Log::info('Pagination details', [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'offset' => $offset,
                'request_page' => $request->query('page')
            ]);

            $paginatedData = $invoiceData->slice($offset, $perPage)->values();
            $lastPage = ceil($total / $perPage);

            Log::info('All Invoices response prepared successfully', [
                'total_records' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'matched_count' => $invoiceData->where('is_matched', true)->count(),
                'unmatched_count' => $invoiceData->where('is_matched', false)->count(),
                'gjtrading3_available' => $gjtrading3Available
            ]);

            return response()->json([
                'data' => $paginatedData,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
                'gjtrading3_available' => $gjtrading3Available,
                'message' => $gjtrading3Available ? null : 'Database GJTRADING3 tidak tersedia, menampilkan data kosong'
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
            $gjtrading3Connection = DB::connection('gjtrading3')->getPdo();

            // Test SjNew connection
            $sjNewList = SjNew::byDateRange($startDate, $endDate)
                ->orderBy('date')
                ->orderBy('doc_number')
                ->get();

            // Test BankMasuk connection
            $bankMasukList = BankMasuk::where('status', 'aktif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();

            // Test matching
            $matchingResults = $this->performBankMatching($sjNewList, $bankMasukList);

            return response()->json([
                'success' => true,
                'connections' => [
                    'sefti' => 'Connected',
                    'gjtrading3' => 'Connected',
                ],
                'sj_new_count' => $sjNewList->count(),
                'bank_masuk_count' => $bankMasukList->count(),
                'matching_results_count' => count($matchingResults),
                'sample_sj_new' => $sjNewList->take(3)->map(function($item) {
                    return [
                        'id' => $item->getKey(),
                        'doc_number' => $item->getDocNumber(),
                        'date' => $item->getDateValue(),
                        'total' => $item->getTotalValue(),
                    ];
                }),
                'sample_bank_masuk' => $bankMasukList->take(3)->map(function($item) {
                    return [
                        'id' => $item->id,
                        'no_bm' => $item->no_bm,
                        'tanggal' => $item->tanggal,
                        'nilai' => $item->nilai,
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

            // Test GJTRADING3 database
            try {
                $gjtrading3Connection = DB::connection('gjtrading3')->getPdo();
                Log::info('GJTRADING3 Database connection successful');
                $gjtrading3Status = 'Connected';
            } catch (\Exception $e) {
                Log::error('GJTRADING3 Database connection failed', [
                    'error' => $e->getMessage()
                ]);
                $gjtrading3Status = 'Failed: ' . $e->getMessage();
            }

            // Test SjNew model
            try {
                $sjNewCount = SjNew::count();
                Log::info('SjNew model test successful', ['count' => $sjNewCount]);
                $sjNewStatus = 'Connected - Count: ' . $sjNewCount;
            } catch (\Exception $e) {
                Log::error('SjNew model test failed', [
                    'error' => $e->getMessage()
                ]);
                $sjNewStatus = 'Failed: ' . $e->getMessage();
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
                'gjtrading3_database' => $gjtrading3Status,
                'sj_new_model' => $sjNewStatus,
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

            // Test 1: Basic response
            Log::info('Test 1: Basic response');

            // Test 2: Database connection
            Log::info('Test 2: Testing database connections');
            $results = [];

            try {
                $seftiConnection = DB::connection()->getPdo();
                $results['sefti'] = 'Connected';
                Log::info('SEFTI database connected');
            } catch (\Exception $e) {
                $results['sefti'] = 'Failed: ' . $e->getMessage();
                Log::error('SEFTI database failed: ' . $e->getMessage());
            }

            try {
                $gjtrading3Connection = DB::connection('gjtrading3')->getPdo();
                $results['gjtrading3'] = 'Connected';
                Log::info('GJTRADING3 database connected');
            } catch (\Exception $e) {
                $results['gjtrading3'] = 'Failed: ' . $e->getMessage();
                Log::error('GJTRADING3 database failed: ' . $e->getMessage());
            }

            // Test 3: Model queries
            Log::info('Test 3: Testing model queries');

            try {
                $autoMatchCount = AutoMatch::count();
                $results['auto_match_count'] = $autoMatchCount;
                Log::info('AutoMatch count: ' . $autoMatchCount);
            } catch (\Exception $e) {
                $results['auto_match_count'] = 'Failed: ' . $e->getMessage();
                Log::error('AutoMatch query failed: ' . $e->getMessage());
            }

            try {
                $sjNewCount = SjNew::count();
                $results['sj_new_count'] = $sjNewCount;
                Log::info('SjNew count: ' . $sjNewCount);
            } catch (\Exception $e) {
                $results['sj_new_count'] = 'Failed: ' . $e->getMessage();
                Log::error('SjNew query failed: ' . $e->getMessage());
            }

            Log::info('Simple test completed successfully');

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
