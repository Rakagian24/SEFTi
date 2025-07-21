<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ArPartner;
use Illuminate\Http\Request;
use App\Http\Requests\StoreArPartnerRequest;
use App\Http\Requests\UpdateArPartnerRequest;

class ArPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = ArPartner::with('department');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama_ap', 'like', '%' . $searchTerm . '%')
                  ->orWhere('jenis_ap', 'like', '%' . $searchTerm . '%')
                  ->orWhere('alamat', 'like', '%' . $searchTerm . '%')
                  ->orWhere('no_telepon', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by jenis_ap
        if ($request->filled('jenis_ap')) {
            $query->where('jenis_ap', $request->jenis_ap);
        }
        // Filter by department
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $arPartners = $query->orderByDesc('created_at')->paginate($perPage);

        $departments = \App\Models\Department::select('id', 'name')->get();

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
                           ->with('success', 'Data AR Partner berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menyimpan data AR Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function show($id)
    {
        $arPartner = ArPartner::findOrFail($id);
        $departments = \App\Models\Department::select('id', 'name')->orderBy('name')->get();

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
                           ->with('success', 'Data AR Partner berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal mengupdate data AR Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function destroy($id)
    {
        $arPartner = ArPartner::findOrFail($id);

        try {
            $arPartner->delete();
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data AR Partner berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus data AR Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function logs(ArPartner $ar_partner, Request $request)
    {
        $logs = \App\Models\ArPartnerLog::with(['user.department', 'user.role'])
            ->where('ar_partner_id', $ar_partner->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = \App\Models\Department::select('id', 'name')->orderBy('name')->get();
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
}
