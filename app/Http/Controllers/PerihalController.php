<?php

namespace App\Http\Controllers;

use App\Models\Perihal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PerihalController extends Controller
{
    public function index(Request $request)
    {
        $query = Perihal::query();

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama', 'like', '%' . $searchTerm . '%')
                  ->orWhere('deskripsi', 'like', '%' . $searchTerm . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $perihals = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('perihals/Index', [
            'perihals' => $perihals,
        ]);
    }

    public function create()
    {
        return Inertia::render('perihals/Create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:100|unique:perihals,nama',
                'deskripsi' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ], [
                'nama.required' => 'Nama perihal wajib diisi.',
                'nama.unique' => 'Nama perihal sudah digunakan.',
                'status.required' => 'Status wajib diisi.',
            ]);

            $perihal = Perihal::create($validated);

            return redirect()->route('perihals.index')
                             ->with('success', 'Data perihal berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data perihal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $perihal = Perihal::findOrFail($id);
        return Inertia::render('perihals/Show', [
            'perihal' => $perihal
        ]);
    }

    public function edit($id)
    {
        $perihal = Perihal::findOrFail($id);
        return Inertia::render('perihals/Edit', [
            'perihal' => $perihal
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $perihal = Perihal::findOrFail($id);

            $validated = $request->validate([
                'nama' => 'required|string|max:100|unique:perihals,nama,' . $id,
                'deskripsi' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ], [
                'nama.required' => 'Nama perihal wajib diisi.',
                'nama.unique' => 'Nama perihal sudah digunakan.',
                'status.required' => 'Status wajib diisi.',
            ]);

            $perihal->update($validated);

            return redirect()->route('perihals.index')
                             ->with('success', 'Data perihal berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data perihal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $perihal = Perihal::findOrFail($id);
            $perihal->delete();

            return redirect()->route('perihals.index')
                             ->with('success', 'Data perihal berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus data perihal: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $perihal = Perihal::findOrFail($id);
            $perihal->status = $perihal->status === 'active' ? 'inactive' : 'active';
            $perihal->save();

            return redirect()->route('perihals.index')
                             ->with('success', 'Status perihal berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui status perihal: ' . $e->getMessage());
        }
    }
}
