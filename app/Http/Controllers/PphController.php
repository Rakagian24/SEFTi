<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Pph;
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
                'description' => 'PPh dibuat',
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
                'description' => 'PPh diupdate',
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
            'description' => 'PPh dihapus',
            'ip_address' => request()->ip(),
        ]);
        $pph->delete();
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

    public function logs(Pph $pph, Request $request)
    {
        $logs = PphLog::with(['user.department', 'user.role'])
            ->where('pph_id', $pph->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = \App\Models\Department::select('id', 'name')->orderBy('name')->get();
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
