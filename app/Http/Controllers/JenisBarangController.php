<?php

namespace App\Http\Controllers;

use App\Models\JenisBarang;
use App\Http\Requests\StoreJenisBarangRequest;
use App\Http\Requests\UpdateJenisBarangRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JenisBarangController extends Controller
{
    public function index(Request $request)
    {
        $query = JenisBarang::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_jenis_barang', 'like', "%$search%")
                  ->orWhere('singkatan', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->integer('per_page', 10);
        $items = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('jenis-barangs/Index', [
            'items' => $items,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreJenisBarangRequest $request)
    {
        JenisBarang::create($request->validated());
        return redirect()->route('jenis-barangs.index')->with('success', 'Data Jenis Barang berhasil ditambahkan');
    }

    public function update(UpdateJenisBarangRequest $request, $id)
    {
        $model = JenisBarang::findOrFail($id);
        $model->update($request->validated());
        return redirect()->route('jenis-barangs.index')->with('success', 'Data Jenis Barang berhasil diperbarui');
    }

    public function destroy($id)
    {
        $model = JenisBarang::findOrFail($id);
        $model->delete();
        return redirect()->route('jenis-barangs.index')->with('success', 'Data Jenis Barang berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $model = JenisBarang::findOrFail($id);
        $model->status = $model->status === 'active' ? 'inactive' : 'active';
        $model->save();
        return redirect()->route('jenis-barangs.index')->with('success', 'Status Jenis Barang berhasil diperbarui');
    }

    public function restore($id)
    {
        $model = JenisBarang::withTrashed()->findOrFail($id);
        $model->restore();
        return redirect()->route('jenis-barangs.index')->with('success', 'Data Jenis Barang berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $model = JenisBarang::withTrashed()->findOrFail($id);
        $model->forceDelete();
        return redirect()->route('jenis-barangs.index')->with('success', 'Data Jenis Barang berhasil dihapus permanen');
    }
}
