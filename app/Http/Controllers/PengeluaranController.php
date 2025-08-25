<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Pengeluaran;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use App\Models\PengeluaranLog;
use Illuminate\Support\Facades\Auth;

class PengeluaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengeluaran::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('nama', 'like', '%' . $searchTerm . '%')
                  ->orWhere('deskripsi', 'like', '%' . $searchTerm . '%');
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $pengeluarans = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('pengeluarans/Index', [
            'pengeluarans' => $pengeluarans,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $pengeluaran = Pengeluaran::create($validated);
        // Log activity
        PengeluaranLog::create([
            'pengeluaran_id' => $pengeluaran->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Pengeluaran dibuat',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('pengeluarans.index')
                         ->with('success', 'Data Pengeluaran berhasil ditambahkan');
    }

    public function show($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        return Inertia::render('pengeluarans/Show', [
            'pengeluaran' => $pengeluaran
        ]);
    }

    public function update(Request $request, $id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $pengeluaran->update($validated);
        // Log activity
        PengeluaranLog::create([
            'pengeluaran_id' => $pengeluaran->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => 'Pengeluaran diupdate',
            'ip_address' => $request->ip(),
        ]);
        return redirect()->route('pengeluarans.index')
                         ->with('success', 'Data Pengeluaran berhasil diperbarui');
    }

    public function destroy($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        // Log activity BEFORE delete
        PengeluaranLog::create([
            'pengeluaran_id' => $pengeluaran->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Pengeluaran dihapus',
            'ip_address' => request()->ip(),
        ]);
        $pengeluaran->delete(); // Ini sekarang akan soft delete
        return redirect()->route('pengeluarans.index')
                         ->with('success', 'Data Pengeluaran berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        $pengeluaran->status = $pengeluaran->status === 'active' ? 'inactive' : 'active';
        $pengeluaran->save();
        return redirect()->route('pengeluarans.index')
                         ->with('success', 'Status pengeluaran berhasil diperbarui');
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
    
    public function logs(Pengeluaran $pengeluaran, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $pengeluaran = \App\Models\Pengeluaran::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($pengeluaran->id);

        $logs = PengeluaranLog::with(['user.department', 'user.role'])
            ->where('pengeluaran_id', $pengeluaran->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = PengeluaranLog::where('pengeluaran_id', $pengeluaran->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('pengeluarans/Log', [
            'pengeluaran' => $pengeluaran,
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }
}
