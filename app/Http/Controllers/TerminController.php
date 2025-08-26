<?php

namespace App\Http\Controllers;

use App\Models\Termin;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentNumberService;

class TerminController extends Controller
{
    public function index(Request $request)
    {
        $query = Termin::query();

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('no_referensi', 'like', '%' . $searchTerm . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $termins = $query->orderByDesc('created_at')->paginate($perPage);

        $departments = \App\Models\Department::orderBy('name')->get(['id','name']);

        return Inertia::render('termins/Index', [
            'termins' => $termins,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'per_page' => $perPage,
            ],
            'departmentOptions' => $departments,
        ]);
    }

    public function create()
    {
        return Inertia::render('termins/Create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'no_referensi' => 'required|string|max:100|unique:termins,no_referensi',
                'jumlah_termin' => 'required|integer|min:1',
                'keterangan' => 'nullable|string',
                'department_id' => 'required|exists:departments,id',
                'status' => 'required|in:active,inactive',
            ], [
                'no_referensi.required' => 'No Referensi wajib diisi.',
                'no_referensi.unique' => 'No Referensi sudah digunakan.',
                'jumlah_termin.required' => 'Jumlah Termin wajib diisi.',
                'jumlah_termin.integer' => 'Jumlah Termin harus berupa angka.',
                'jumlah_termin.min' => 'Jumlah Termin minimal 1.',
                'department_id.required' => 'Department wajib diisi.',
                'department_id.exists' => 'Department tidak valid.',
                'status.required' => 'Status wajib diisi.',
            ]);

            $termin = Termin::create($validated);

            return redirect()->route('termins.index')
                             ->with('success', 'Data termin berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data termin: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $termin = Termin::findOrFail($id);
        return Inertia::render('termins/Show', [
            'termin' => $termin
        ]);
    }

    public function edit($id)
    {
        $termin = Termin::findOrFail($id);
        return Inertia::render('termins/Edit', [
            'termin' => $termin
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $termin = Termin::findOrFail($id);

            $validated = $request->validate([
                'no_referensi' => 'required|string|max:100|unique:termins,no_referensi,' . $id,
                'jumlah_termin' => 'required|integer|min:1',
                'keterangan' => 'nullable|string',
                'department_id' => 'required|exists:departments,id',
                'status' => 'required|in:active,inactive',
            ], [
                'no_referensi.required' => 'No Referensi wajib diisi.',
                'no_referensi.unique' => 'No Referensi sudah digunakan.',
                'jumlah_termin.required' => 'Jumlah Termin wajib diisi.',
                'jumlah_termin.integer' => 'Jumlah Termin harus berupa angka.',
                'jumlah_termin.min' => 'Jumlah Termin minimal 1.',
                'department_id.required' => 'Department wajib diisi.',
                'department_id.exists' => 'Department tidak valid.',
                'status.required' => 'Status wajib diisi.',
            ]);

            $termin->update($validated);

            return redirect()->route('termins.index')
                             ->with('success', 'Data termin berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data termin: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $termin = Termin::findOrFail($id);
            $termin->delete(); // Ini sekarang akan soft delete

            return redirect()->route('termins.index')
                             ->with('success', 'Data termin berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus data termin: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $termin = Termin::findOrFail($id);
            $termin->status = $termin->status === 'active' ? 'inactive' : 'active';
            $termin->save();

            return redirect()->route('termins.index')
                             ->with('success', 'Status termin berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui status termin: ' . $e->getMessage());
        }
    }

    /**
     * Force delete (permanently remove from database)
     */
    public function forceDelete($id)
    {
        $termin = Termin::withTrashed()->findOrFail($id);

        try {
            $termin->forceDelete();
            return redirect()->route('termins.index')
                           ->with('success', 'Data termin berhasil dihapus permanen');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus permanen data termin: ' . $e->getMessage());
        }
    }

    /**
     * Restore soft deleted record
     */
    public function restore($id)
    {
        $termin = Termin::withTrashed()->findOrFail($id);

        try {
            $termin->restore();
            return redirect()->route('termins.index')
                           ->with('success', 'Data termin berhasil dipulihkan');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal memulihkan data termin: ' . $e->getMessage());
        }
    }

    /**
     * Generate preview document number for Termin
     */
    public function generatePreviewNumber(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info('TerminController - generatePreviewNumber called', [
                'request_data' => $request->all()
            ]);

            $request->validate([
                'department_id' => 'required|exists:departments,id',
            ]);

            $department = \App\Models\Department::findOrFail($request->department_id);

            \Illuminate\Support\Facades\Log::info('TerminController - Department found', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'department_alias' => $department->alias
            ]);

            // Check if department has alias
            if (empty($department->alias)) {
                \Illuminate\Support\Facades\Log::error('TerminController - Department missing alias', [
                    'department_id' => $department->id,
                    'department_name' => $department->name
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Department tidak memiliki alias yang valid'
                ], 400);
            }

            $previewNumber = DocumentNumberService::generateFormPreviewNumber(
                'Termin',
                null, // Termin doesn't have tipe
                $department->id,
                $department->alias
            );

            \Illuminate\Support\Facades\Log::info('TerminController - Preview number generated', [
                'preview_number' => $previewNumber
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'preview_number' => $previewNumber
                ]
            ])->header('Content-Type', 'application/json')
              ->header('X-Debug-Preview-Number', $previewNumber)
              ->header('X-Debug-Department', $department->alias);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TerminController - Error generating preview number', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate preview nomor: ' . $e->getMessage()
            ], 500);
        }
    }
}
