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
                    $subQuery->where('department_id', $departmentId);
                    if ($allDepartmentId) {
                        $subQuery->orWhere('department_id', $allDepartmentId);
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
                'bank',
                'creator',
                'updater',
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

        return Inertia::render('bank-keluar/Create', [
            'departments' => $departments,
            'suppliers' => $suppliers,
            'bisnisPartners' => $bisnisPartners,
            'banks' => $banks,
            'bankSupplierAccounts' => $bankSupplierAccounts,
            'creditCards' => $creditCards,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_bk' => 'required|in:Reguler,Anggaran,Lainnya',
            'department_id' => 'required|exists:departments,id',
            'nominal' => 'required|numeric|min:0.01',
            'metode_bayar' => 'required|string',
            'supplier_id' => 'nullable|required_if:tipe_bk,Reguler,Lainnya|exists:suppliers,id',
            'bisnis_partner_id' => 'nullable|required_if:tipe_bk,Anggaran|exists:bisnis_partners,id',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'nama_pemilik_rekening' => 'nullable|string|max:255',
            'no_rekening' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $department = Department::findOrFail($validated['department_id']);

            // Generate document number
            $no_bk = DocumentNumberService::generateNumber('Bank Keluar', $validated['tipe_bk'] ?? null, $department->id, $department->alias);

            $bankKeluar = BankKeluar::create([
                'no_bk' => $no_bk,
                'tanggal' => $validated['tanggal'],
                'tipe_bk' => $validated['tipe_bk'] ?? null,
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

            // Create log entry
            BankKeluarLog::create([
                'bank_keluar_id' => $bankKeluar->id,
                'user_id' => Auth::id(),
                'action' => 'create',
                'description' => 'Membuat Bank Keluar baru',
                'ip_address' => $request->ip(),
            ]);

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

        return Inertia::render('bank-keluar/Edit', [
            'bankKeluar' => $bankKeluar,
            'departments' => $departments,
            'suppliers' => $suppliers,
            'bisnisPartners' => $bisnisPartners,
            'banks' => $banks,
            'bankSupplierAccounts' => $bankSupplierAccounts,
            'creditCards' => $creditCards,
        ]);
    }

    public function update(Request $request, BankKeluar $bankKeluar)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_bk' => 'required|in:Reguler,Anggaran,Lainnya',
            'department_id' => 'required|exists:departments,id',
            'perihal_id' => 'nullable|exists:perihals,id',
            'nominal' => 'required|numeric|min:0.01',
            'metode_bayar' => 'required|string',
            'supplier_id' => 'nullable|required_if:tipe_bk,Reguler,Lainnya|exists:suppliers,id',
            'bisnis_partner_id' => 'nullable|required_if:tipe_bk,Anggaran|exists:bisnis_partners,id',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'nama_pemilik_rekening' => 'nullable|string|max:255',
            'no_rekening' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update Bank Keluar
            $bankKeluar->update([
                'tanggal' => $validated['tanggal'],
                'tipe_bk' => $validated['tipe_bk'],
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

            return redirect()->route('bank-keluar.index')->with('success', 'Bank Keluar berhasil dibatalkan.');
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
