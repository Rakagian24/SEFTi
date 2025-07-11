<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\BankAccount;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::with('bank');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama_pemilik', 'like', '%' . $searchTerm . '%')
                  ->orWhere('no_rekening', 'like', '%' . $searchTerm . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $bankAccounts = $query->orderByDesc('created_at')->paginate($perPage);

        $banks = Bank::where('status', 'active')->get(['id', 'nama_bank', 'status']);

        return Inertia::render('bank-accounts/Index', [
            'bankAccounts' => $bankAccounts,
            'banks' => $banks,
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
            'nama_pemilik' => 'required|string|max:255',
            'no_rekening' => 'required|string|max:255',
            'bank_id' => 'required|exists:banks,id',
            'status' => 'required|in:active,inactive',
        ]);

        BankAccount::create($validated);

        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Data Bank Account berhasil ditambahkan');
    }

    public function show($id)
    {
        $bankAccount = BankAccount::with('bank')->findOrFail($id);
        return Inertia::render('bank-accounts/Show', [
            'bankAccount' => $bankAccount
        ]);
    }

    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        $validated = $request->validate([
            'nama_pemilik' => 'required|string|max:255',
            'no_rekening' => 'required|string|max:255',
            'bank_id' => 'required|exists:banks,id',
            'status' => 'required|in:active,inactive',
        ]);
        $bankAccount->update($validated);
        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Data Bank Account berhasil diperbarui');
    }

    public function destroy($id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->delete();
        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Data Bank Account berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->status = $bankAccount->status === 'active' ? 'inactive' : 'active';
        $bankAccount->save();

        return redirect()->route('bank-accounts.index')
                         ->with('success', 'Status Bank Account berhasil diperbarui');
    }
}
