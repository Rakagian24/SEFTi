<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;

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
        ]);

        Pengeluaran::create($validated);

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
        ]);
        $pengeluaran->update($validated);
        return redirect()->route('pengeluarans.index')
                         ->with('success', 'Data Pengeluaran berhasil diperbarui');
    }

    public function destroy($id)
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        $pengeluaran->delete();
        return redirect()->route('pengeluarans.index')
                         ->with('success', 'Data Pengeluaran berhasil dihapus');
    }
}
