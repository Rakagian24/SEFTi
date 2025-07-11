<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Bank;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('nama_supplier', 'like', '%' . $searchTerm . '%')
                  ->orWhere('alamat', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%')
                  ->orWhere('no_telepon', 'like', '%' . $searchTerm . '%');
        }

        if ($request->filled('terms_of_payment')) {
            $query->where('terms_of_payment', $request->terms_of_payment);
        }

        if ($request->filled('supplier')) {
            $query->where('nama_supplier', $request->supplier);
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
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'bank_accounts' => 'required|array|min:1|max:3',
            'bank_accounts.*.bank' => 'required|string|max:255',
            'bank_accounts.*.nama_rekening' => 'required|string|max:255',
            'bank_accounts.*.no_rekening' => 'required|string|max:255',
            'terms_of_payment' => 'nullable|string|max:255',
        ]);

        // Process bank accounts
        $bankAccounts = $validated['bank_accounts'];
        $supplierData = [
            'nama_supplier' => $validated['nama_supplier'],
            'alamat' => $validated['alamat'],
            'email' => $validated['email'],
            'no_telepon' => $validated['no_telepon'],
            'terms_of_payment' => $validated['terms_of_payment'],
        ];

        // Map bank accounts to individual fields
        for ($i = 0; $i < 3; $i++) {
            $index = $i + 1;
            if (isset($bankAccounts[$i])) {
                $supplierData["bank_{$index}"] = $bankAccounts[$i]['bank'];
                $supplierData["nama_rekening_{$index}"] = $bankAccounts[$i]['nama_rekening'];
                $supplierData["no_rekening_{$index}"] = $bankAccounts[$i]['no_rekening'];
            } else {
                $supplierData["bank_{$index}"] = null;
                $supplierData["nama_rekening_{$index}"] = null;
                $supplierData["no_rekening_{$index}"] = null;
            }
        }

        Supplier::create($supplierData);

        return redirect()->route('suppliers.index')
                         ->with('success', 'Data Supplier berhasil ditambahkan');
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
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
            'alamat' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'bank_accounts' => 'required|array|min:1|max:3',
            'bank_accounts.*.bank' => 'required|string|max:255',
            'bank_accounts.*.nama_rekening' => 'required|string|max:255',
            'bank_accounts.*.no_rekening' => 'required|string|max:255',
            'terms_of_payment' => 'nullable|string|max:255',
        ]);

        // Process bank accounts
        $bankAccounts = $validated['bank_accounts'];
        $supplierData = [
            'nama_supplier' => $validated['nama_supplier'],
            'alamat' => $validated['alamat'],
            'email' => $validated['email'],
            'no_telepon' => $validated['no_telepon'],
            'terms_of_payment' => $validated['terms_of_payment'],
        ];

        // Map bank accounts to individual fields
        for ($i = 0; $i < 3; $i++) {
            $index = $i + 1;
            if (isset($bankAccounts[$i])) {
                $supplierData["bank_{$index}"] = $bankAccounts[$i]['bank'];
                $supplierData["nama_rekening_{$index}"] = $bankAccounts[$i]['nama_rekening'];
                $supplierData["no_rekening_{$index}"] = $bankAccounts[$i]['no_rekening'];
            } else {
                $supplierData["bank_{$index}"] = null;
                $supplierData["nama_rekening_{$index}"] = null;
                $supplierData["no_rekening_{$index}"] = null;
            }
        }

        $supplier->update($supplierData);
        return redirect()->route('suppliers.index')
                         ->with('success', 'Data Supplier berhasil diperbarui');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return redirect()->route('suppliers.index')
                         ->with('success', 'Data Supplier berhasil dihapus');
    }
}
