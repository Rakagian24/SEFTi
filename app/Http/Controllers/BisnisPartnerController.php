<?php

namespace App\Http\Controllers;

use App\Models\BisnisPartner;
use App\Models\Bank;
use App\Http\Requests\StoreBisnisPartnerRequest;
use App\Http\Requests\UpdateBisnisPartnerRequest;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BisnisPartnerLog;
use Illuminate\Support\Facades\Auth;

class BisnisPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = BisnisPartner::with('bank');

        // Handle search parameter (general search across nama_bp)
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Handle legacy nama_bp parameter (for backward compatibility)
        if ($request->filled('nama_bp')) {
            $query->where('nama_bp', 'like', '%'.$request->nama_bp.'%');
        }

        // Handle jenis_bp filter
        if ($request->filled('jenis_bp')) {
            $query->byJenisBp($request->jenis_bp);
        }

        // Handle terms_of_payment filter
        if ($request->filled('terms_of_payment')) {
            $query->byTermsOfPayment($request->terms_of_payment);
        }

        // Handle per_page parameter for pagination
        $perPage = $request->filled('per_page') ? $request->per_page : 10;

        $bisnisPartners = $query->orderByDesc('created_at')->paginate($perPage);

        // Cache banks data for better performance
        $banks = cache()->remember('banks_all', 3600, function() {
            return Bank::select('id', 'nama_bank', 'singkatan')->orderBy('nama_bank')->get();
        });

        return Inertia::render('bisnis-partners/Index', [
            'bisnisPartners' => $bisnisPartners,
            'filters' => [
                'search' => $request->search,
                'nama_bp' => $request->nama_bp,
                'jenis_bp' => $request->jenis_bp,
                'terms_of_payment' => $request->terms_of_payment,
                'per_page' => $perPage,
            ],
            'banks' => $banks,
        ]);
    }

    public function create()
    {
        // Ambil data banks untuk dropdown
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        return Inertia::render('bisnis-partners/Create', [
            'banks' => $banks
        ]);
    }

    public function store(StoreBisnisPartnerRequest $request)
    {
        try {
            $bisnisPartner = BisnisPartner::create($request->validated());
            // Log activity
            BisnisPartnerLog::create([
                'bisnis_partner_id' => $bisnisPartner->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => 'Bisnis Partner dibuat',
                'ip_address' => $request->ip(),
            ]);
            return redirect()->back()->with('success', 'Bisnis Partner berhasil ditambahkan.');
        } catch (\Exception $e) {
            $msg = 'Gagal menyimpan data Bisnis Partner.';
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $msg = 'Nama Bisnis Partner atau Email sudah digunakan.';
            }
            return redirect()->back()
                           ->with('error', $msg)
                           ->withInput();
        }
    }

    public function show(BisnisPartner $bisnis_partner)
    {
        // Load the bank relationship
        $bisnis_partner->load('bank');
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();
        return Inertia::render('bisnis-partners/Detail', [
            'bisnisPartner' => $bisnis_partner,
            'banks' => $banks
        ]);
    }

    public function edit(BisnisPartner $bisnis_partner)
    {
        // Ambil data banks untuk dropdown
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        return Inertia::render('bisnis-partners/Edit', [
            'bisnisPartner' => $bisnis_partner->load('bank'),
            'banks' => $banks
        ]);
    }

    public function update(UpdateBisnisPartnerRequest $request, BisnisPartner $bisnis_partner)
    {
        try {
            $bisnis_partner->update($request->validated());
            // Log activity
            BisnisPartnerLog::create([
                'bisnis_partner_id' => $bisnis_partner->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Bisnis Partner diupdate',
                'ip_address' => $request->ip(),
            ]);
            return redirect()->back()->with('success', 'Bisnis Partner berhasil diupdate.');
        } catch (\Exception $e) {
            $msg = 'Gagal mengupdate data Bisnis Partner.';
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $msg = 'Nama Bisnis Partner atau Email sudah digunakan.';
            }
            return redirect()->back()
                           ->with('error', $msg)
                           ->withInput();
        }
    }

    public function destroy(BisnisPartner $bisnis_partner)
    {
        try {
            $bisnis_partner->forceDelete();
            // Log activity
            BisnisPartnerLog::create([
                'bisnis_partner_id' => $bisnis_partner->id,
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'description' => 'Bisnis Partner dihapus permanen',
                'ip_address' => request()->ip(),
            ]);
            // Selalu redirect ke index untuk Inertia
            if (request()->header('X-Inertia')) {
                return redirect()->route('bisnis-partners.index')->with('success', 'Bisnis Partner berhasil dihapus secara permanen.');
            }
            return redirect()->route('bisnis-partners.index')->with('success', 'Bisnis Partner berhasil dihapus secara permanen.');
        } catch (\Exception $e) {
            $msg = 'Gagal menghapus Bisnis Partner.';
            if (str_contains($e->getMessage(), 'foreign key')) {
                $msg = 'Data tidak dapat dihapus karena masih digunakan di data lain.';
            }
            // Selalu redirect ke index untuk Inertia, JANGAN back
            if (request()->header('X-Inertia')) {
                return redirect()->route('bisnis-partners.index')->with('error', $msg);
            }
            return redirect()->route('bisnis-partners.index')->with('error', $msg);
        }
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
    
    public function logs(BisnisPartner $bisnis_partner, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $bisnis_partner = \App\Models\BisnisPartner::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($bisnis_partner->id);

        $logs = \App\Models\BisnisPartnerLog::with(['user.department', 'user.role'])
            ->where('bisnis_partner_id', $bisnis_partner->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        // Get all roles and departments for filter options
        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        // Get unique actions from logs for filter options
        $actionOptions = \App\Models\BisnisPartnerLog::where('bisnis_partner_id', $bisnis_partner->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('bisnis-partners/Log', [
            'bisnisPartner' => $bisnis_partner,
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }
}
