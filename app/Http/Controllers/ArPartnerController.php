<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ArPartner;
use Illuminate\Http\Request;
use App\Http\Requests\StoreArPartnerRequest;
use App\Http\Requests\UpdateArPartnerRequest;
use App\Services\MigrasiPelangganService;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Auth;

class ArPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = ArPartner::with('department');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by jenis_ap
        if ($request->filled('jenis_ap')) {
            $query->byJenisAp($request->jenis_ap);
        }
        // Filter by department
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $arPartners = $query->orderByDesc('created_at')->paginate($perPage);

        // Get department options based on user permissions
        $departments = DepartmentService::getOptionsForFilter();

        return Inertia::render('ar-partners/Index', [
            'arPartners' => $arPartners,
            'filters' => [
                'search' => $request->search,
                'jenis_ap' => $request->jenis_ap,
                'per_page' => $perPage,
                'department' => $request->department,
            ],
            'departments' => $departments,
        ]);
    }

    public function store(StoreArPartnerRequest $request)
    {
        try {
            $arPartners = ArPartner::create($request->validated());

            // Redirect kembali ke index dengan pesan sukses
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data Customer berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menyimpan data Customer: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function show($id)
    {
        $arPartner = ArPartner::findOrFail($id);
        $departments = DepartmentService::getOptionsForForm();

        return Inertia::render('ar-partners/Show', [
            'arPartner' => $arPartner,
            'departments' => $departments,
        ]);
    }

    public function update(UpdateArPartnerRequest $request, $id)
    {
        $arPartner = ArPartner::findOrFail($id);

        try {
            $arPartner->update($request->validated());

            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data Customer berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal mengupdate data Customer: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function destroy($id)
    {
        $arPartner = ArPartner::findOrFail($id);

        try {
            $arPartner->delete(); // Ini sekarang akan soft delete
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data Customer berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus data Customer: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Force delete (permanently remove from database)
     */
    public function forceDelete($id)
    {
        $arPartner = ArPartner::withTrashed()->findOrFail($id);

        try {
            $arPartner->forceDelete();
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data Customer berhasil dihapus permanen');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus permanen data Customer: ' . $e->getMessage());
        }
    }

    /**
     * Restore soft deleted record
     */
    public function restore($id)
    {
        $arPartner = ArPartner::withTrashed()->findOrFail($id);

        try {
            $arPartner->restore();
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data Customer berhasil dipulihkan');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal memulihkan data Customer: ' . $e->getMessage());
        }
    }

    public function logs(ArPartner $ar_partner, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $ar_partner = \App\Models\ArPartner::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($ar_partner->id);

        $logs = \App\Models\ArPartnerLog::with(['user.department', 'user.role'])
            ->where('ar_partner_id', $ar_partner->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = \App\Models\ArPartnerLog::where('ar_partner_id', $ar_partner->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('ar-partners/Log', [
            'arPartner' => $ar_partner,
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }

    public function migrate(MigrasiPelangganService $migrasiService)
    {
        try {
            $count = $migrasiService->jalankanMigrasi();

            return redirect()->route('ar-partners.index')
                           ->with('success', "Migrasi berhasil! Total data yang dimigrasi: $count");
        } catch (\Exception $e) {
            return redirect()->route('ar-partners.index')
                           ->with('error', 'Gagal menjalankan migrasi: ' . $e->getMessage());
        }
    }
}
