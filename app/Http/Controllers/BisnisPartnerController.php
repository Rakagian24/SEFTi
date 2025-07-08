<?php

namespace App\Http\Controllers;

use App\Models\BisnisPartner;
use App\Http\Requests\StoreBisnisPartnerRequest;
use App\Http\Requests\UpdateBisnisPartnerRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BisnisPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = BisnisPartner::query();

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
        $perPage = $request->filled('per_page') ? $request->per_page : 15;

        // Ensure per_page is within reasonable limits
        $perPage = min(max((int)$perPage, 10), 100);

        $bisnisPartners = $query->orderByDesc('id')->paginate($perPage);

        return Inertia::render('bisnis-partners/Index', [
            'bisnisPartners' => $bisnisPartners,
            'filters' => $request->only(['search', 'nama_bp', 'jenis_bp', 'terms_of_payment', 'per_page'])
        ]);
    }

    public function store(StoreBisnisPartnerRequest $request)
    {
        $bisnisPartner = BisnisPartner::create($request->validated());
        return redirect()->back()->with('success', 'Bisnis Partner berhasil ditambahkan.');
    }

    public function show(BisnisPartner $bisnis_partner)
    {
        return response()->json($bisnis_partner);
    }

    public function update(UpdateBisnisPartnerRequest $request, BisnisPartner $bisnis_partner)
    {
        $bisnis_partner->update($request->validated());
        return redirect()->back()->with('success', 'Bisnis Partner berhasil diupdate.');
    }

    public function destroy(BisnisPartner $bisnis_partner)
    {
        $bisnis_partner->update(['status' => 'batal']);
        return redirect()->back()->with('success', 'Bisnis Partner dibatalkan.');
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
