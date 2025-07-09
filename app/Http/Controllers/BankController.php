<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Bank::query();

        // Search functionality - sesuai dengan yang digunakan di frontend
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama_bank', 'like', '%' . $searchTerm . '%')
                  ->orWhere('kode_bank', 'like', '%' . $searchTerm . '%')
                  ->orWhere('singkatan', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by status jika diperlukan
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pagination dengan per_page yang bisa diatur
        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $banks = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('banks/Index', [
            'Banks' => $banks, // Sesuai dengan props di Vue component
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBankRequest $request)
    {
        try {
            $bank = Bank::create($request->validated());

            // Redirect kembali ke index dengan pesan sukses
            return redirect()->route('banks.index')
                           ->with('success', 'Data bank berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menyimpan data bank: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bank = Bank::findOrFail($id);

        return Inertia::render('banks/Show', [
            'bank' => $bank
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBankRequest $request, $id)
    {
        $bank = Bank::findOrFail($id);

        try {
            $bank->update($request->validated());

            return redirect()->route('banks.index')
                           ->with('success', 'Data bank berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal mengupdate data bank: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage (soft delete: set status non-active).
     */
    public function destroy($id)
    {
        $bank = Bank::findOrFail($id);

        try {
            // Soft delete dengan mengubah status menjadi non-active
            $bank->update(['status' => 'Non-Active']);

            return redirect()->route('banks.index')
                           ->with('success', 'Bank berhasil dinonaktifkan');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menonaktifkan bank: ' . $e->getMessage());
        }
    }

    /**
     * Show log activity for specific bank.
     */
    public function logs($id)
    {
        $bank = Bank::findOrFail($id);

        // Dummy log data - ganti dengan implementasi log yang sebenarnya
        $logs = [
            [
                'id' => 1,
                'activity' => 'Created',
                'description' => 'Bank created by user',
                'user' => 'Admin',
                'created_at' => now()->subDays(3)->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'activity' => 'Updated',
                'description' => 'Bank information updated',
                'user' => 'Admin',
                'created_at' => now()->subDay()->format('Y-m-d H:i:s'),
            ],
        ];

        return Inertia::render('banks/Logs', [
            'bank' => $bank,
            'logs' => $logs
        ]);
    }
}
