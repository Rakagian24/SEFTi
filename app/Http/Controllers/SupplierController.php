<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Bank;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use App\Models\SupplierLog;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::with(['banks', 'department']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('terms_of_payment')) {
            $query->byTermsOfPayment($request->terms_of_payment);
        }

        if ($request->filled('supplier')) {
            $query->where('nama_supplier', $request->supplier);
        }

        if ($request->filled('bank')) {
            $query->byBank($request->bank);
        }
        // Filter by department
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $suppliers = $query->orderByDesc('created_at')->paginate($perPage);

        // Cache master data for better performance
        $banks = cache()->remember('banks_active', 3600, function() {
            return Bank::where('status', 'active')->orderBy('nama_bank')->get();
        });

        $departments = DepartmentService::getOptionsForFilter();
        $departmentsForForm = DepartmentService::getOptionsForForm();

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $request->search,
                'terms_of_payment' => $request->terms_of_payment,
                'supplier' => $request->supplier,
                'bank' => $request->bank,
                'department' => $request->department,
                'per_page' => $perPage,
            ],
            'banks' => $banks,
            'departmentOptions' => $departments,
            'departmentOptionsForForm' => $departmentsForForm,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'alamat' => 'required|string',
            'email' => 'required|email|max:255',
            'contact' => 'nullable|string|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'bank_accounts' => 'required|array|min:1|max:3',
            'bank_accounts.*.bank_id' => 'required|exists:banks,id',
            'bank_accounts.*.nama_rekening' => 'required|string|max:255',
            'bank_accounts.*.no_rekening' => 'required|string|max:255',
            'terms_of_payment' => 'nullable|string|max:255',
        ], [
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'nama_supplier.max' => 'Nama supplier maksimal 255 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'contact.max' => 'Contact maksimal 255 karakter.',
            'no_telepon.max' => 'No telepon maksimal 50 karakter.',
            'department_id.exists' => 'Departemen tidak valid.',
            'bank_accounts.required' => 'Minimal satu rekening bank harus diisi.',
            'bank_accounts.array' => 'Format rekening bank tidak valid.',
            'bank_accounts.min' => 'Minimal satu rekening bank harus diisi.',
            'bank_accounts.max' => 'Maksimal tiga rekening bank.',
            'bank_accounts.*.bank_id.required' => 'Bank wajib dipilih.',
            'bank_accounts.*.bank_id.exists' => 'Bank yang dipilih tidak valid.',
            'bank_accounts.*.nama_rekening.required' => 'Nama rekening wajib diisi.',
            'bank_accounts.*.nama_rekening.max' => 'Nama rekening maksimal 255 karakter.',
            'bank_accounts.*.no_rekening.required' => 'No rekening wajib diisi.',
            'bank_accounts.*.no_rekening.max' => 'No rekening maksimal 255 karakter.',
            'terms_of_payment.max' => 'Terms of payment maksimal 255 karakter.',
        ]);

        // Create supplier
        $supplier = Supplier::create([
            'nama_supplier' => $validated['nama_supplier'],
            'alamat' => $validated['alamat'],
            'email' => $validated['email'],
            'contact' => $validated['contact'] ?? null,
            'no_telepon' => $validated['no_telepon'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'terms_of_payment' => $validated['terms_of_payment'] ?? null,
        ]);

        // Attach bank accounts to pivot table
        foreach ($validated['bank_accounts'] as $account) {
            $supplier->banks()->attach($account['bank_id'], [
                'nama_rekening' => $account['nama_rekening'],
                'no_rekening' => $account['no_rekening'],
            ]);
        }

        // Log activity
        SupplierLog::create([
            'supplier_id' => $supplier->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Membuat data Supplier',
            'ip_address' => $request->ip(),
        ]);

        if ($request->header('X-Inertia')) {
            return redirect()->back(303)->with('success', 'Data Supplier berhasil ditambahkan');
        }
        return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil ditambahkan');
    }

    public function show($id)
    {
        $supplier = Supplier::with(['banks', 'department'])->findOrFail($id);
        $banks = Bank::where('status', 'active')->get(['id', 'nama_bank', 'singkatan']);
        $departmentOptions = DepartmentService::getOptionsForForm();
        return Inertia::render('suppliers/Detail', [
            'supplier' => $supplier,
            'banks' => $banks,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'alamat' => 'required|string',
            'email' => 'required|email|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'bank_accounts' => 'required|array|min:1|max:3',
            'bank_accounts.*.bank_id' => 'required|exists:banks,id',
            'bank_accounts.*.nama_rekening' => 'required|string|max:255',
            'bank_accounts.*.no_rekening' => 'required|string|max:255',
            'terms_of_payment' => 'nullable|string|max:255',
        ], [
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'nama_supplier.max' => 'Nama supplier maksimal 255 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'no_telepon.max' => 'No telepon maksimal 50 karakter.',
            'department_id.exists' => 'Departemen tidak valid.',
            'bank_accounts.required' => 'Minimal satu rekening bank harus diisi.',
            'bank_accounts.array' => 'Format rekening bank tidak valid.',
            'bank_accounts.min' => 'Minimal satu rekening bank harus diisi.',
            'bank_accounts.max' => 'Maksimal tiga rekening bank.',
            'bank_accounts.*.bank_id.required' => 'Bank wajib dipilih.',
            'bank_accounts.*.bank_id.exists' => 'Bank yang dipilih tidak valid.',
            'bank_accounts.*.nama_rekening.required' => 'Nama rekening wajib diisi.',
            'bank_accounts.*.nama_rekening.max' => 'Nama rekening maksimal 255 karakter.',
            'bank_accounts.*.no_rekening.required' => 'No rekening wajib diisi.',
            'bank_accounts.*.no_rekening.max' => 'No rekening maksimal 255 karakter.',
            'terms_of_payment.max' => 'Terms of payment maksimal 255 karakter.',
        ]);

        // Update supplier basic info
        $supplier->update([
            'nama_supplier' => $validated['nama_supplier'],
            'alamat' => $validated['alamat'],
            'email' => $validated['email'],
            'contact' => $validated['contact'] ?? null,
            'no_telepon' => $validated['no_telepon'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'terms_of_payment' => $validated['terms_of_payment'] ?? null,
        ]);

        try {
            // Update bank accounts - detach all existing and attach new ones
            $supplier->banks()->detach();
            foreach ($validated['bank_accounts'] as $account) {
                $supplier->banks()->attach($account['bank_id'], [
                    'nama_rekening' => $account['nama_rekening'],
                    'no_rekening' => $account['no_rekening'],
                ]);
            }

            // Log activity
            SupplierLog::create([
                'supplier_id' => $supplier->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Mengubah data Supplier',
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('suppliers.index')
                             ->with('success', 'Data Supplier berhasil diperbarui');
        } catch (\Illuminate\Database\QueryException $e) {
            // Jika gagal karena constraint (misal sudah dipakai di Payment Voucher),
            // biarkan perubahan basic info supplier tetap tersimpan, tapi tampilkan pesan yang jelas.
            $message = 'Rekening bank supplier tidak dapat diubah karena sudah digunakan pada Payment Voucher.';

            if ($request->header('X-Inertia')) {
                return redirect()->back()->with('error', $message);
            }

            return redirect()->route('suppliers.index')->with('error', $message);
        }
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        // Log activity BEFORE deleting the supplier
        SupplierLog::create([
            'supplier_id' => $supplier->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Menghapus data Supplier',
            'ip_address' => request()->ip(),
        ]);
        try {
            $supplier->delete(); // Ini sekarang akan soft delete
            if (request()->header('X-Inertia')) {
                return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil dihapus');
            } else if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Data Supplier berhasil dihapus']);
            }
            return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil dihapus');
        } catch (\Exception $e) {
            $msg = 'Data tidak dapat dihapus karena masih digunakan di data lain.';
            if (request()->header('X-Inertia')) {
                return redirect()->back()->with('error', $msg);
            } else if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    /**
     * Force delete (permanently remove from database)
     */
    public function forceDelete($id)
    {
        $supplier = Supplier::withTrashed()->findOrFail($id);

        try {
            $supplier->forceDelete();
            return redirect()->route('suppliers.index')
                           ->with('success', 'Data Supplier berhasil dihapus permanen');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus permanen data Supplier: ' . $e->getMessage());
        }
    }

    /**
     * Restore soft deleted record
     */
    public function restore($id)
    {
        $supplier = Supplier::withTrashed()->findOrFail($id);

        try {
            $supplier->restore();
            return redirect()->route('suppliers.index')
                           ->with('success', 'Data Supplier berhasil dipulihkan');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal memulihkan data Supplier: ' . $e->getMessage());
        }
    }

    public function logs(Supplier $supplier, Request $request)
    {
        // Bypass DepartmentScope for the main entity on log pages
        $supplier = \App\Models\Supplier::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
            ->findOrFail($supplier->id);

        $logs = SupplierLog::with(['user.department', 'user.role'])
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = SupplierLog::where('supplier_id', $supplier->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('suppliers/Log', [
            'supplier' => $supplier,
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }
}
