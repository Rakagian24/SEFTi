<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Pph;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use App\Models\PphLog;
use Illuminate\Support\Facades\Auth;

class PphController extends Controller
{
    public function index(Request $request)
    {
        $query = Pph::query();

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('kode_pph', 'like', '%' . $searchTerm . '%')
                  ->orWhere('nama_pph', 'like', '%' . $searchTerm . '%')
                  ->orWhere('tarif_pph', 'like', '%' . $searchTerm . '%')
                  ->orWhere('deskripsi', 'like', '%' . $searchTerm . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->distinct();

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $pphs = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('pphs/Index', [
            'pphs' => $pphs,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'per_page' => $perPage,
            ],
        ]);
    }

    // API endpoint for frontend to get PPH list
    public function apiIndex(Request $request)
    {
        $query = Pph::query();

        // Only return active PPHs for the API
        $query->where('status', 'active');

        $pphs = $query->orderBy('kode_pph')->get();

        return response()->json($pphs);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kode_pph' => 'required|string|max:255|unique:pphs,kode_pph',
                'nama_pph' => 'required|string|max:255|unique:pphs,nama_pph',
                'tarif_pph' => 'nullable|numeric|min:0|max:100',
                'deskripsi' => 'nullable|string',
                'status' => 'required|string|max:50',
            ], [
                'kode_pph.required' => 'Kode PPh wajib diisi.',
                'kode_pph.unique' => 'Kode PPh sudah digunakan.',
                'nama_pph.required' => 'Nama PPh wajib diisi.',
                'nama_pph.unique' => 'Nama PPh sudah digunakan.',
                'tarif_pph.numeric' => 'Tarif PPh harus berupa angka.',
                'tarif_pph.min' => 'Tarif PPh minimal 0.',
                'tarif_pph.max' => 'Tarif PPh maksimal 100.',
                'status.required' => 'Status wajib diisi.',
            ]);

            $pph = Pph::create($validated);
            // Log activity
            PphLog::create([
                'pph_id' => $pph->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => 'Membuat data PPh',
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('pphs.index')
                             ->with('success', 'Data PPh berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data PPh: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $pph = Pph::findOrFail($id);
        return Inertia::render('pphs/Show', [
            'pph' => $pph
        ]);
    }

    public function update(Request $request, $id)
    {
        $pph = Pph::findOrFail($id);
        try {
            $validated = $request->validate([
                'kode_pph' => 'required|string|max:255|unique:pphs,kode_pph,' . $id,
                'nama_pph' => 'required|string|max:255|unique:pphs,nama_pph,' . $id,
                'tarif_pph' => 'nullable|numeric|min:0|max:100',
                'deskripsi' => 'nullable|string',
                'status' => 'required|string|max:255',
            ], [
                'kode_pph.required' => 'Kode PPh wajib diisi.',
                'kode_pph.unique' => 'Kode PPh sudah digunakan.',
                'nama_pph.required' => 'Nama PPh wajib diisi.',
                'nama_pph.unique' => 'Nama PPh sudah digunakan.',
                'tarif_pph.numeric' => 'Tarif PPh harus berupa angka.',
                'tarif_pph.min' => 'Tarif PPh minimal 0.',
                'tarif_pph.max' => 'Tarif PPh maksimal 100.',
                'status.required' => 'Status wajib diisi.',
            ]);
            $pph->update($validated);
            // Log activity
            PphLog::create([
                'pph_id' => $pph->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Mengubah data PPh',
                'ip_address' => $request->ip(),
            ]);
            return redirect()->route('pphs.index')
                             ->with('success', 'Data PPh berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengupdate data PPh: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $pph = Pph::findOrFail($id);
        // Log activity BEFORE delete
        PphLog::create([
            'pph_id' => $pph->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Menghapus data PPh',
            'ip_address' => request()->ip(),
        ]);
        $pph->delete(); // Ini sekarang akan soft delete
        return redirect()->route('pphs.index')
                         ->with('success', 'Data PPh berhasil dihapus');
    }

        public function toggleStatus($id)
    {
        $pph = Pph::findOrFail($id);
        $pph->status = $pph->status === 'active' ? 'inactive' : 'active';
        $pph->save();

        return redirect()->route('pphs.index')
                         ->with('success', 'Status PPh berhasil diperbarui');
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

    public function logs(Pph $pph, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $pph = \App\Models\Pph::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($pph->id);

        $logs = PphLog::with(['user.department', 'user.role'])
            ->where('pph_id', $pph->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = PphLog::where('pph_id', $pph->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('pphs/Log', [
            'pph' => $pph,
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }
}
