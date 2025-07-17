<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Bank;
use Illuminate\Http\Request;
use App\Models\SupplierLog;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::with('banks');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'like', "%$searchTerm%")
                  ->orWhere('nama_supplier', 'like', "%$searchTerm%")
                  ->orWhere('alamat', 'like', "%$searchTerm%")
                  ->orWhere('email', 'like', "%$searchTerm%")
                  ->orWhere('no_telepon', 'like', "%$searchTerm%")
                  ->orWhere('terms_of_payment', 'like', "%$searchTerm%")
                  ->orWhere('created_at', 'like', "%$searchTerm%")
                  ->orWhere('updated_at', 'like', "%$searchTerm%")
                  ->orWhereHas('banks', function($b) use ($searchTerm) {
                      $b->where('nama_bank', 'like', "%$searchTerm%")
                        ->orWhere('singkatan', 'like', "%$searchTerm%");
                  });
            });
        }

        if ($request->filled('terms_of_payment')) {
            $query->where('terms_of_payment', $request->terms_of_payment);
        }

        if ($request->filled('supplier')) {
            $query->where('nama_supplier', $request->supplier);
        }

        if ($request->filled('bank')) {
            $query->whereHas('banks', function($q) use ($request) {
                $q->where('banks.id', $request->bank);
            });
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $suppliers = $query->orderByDesc('created_at')->paginate($perPage);

        $banks = Bank::where('status', 'active')->get(['id', 'nama_bank', 'singkatan']);

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'banks' => $banks,
            'filters' => [
                'search' => $request->search,
                'terms_of_payment' => $request->terms_of_payment,
                'supplier' => $request->supplier,
                'bank' => $request->bank,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'alamat' => 'required|string',
            'email' => 'required|email|max:255',
            'no_telepon' => 'nullable|string|max:50',
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
            'no_telepon' => $validated['no_telepon'],
            'terms_of_payment' => $validated['terms_of_payment'],
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
            'description' => 'Supplier dibuat',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('suppliers.index')
                         ->with('success', 'Data Supplier berhasil ditambahkan');
    }

    public function show($id)
    {
        $supplier = Supplier::with('banks')->findOrFail($id);
        $banks = Bank::where('status', 'active')->get(['id', 'nama_bank', 'singkatan']);
        return Inertia::render('suppliers/Detail', [
            'supplier' => $supplier,
            'banks' => $banks
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
            'no_telepon' => $validated['no_telepon'],
            'terms_of_payment' => $validated['terms_of_payment'],
        ]);

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
            'description' => 'Supplier diupdate',
            'ip_address' => $request->ip(),
        ]);
        return redirect()->route('suppliers.index')
                         ->with('success', 'Data Supplier berhasil diperbarui');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        // Log activity BEFORE deleting the supplier
        SupplierLog::create([
            'supplier_id' => $supplier->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Supplier dihapus',
            'ip_address' => request()->ip(),
        ]);
        try {
            $supplier->delete();
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

    public function logs(Supplier $supplier, Request $request)
    {
        $logs = SupplierLog::with(['user.department', 'user.role'])
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = \App\Models\Department::select('id', 'name')->orderBy('name')->get();
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
