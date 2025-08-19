<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BankLog;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Bank::query();

        // Search functionality - sesuai dengan yang digunakan di frontend
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $tanggalDb = $this->parseIndonesianDate($searchTerm);
            $bulanIndo = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];
            $query->where(function($q) use ($searchTerm, $tanggalDb, $bulanIndo) {
                $q->where('nama_bank', 'like', "%$searchTerm%")
                  ->orWhere('kode_bank', 'like', "%$searchTerm%")
                  ->orWhere('singkatan', 'like', "%$searchTerm%")
                  ->orWhere('id', 'like', "%$searchTerm%")
                  ->orWhere('status', 'like', "%$searchTerm%")
                  ;
                if ($tanggalDb) {
                    $q->orWhereDate('created_at', $tanggalDb)
                      ->orWhereDate('updated_at', $tanggalDb);
                }
                // Search by year
                $q->orWhereRaw("DATE_FORMAT(created_at, '%Y') LIKE ?", ["%$searchTerm%"])
                  ->orWhereRaw("DATE_FORMAT(updated_at, '%Y') LIKE ?", ["%$searchTerm%"]);
                // Search by day
                $q->orWhereRaw("DATE_FORMAT(created_at, '%d') LIKE ?", ["%$searchTerm%"])
                  ->orWhereRaw("DATE_FORMAT(updated_at, '%d') LIKE ?", ["%$searchTerm%"]);
                // Search by Indonesian month name
                foreach ($bulanIndo as $num => $nama) {
                    if (stripos($nama, $searchTerm) !== false) {
                        $q->orWhereRaw("DATE_FORMAT(created_at, '%m') = ?", [$num])
                          ->orWhereRaw("DATE_FORMAT(updated_at, '%m') = ?", [$num]);
                    }
                }
            });
        }

        // Filter by status jika diperlukan
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pagination dengan per_page yang bisa diatur
        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $banks = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('banks/Index', [
            'Banks' => $banks, // Sesuai dengan props di Vue component
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankRequest $request)
    {
        try {
            $bank = Bank::create($request->validated());
            // Log activity
            BankLog::create([
                'bank_id' => $bank->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => 'Bank dibuat',
                'ip_address' => $request->ip(),
            ]);

            // Clear related caches to ensure fresh data
            $this->clearBankCaches();

            // Redirect kembali ke index dengan pesan sukses
            return redirect()->route('banks.index')
                           ->with('success', 'Data bank berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menyimpan data bank: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bank = Bank::findOrFail($id);

        return Inertia::render('banks/Show', [
            'bank' => $bank
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankRequest $request, $id)
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->update($request->validated());
            // Log activity
            BankLog::create([
                'bank_id' => $bank->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Bank diupdate',
                'ip_address' => $request->ip(),
            ]);

            // Clear related caches to ensure fresh data
            $this->clearBankCaches();

            return redirect()->route('banks.index')
                ->with('success', 'Data bank berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data bank: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bank = Bank::findOrFail($id);
        try {
            $bank->delete();
            // Log activity
            BankLog::create([
                'bank_id' => $bank->id,
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'description' => 'Bank dihapus',
                'ip_address' => request()->ip(),
            ]);

            // Clear related caches to ensure fresh data
            $this->clearBankCaches();

            return redirect()->route('banks.index')
                           ->with('success', 'Data bank berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus bank: ' . $e->getMessage());
        }
    }

    /**
     * Toggle bank status (Active/Non-Active).
     */
        public function toggleStatus($id)
    {
        $bank = Bank::findOrFail($id);
        $bank->status = $bank->status === 'active' ? 'inactive' : 'active';
        $bank->save();

        // Clear related caches to ensure fresh data
        $this->clearBankCaches();

        return redirect()->route('banks.index')
                       ->with('success', 'Status bank berhasil diperbarui');
    }

    /**
     * Show log activity for specific bank.
     */
    public function logs(Bank $bank, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $bank = \App\Models\Bank::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($bank->id);

        $logs = BankLog::with(['user.department', 'user.role'])
            ->where('bank_id', $bank->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = BankLog::where('bank_id', $bank->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('banks/Log', [
            'bank' => $bank,
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }

    /**
     * Helper: Konversi tanggal format Indonesia (15 Juli 2025) ke YYYY-MM-DD
     */
    private function parseIndonesianDate($input)
    {
        $bulan = [
            'Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04',
            'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08',
            'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12'
        ];
        if (preg_match('/^([0-9]{1,2})\s([A-Za-z]+)\s([0-9]{4})$/u', trim($input), $m)) {
            $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $b = $bulan[$m[2]] ?? null;
            $y = $m[3];
            if ($b) return "$y-$b-$d";
        }
        return null;
    }

    /**
     * Clear all bank-related caches
     */
    private function clearBankCaches()
    {
        // Clear all bank-related cache keys
        cache()->forget('banks_active_accounts');
        cache()->forget('banks_all');
        cache()->forget('banks_active');

        // Also clear bank account caches since they depend on banks
        cache()->forget('bank_accounts_active');
    }
}
