<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\BankAccount;
use App\Models\Department;
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
            $allowedSorts = ['no_bm', 'purchase_order_id', 'tanggal', 'note', 'nilai', 'created_at'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $query->orderByDesc('created_at');
            }

            // Rows per page (support entriesPerPage dari frontend)
            $perPage = $request->input('per_page', $request->input('entriesPerPage', 10));

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

            $bankMasuks = $query->paginate($perPage)->withQueryString();

            // Cache bank accounts data for better performance
            $bankAccounts = cache()->remember('bank_accounts_active', 3600, function() {
                $accounts = BankAccount::with(['bank', 'department'])->where('status', 'active')->orderBy('no_rekening')->get();

                // Debug: Log each account's data
                foreach ($accounts as $acc) {
                    Log::info("Bank Account Debug - ID: {$acc->id}, Dept ID: {$acc->department_id}, Bank: " . ($acc->bank ? $acc->bank->singkatan : 'N/A') . ", Dept: " . ($acc->department ? $acc->department->name : 'N/A'));
                }

                return $accounts;
            });

            // Cache departments data for better performance
            $departments = cache()->remember('departments_active_bank_masuk', 3600, function() {
                return Department::where('status', 'active')->orderBy('name')->get(['id', 'name', 'status']);
            });

            // Debug logging
            Log::info('Bank Masuk Index - Data being sent to frontend', [
                'bankAccounts_count' => $bankAccounts->count(),
                'departments_count' => $departments->count(),
                'departments_data' => $departments->toArray(),
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
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'terima_dari' => 'required|in:Customer,Karyawan,Penjualan Toko,Lainnya',
            'nilai' => 'required|numeric|min:0',
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
            'bank_account_id.required' => 'Rekening wajib diisi',
            'bank_account_id.exists' => 'Rekening tidak valid',
            'department_id.required' => 'Departemen wajib diisi',
            'department_id.exists' => 'Departemen tidak valid',
            'ar_partner_id.exists' => 'AR Partner tidak valid',
        ]);

        // Generate no BM otomatis sesuai format baru
        $bankAccount = \App\Models\BankAccount::with('department')->find($validated['bank_account_id']);
        $departmentAlias = $bankAccount && $bankAccount->department ? $bankAccount->department->alias : 'XXX';
        $dt = \Carbon\Carbon::parse($validated['tanggal']);
        $bulanRomawi = $this->bulanRomawi($dt->format('n'));
        $tahun = $dt->format('Y');

        // Hitung nomor urut untuk departemen dan bulan-tahun tertentu
        $like = "BM/{$departmentAlias}/{$bulanRomawi}-{$tahun}/%";
        $count = \App\Models\BankMasuk::where('no_bm', 'like', $like)->count();
        $autoNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $validated['no_bm'] = "BM/{$departmentAlias}/{$bulanRomawi}-{$tahun}/{$autoNum}";
        $validated['status'] = 'aktif';
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $bankMasuk = BankMasuk::create($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'create',
            'description' => 'Membuat dokumen Bank Masuk',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil disimpan.');
    }

    public function show(BankMasuk $bankMasuk)
    {
        $bankMasuk->load(['bankAccount.bank', 'bankAccount.department', 'creator', 'updater', 'arPartner']);
        $bankAccounts = BankAccount::with(['bank', 'department'])->where('status', 'active')->orderBy('no_rekening')->get();
        $departments = Department::where('status', 'active')->orderBy('name')->get();
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
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'terima_dari' => 'required|in:Customer,Karyawan,Penjualan Toko,Lainnya',
            'nilai' => 'required|numeric|min:0',
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
            'bank_account_id.required' => 'Rekening wajib diisi',
            'bank_account_id.exists' => 'Rekening tidak valid',
            'department_id.required' => 'Departemen wajib diisi',
            'department_id.exists' => 'Departemen tidak valid',
            'ar_partner_id.exists' => 'AR Partner tidak valid',
        ]);

        // Explicitly remove no_bm from validated data
        unset($validated['no_bm']);

        // Check if bank_account_id or tanggal has changed
        $shouldRegenerateNoBm = $bankMasuk->bank_account_id != $validated['bank_account_id'] ||
                               $bankMasuk->tanggal != $validated['tanggal'];

        // Jangan update no_bm jika tidak ada perubahan
        if (!$shouldRegenerateNoBm) {
            unset($validated['no_bm']);
        } else {
            // Generate new no_bm
            $bankAccount = \App\Models\BankAccount::with('department')->find($validated['bank_account_id']);
            $namaBank = $bankAccount && $bankAccount->department ? $bankAccount->department->name : 'XXX';
            $dt = \Carbon\Carbon::parse($validated['tanggal']);
            $bulanRomawi = $this->bulanRomawi($dt->format('n'));
            $tahun = $dt->format('Y');

            // Jika hanya departemen yang berubah (tanggal sama), pertahankan nomor unik
            if ($bankMasuk->tanggal == $validated['tanggal'] && $bankMasuk->bank_account_id != $validated['bank_account_id']) {
                // Ekstrak nomor unik dari no_bm lama
                $oldNoBm = $bankMasuk->no_bm;
                if (preg_match('/\/(\d{4})$/', $oldNoBm, $matches)) {
                    $uniqueNumber = $matches[1]; // akan berisi 0001 (tanpa slash)
                    $validated['no_bm'] = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$uniqueNumber}";
                } else {
                    // Fallback jika format tidak sesuai, generate nomor baru
                    $like = "BM/%/{$bulanRomawi}-{$tahun}/%";
                    $count = \App\Models\BankMasuk::where('no_bm', 'like', $like)
                        ->where('id', '!=', $bankMasuk->id)
                        ->count();
                    $autoNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                    $validated['no_bm'] = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$autoNum}";
                }
            } else {
                // Cek apakah hanya tanggal yang berubah (bulan dan tahun sama)
                $originalDt = \Carbon\Carbon::parse($bankMasuk->tanggal);
                $originalBulanRomawi = $this->bulanRomawi($originalDt->format('n'));
                $originalTahun = $originalDt->format('Y');

                if ($bulanRomawi == $originalBulanRomawi && $tahun == $originalTahun) {
                    // Jika hanya tanggal yang berubah (bulan dan tahun sama), pertahankan nomor BM
                    $validated['no_bm'] = $bankMasuk->no_bm;
                } else {
                    // Jika bulan atau tahun berubah, generate nomor baru
                    $like = "BM/%/{$bulanRomawi}-{$tahun}/%";
                    $count = \App\Models\BankMasuk::where('no_bm', 'like', $like)
                        ->where('id', '!=', $bankMasuk->id)
                        ->count();
                    $autoNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                    $validated['no_bm'] = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$autoNum}";
                }
            }
        }

        $validated['updated_by'] = Auth::id();
        $bankMasuk->update($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'update',
            'description' => 'Mengupdate dokumen Bank Masuk' . ($shouldRegenerateNoBm ? ' (dengan perubahan nomor BM)' : ''),
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
            'action' => 'delete',
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

    public function log(BankMasuk $bankMasuk)
    {
        // Cek apakah tabel log sudah ada
        if (Schema::hasTable('bank_masuk_logs')) {
            $logs = DB::table('bank_masuk_logs')
                ->where('bank_masuk_id', $bankMasuk->id)
                ->leftJoin('users', 'bank_masuk_logs.user_id', '=', 'users.id')
                ->select('bank_masuk_logs.*', 'users.name as user_name')
                ->orderByDesc('bank_masuk_logs.created_at')
                ->get();
        } else {
            $logs = [];
        }
        return Inertia::render('bank-masuk/Log', [
            'bankMasukLog' => $logs,
            'bankMasuk' => $bankMasuk,
        ]);
    }

    public function getNextNumber(Request $request)
    {
        $bank_account_id = $request->query('bank_account_id');
        $tanggal = $request->query('tanggal');
        $exclude_id = $request->query('exclude_id'); // Untuk mode edit
        $current_no_bm = $request->query('current_no_bm'); // Nomor BM saat ini untuk mode edit
        $departmentAlias = 'XXX';
        if ($bank_account_id) {
            $bankAccount = \App\Models\BankAccount::with('department')->find($bank_account_id);
            if ($bankAccount && $bankAccount->department) {
                $departmentAlias = $bankAccount->department->alias ?? 'XXX';
            }
        }
        $bulanRomawi = '';
        $tahun = '';
        if ($tanggal) {
            $dt = \Carbon\Carbon::parse($tanggal);
            $bulanRomawi = $this->bulanRomawi($dt->format('n'));
            $tahun = $dt->format('Y');
        }

        // Jika dalam mode edit dan ada current_no_bm, cek apakah hanya departemen yang berubah
        if ($exclude_id && $current_no_bm) {
            // Ambil data asli untuk perbandingan
            $originalData = \App\Models\BankMasuk::find($exclude_id);
            if ($originalData) {
                $originalDt = \Carbon\Carbon::parse($originalData->tanggal);
                $originalBulanRomawi = $this->bulanRomawi($originalDt->format('n'));
                $originalTahun = $originalDt->format('Y');

                // Jika tanggal sama (hanya departemen yang berubah), pertahankan nomor unik
                if ($originalData->tanggal == $tanggal) {
                    // Ekstrak nomor unik dari current_no_bm
                    if (preg_match('/\/(\d{4})$/', $current_no_bm, $matches)) {
                        $uniqueNumber = $matches[1]; // akan berisi 0001 (tanpa slash)
                        $no_bm = "BM/{$departmentAlias}/{$bulanRomawi}-{$tahun}/{$uniqueNumber}";
                        return response()->json(['no_bm' => $no_bm]);
                    }
                }

                // Jika hanya tanggal yang berubah (bulan dan tahun sama), pertahankan nomor BM
                if ($bulanRomawi == $originalBulanRomawi && $originalTahun == $tahun) {
                    return response()->json(['no_bm' => $current_no_bm]);
                }
            }
        }

        // Generate nomor baru jika tanggal berubah atau tidak ada current_no_bm
        $like = "BM/{$departmentAlias}/{$bulanRomawi}-{$tahun}/%";
        $query = \App\Models\BankMasuk::where('no_bm', 'like', $like);

        // Exclude current record if editing
        if ($exclude_id) {
            $query->where('id', '!=', $exclude_id);
        }

        $count = $query->count();
        $autoNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $no_bm = "BM/{$departmentAlias}/{$bulanRomawi}-{$tahun}/{$autoNum}";
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
                        $cellValue = $dataRow->{$field} ?? '';
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
