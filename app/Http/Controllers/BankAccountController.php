<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\BankAccount;
use App\Models\Bank;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use App\Models\BankAccountLog;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::with(['bank', 'department']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $bulanIndo = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];
            $query->where(function($q) use ($searchTerm, $bulanIndo) {
                $q->where('no_rekening', 'like', "%$searchTerm%")
                  ->orWhere('id', 'like', "%$searchTerm%")
                  ->orWhere('status', 'like', "%$searchTerm%")
                  ->orWhereRaw("DATE_FORMAT(created_at, '%Y') LIKE ?", ["%$searchTerm%"])
                  ->orWhereRaw("DATE_FORMAT(updated_at, '%Y') LIKE ?", ["%$searchTerm%"])
                  ->orWhereRaw("DATE_FORMAT(created_at, '%d') LIKE ?", ["%$searchTerm%"])
                  ->orWhereRaw("DATE_FORMAT(updated_at, '%d') LIKE ?", ["%$searchTerm%"]);
                // Search by Indonesian month name
                foreach ($bulanIndo as $num => $nama) {
                    if (stripos($nama, $searchTerm) !== false) {
                        $q->orWhereRaw("DATE_FORMAT(created_at, '%m') = ?", [$num])
                          ->orWhereRaw("DATE_FORMAT(updated_at, '%m') = ?", [$num]);
                    }
                }
                // Search by nama_bank (relasi)
                $q->orWhereHas('bank', function($b) use ($searchTerm) {
                    $b->where('nama_bank', 'like', "%$searchTerm%")
                      ->orWhere('singkatan', 'like', "%$searchTerm%")
                      ->orWhere('id', 'like', "%$searchTerm%")
                      ->orWhere('status', 'like', "%$searchTerm%")
                      ;
                });
                // Search by department name (relasi)
                $q->orWhereHas('department', function($d) use ($searchTerm) {
                    $d->where('name', 'like', "%$searchTerm%")
                      ->orWhere('id', 'like', "%$searchTerm%")
                      ->orWhere('status', 'like', "%$searchTerm%")
                      ;
                });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by bank_id
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by department_id
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $bankAccounts = $query->orderByDesc('created_at')->paginate($perPage);

        // Cache banks data for better performance
        $banks = cache()->remember('banks_active_accounts', 3600, function() {
            return Bank::active()->get(['id', 'nama_bank', 'singkatan', 'status']);
        });

        // Get department options based on user permissions
        $departments = DepartmentService::getOptionsForFilter();

        return Inertia::render('bank-accounts/Index', [
            'bankAccounts' => $bankAccounts,
            'banks' => $banks,
            'departments' => $departments,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'bank_id' => $request->bank_id,
                'department_id' => $request->department_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreBankAccountRequest $request)
    {
        $validated = $request->validated();
        $bankAccount = BankAccount::create($validated);
        // Log activity
        BankAccountLog::create([
            'bank_account_id' => $bankAccount->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Menambahkan data Bank Account',
            'ip_address' => $request->ip(),
        ]);
        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Data Bank Account berhasil ditambahkan');
    }

    public function show($id)
    {
        $bankAccount = BankAccount::with(['bank', 'department'])->findOrFail($id);
        return Inertia::render('bank-accounts/Show', [
            'bankAccount' => $bankAccount
        ]);
    }

    public function update(UpdateBankAccountRequest $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        $validated = $request->validated();
        $bankAccount->update($validated);
        // Log activity
        BankAccountLog::create([
            'bank_account_id' => $bankAccount->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => 'Mengubah data Bank Account',
            'ip_address' => $request->ip(),
        ]);
        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Data Bank Account berhasil diperbarui');
    }

    public function destroy($id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        // Log activity
        BankAccountLog::create([
            'bank_account_id' => $bankAccount->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Menghapus data Bank Account',
            'ip_address' => request()->ip(),
        ]);
        $bankAccount->delete(); // Ini sekarang akan soft delete
        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Data Bank Account berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->status = $bankAccount->status === 'active' ? 'inactive' : 'active';
        $bankAccount->save();

        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Status Bank Account berhasil diperbarui');
    }


    /**
     * Force delete (permanently remove from database)
     */
    public function forceDelete($id)
    {
        $model = $this->getModelClass()::withTrashed()->findOrFail($id);

        try {
            $model->forceDelete();
            return redirect()->route($this->getRouteName() . '.index')
                           ->with('success', 'Data berhasil dihapus permanen');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus permanen data: ' . $e->getMessage());
        }
    }

    /**
     * Restore soft deleted record
     */
    public function restore($id)
    {
        $model = $this->getModelClass()::withTrashed()->findOrFail($id);

        try {
            $model->restore();
            return redirect()->route($this->getRouteName() . '.index')
                           ->with('success', 'Data berhasil dipulihkan');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal memulihkan data: ' . $e->getMessage());
        }
    }

    /**
     * Get model class name for soft delete operations
     */
    protected function getModelClass()
    {
        // Default implementation - override in child classes if needed
        $className = class_basename($this);
        $modelName = str_replace('Controller', '', $className);

        // Handle special cases
        $modelMap = [
            'ArPartnerController' => 'ArPartner',
            'BisnisPartnerController' => 'BisnisPartner',
            'BankController' => 'Bank',
            'BankAccountController' => 'BankAccount',
            'PengeluaranController' => 'Pengeluaran',
            'PphController' => 'Pph',
            'TerminController' => 'Termin',
            'PerihalController' => 'Perihal',
            'PurchaseOrderController' => 'PurchaseOrder',
            'MemoPembayaranController' => 'MemoPembayaran',
            'UserController' => 'User',
            'RoleController' => 'Role',
            'DepartmentController' => 'Department',
        ];

        return 'App\\Models\\' . ($modelMap[$className] ?? $modelName);
    }

    /**
     * Get route name for redirects
     */
    protected function getRouteName()
    {
        $className = class_basename($this);
        $routeName = str_replace('Controller', '', $className);

        // Convert to kebab case
        $routeName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $routeName));

        // Handle special cases
        $routeMap = [
            'ArPartnerController' => 'ar-partners',
            'BisnisPartnerController' => 'bisnis-partners',
            'BankController' => 'banks',
            'BankAccountController' => 'bank-accounts',
            'PengeluaranController' => 'pengeluarans',
            'PphController' => 'pphs',
            'TerminController' => 'termins',
            'PerihalController' => 'perihals',
            'PurchaseOrderController' => 'purchase-orders',
            'MemoPembayaranController' => 'memo-pembayarans',
            'UserController' => 'users',
            'RoleController' => 'roles',
            'DepartmentController' => 'departments',
        ];

        return $routeMap[$className] ?? $routeName;
    }

    public function logs(BankAccount $bank_account, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $bank_account = \App\Models\BankAccount::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($bank_account->id);

        $logs = BankAccountLog::with(['user.department', 'user.role'])
            ->where('bank_account_id', $bank_account->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = BankAccountLog::where('bank_account_id', $bank_account->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('bank-accounts/Log', [
            'bankAccount' => $bank_account->load(['bank', 'department']),
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }
}
