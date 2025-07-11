<?php

namespace App\Http\Controllers;

use App\Models\BisnisPartner;
use App\Models\Bank;
use App\Http\Requests\StoreBisnisPartnerRequest;
use App\Http\Requests\UpdateBisnisPartnerRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BisnisPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = BisnisPartner::with('bank');

        // Handle search parameter (general search across nama_bp)
        if ($request->filled('search')) {
            $query->where('nama_bp', 'like', '%'.$request->search.'%');
        }

        // Handle legacy nama_bp parameter (for backward compatibility)
        if ($request->filled('nama_bp')) {
            $query->where('nama_bp', 'like', '%'.$request->nama_bp.'%');
        }

        // Handle jenis_bp filter
        if ($request->filled('jenis_bp')) {
            $query->where('jenis_bp', $request->jenis_bp);
        }

        // Handle terms_of_payment filter
        if ($request->filled('terms_of_payment')) {
            $query->where('terms_of_payment', $request->terms_of_payment);
        }

        // Handle per_page parameter for pagination
        $perPage = $request->filled('per_page') ? $request->per_page : 10;

        // Ensure per_page is within reasonable limits
        $perPage = min(max((int)$perPage, 10), 100);

        $bisnisPartners = $query->orderByDesc('id')->paginate($perPage);

        // Ambil data banks untuk dropdown
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        return Inertia::render('bisnis-partners/Index', [
            'bisnisPartners' => $bisnisPartners,
            'filters' => $request->only(['search', 'nama_bp', 'jenis_bp', 'terms_of_payment', 'per_page']),
            'banks' => $banks
        ]);
    }

    public function create()
    {
        // Ambil data banks untuk dropdown
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        return Inertia::render('bisnis-partners/Create', [
            'banks' => $banks
        ]);
    }

    public function store(StoreBisnisPartnerRequest $request)
    {
        try {
            $bisnisPartner = BisnisPartner::create($request->validated());
            return redirect()->back()->with('success', 'Bisnis Partner berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menyimpan data Bisnis Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function show(BisnisPartner $bisnis_partner)
    {
        // Load the bank relationship
        $bisnis_partner->load('bank');
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();
        return Inertia::render('bisnis-partners/Detail', [
            'bisnisPartner' => $bisnis_partner,
            'banks' => $banks
        ]);
    }

    public function edit(BisnisPartner $bisnis_partner)
    {
        // Ambil data banks untuk dropdown
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        return Inertia::render('bisnis-partners/Edit', [
            'bisnisPartner' => $bisnis_partner->load('bank'),
            'banks' => $banks
        ]);
    }

    public function update(UpdateBisnisPartnerRequest $request, BisnisPartner $bisnis_partner)
    {
        try {
            $bisnis_partner->update($request->validated());
            return redirect()->back()->with('success', 'Bisnis Partner berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal mengupdate data Bisnis Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function destroy(BisnisPartner $bisnis_partner)
    {
        try {
            $bisnis_partner->update(['status' => 'batal']);
            return redirect()->back()->with('success', 'Bisnis Partner berhasil dibatalkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal membatalkan Bisnis Partner: ' . $e->getMessage());
        }
    }

    public function log(BisnisPartner $bisnis_partner)
    {
        // Dummy log, replace with actual log if available
        $logs = [
            ['action' => 'created', 'at' => $bisnis_partner->created_at],
            ['action' => 'updated', 'at' => $bisnis_partner->updated_at],
        ];
        return response()->json($logs);
    }
}
