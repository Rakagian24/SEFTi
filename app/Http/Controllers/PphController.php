<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Pph;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'kode_pph' => 'required|string|max:255',
            'nama_pph' => 'required|string|max:255',
            'tarif_pph' => 'nullable|numeric|min:0|max:100',
            'deskripsi' => 'nullable|string',
            'status' => 'required|string|max:50',
        ]);

        Pph::create($validated);

        return redirect()->route('pphs.index')
                         ->with('success', 'Data PPh berhasil ditambahkan');
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
        $validated = $request->validate([
            'kode_pph' => 'required|string|max:255',
            'nama_pph' => 'required|string|max:255',
            'tarif_pph' => 'nullable|numeric|min:0|max:100',
            'deskripsi' => 'nullable|string',
            'status' => 'required|string|max:255',
        ]);
        $pph->update($validated);
        return redirect()->route('pphs.index')
                         ->with('success', 'Data PPh berhasil diperbarui');
    }

    public function destroy($id)
    {
        $pph = Pph::findOrFail($id);
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
}
