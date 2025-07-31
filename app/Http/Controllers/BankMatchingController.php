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

        // Only perform matching if user explicitly requested it
        if ($isSearchRequest) {
            // Ambil data dari v_sj_new view dari database gjtrading3
            $sjNewList = SjNew::byDateRange($startDate, $endDate)
                ->orderBy('date')
                ->orderBy('doc_number')
                ->get();

            // Debug: Log the data retrieved
            Log::info('SjNew data retrieved', [
                'count' => $sjNewList->count(),
                'sample_data' => $sjNewList->take(3)->map(function($item) {
                    return [
                        'id' => $item->getKey(),
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

            // Debug: Log the bank masuk data retrieved
            Log::info('BankMasuk data retrieved', [
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

            // Get already matched data for logging
            $alreadyMatchedSjNos = AutoMatch::pluck('sj_no')->toArray();
            $alreadyMatchedBankMasukIds = AutoMatch::pluck('bank_masuk_id')->toArray();

            // Lakukan matching berdasarkan rules
            $matchingResults = $this->performBankMatching($sjNewList, $bankMasukList);

            // Debug: Log the final matching results
            Log::info('Final matching results', [
                'count' => count($matchingResults),
                'sample_results' => array_slice($matchingResults, 0, 3),
                'all_results' => $matchingResults, // Log all results for debugging
            ]);

            // Debug: Log jumlah data untuk memastikan data diambil dengan benar
            Log::info('Bank Matching Data Count', [
                'sjNewCount' => $sjNewList->count(),
                'bankMasukCount' => $bankMasukList->count(),
                'alreadyMatchedSjCount' => count($alreadyMatchedSjNos),
                'alreadyMatchedBankMasukCount' => count($alreadyMatchedBankMasukIds),
                'matchingResultsCount' => count($matchingResults),
                'matchedCount' => collect($matchingResults)->where('is_matched', true)->count(),
                'unmatchedCount' => collect($matchingResults)->where('is_matched', false)->count()
            ]);
        } else {
            // Return empty results if no explicit search request
            $matchingResults = [];
        }

        return Inertia::render('bank-matching/Index', [
            'matchingResults' => $matchingResults,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function performBankMatching($sjNewList, $bankMasukList)
    {
        $results = [];
        $usedSjNewIds = [];
        $usedBankMasukIds = [];

        // Get already matched data from auto_matches table
        $alreadyMatchedSjNos = AutoMatch::pluck('sj_no')->toArray();
        $alreadyMatchedBankMasukIds = AutoMatch::pluck('bank_masuk_id')->toArray();

        // Filter out already matched SJ data
        $availableSjNewList = collect($sjNewList)->filter(function($sjNew) use ($alreadyMatchedSjNos) {
            return !in_array($sjNew->getDocNumber(), $alreadyMatchedSjNos);
        });

        // Filter out already matched Bank Masuk data
        $availableBankMasukList = collect($bankMasukList)->filter(function($bankMasuk) use ($alreadyMatchedBankMasukIds) {
            return !in_array($bankMasuk->id, $alreadyMatchedBankMasukIds);
        });

        // Group sj_new dan bank masuk berdasarkan tanggal dan nilai
        $sjNewByDateValue = [];
        $bankMasukByDateValue = [];

        foreach ($availableSjNewList as $sjNew) {
            $tanggal = $sjNew->getDateValue();
            $tanggalFormatted = $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : '';
            $key = $tanggalFormatted . '_' . $sjNew->getTotalValue();
            if (!isset($sjNewByDateValue[$key])) {
                $sjNewByDateValue[$key] = [];
            }
            $sjNewByDateValue[$key][] = $sjNew;
        }

        foreach ($availableBankMasukList as $bankMasuk) {
            $key = $bankMasuk->tanggal . '_' . $bankMasuk->nilai;
            if (!isset($bankMasukByDateValue[$key])) {
                $bankMasukByDateValue[$key] = [];
            }
            $bankMasukByDateValue[$key][] = $bankMasuk;
        }

        // Lakukan matching berdasarkan tanggal dan nilai yang sama
        foreach ($sjNewByDateValue as $dateValueKey => $sjNewGroup) {
            if (isset($bankMasukByDateValue[$dateValueKey])) {
                $bankMasukGroup = $bankMasukByDateValue[$dateValueKey];

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

                        // Debug log
                        Log::info('Generated match result', [
                            'sj_no' => $sjNew->getDocNumber(),
                            'bank_masuk_id' => $bankMasuk->id,
                            'no_invoice' => $sjNew->getDocNumber(),
                            'nilai_invoice' => $sjNew->getTotalValue(),
                        ]);
                    }
                }
            }
        }

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
}
