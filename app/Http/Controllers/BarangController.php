<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Supplier;
use App\Services\DepartmentService;
use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $query = Barang::with(['jenisBarang', 'departments', 'supplier']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%$search%")
                  ->orWhere('satuan', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%")
                  ->orWhereHas('jenisBarang', function($r) use ($search) {
                      $r->where('nama_jenis_barang', 'like', "%$search%")
                        ->orWhere('singkatan', 'like', "%$search%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('jenis_barang_id')) {
            $query->where('jenis_barang_id', $request->jenis_barang_id);
        }

        $perPage = $request->integer('per_page', 10);
        $items = $query->orderByDesc('created_at')->paginate($perPage);

        $jenisOptions = JenisBarang::active()->get(['id', 'nama_jenis_barang', 'singkatan', 'status']);
        $supplierOptions = Supplier::where('status', 'active')->orderBy('nama_supplier')->get(['id', 'nama_supplier']);
        $departmentOptionsForForm = DepartmentService::getOptionsForForm();

        return Inertia::render('barangs/Index', [
            'items' => $items,
            'jenisOptions' => $jenisOptions,
            'departmentOptionsForForm' => $departmentOptionsForForm,
            'supplierOptions' => $supplierOptions,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'jenis_barang_id' => $request->jenis_barang_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreBarangRequest $request)
    {
        $data = $request->validated();
        $departmentIds = collect($data['department_ids'] ?? [])->map(fn($id) => (int) $id)->unique()->values()->all();
        unset($data['department_ids']);

        $barang = Barang::create($data);
        if (!empty($departmentIds)) {
            $barang->departments()->sync($departmentIds);
        }
        return redirect()->route('barangs.index')->with('success', 'Data Barang berhasil ditambahkan');
    }

    public function update(UpdateBarangRequest $request, $id)
    {
        $model = Barang::findOrFail($id);
        $data = $request->validated();
        $departmentIds = collect($data['department_ids'] ?? [])->map(fn($id) => (int) $id)->unique()->values()->all();
        unset($data['department_ids']);

        $model->update($data);
        if (!empty($departmentIds)) {
            $model->departments()->sync($departmentIds);
        } else {
            $model->departments()->sync([]);
        }
        return redirect()->route('barangs.index')->with('success', 'Data Barang berhasil diperbarui');
    }

    public function destroy($id)
    {
        $model = Barang::findOrFail($id);
        $model->delete();
        return redirect()->route('barangs.index')->with('success', 'Data Barang berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $model = Barang::findOrFail($id);
        $model->status = $model->status === 'active' ? 'inactive' : 'active';
        $model->save();
        return redirect()->route('barangs.index')->with('success', 'Status Barang berhasil diperbarui');
    }

    public function restore($id)
    {
        $model = Barang::withTrashed()->findOrFail($id);
        $model->restore();
        return redirect()->route('barangs.index')->with('success', 'Data Barang berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $model = Barang::withTrashed()->findOrFail($id);
        $model->forceDelete();
        return redirect()->route('barangs.index')->with('success', 'Data Barang berhasil dihapus permanen');
    }
}
