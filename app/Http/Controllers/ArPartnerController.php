<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ArPartner;
use Illuminate\Http\Request;
use App\Http\Requests\StoreArPartnerRequest;
use App\Http\Requests\UpdateArPartnerRequest;

class ArPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = ArPartner::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama_ap', 'like', '%' . $searchTerm . '%')
                  ->orWhere('jenis_ap', 'like', '%' . $searchTerm . '%')
                  ->orWhere('alamat', 'like', '%' . $searchTerm . '%')
                  ->orWhere('no_telepon', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by jenis_ap
        if ($request->filled('jenis_ap')) {
            $query->where('jenis_ap', $request->jenis_ap);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $arPartners = $query->orderByDesc('created_at')->paginate($perPage);

        return Inertia::render('ar-partners/Index', [
            'arPartners' => $arPartners,
            'filters' => [
                'search' => $request->search,
                'jenis_ap' => $request->jenis_ap,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreArPartnerRequest $request)
    {
        try {
            $arPartners = ArPartner::create($request->validated());

            // Redirect kembali ke index dengan pesan sukses
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data AR Partner berhasil ditambahkan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menyimpan data AR Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function show($id)
    {
        $arPartner = ArPartner::findOrFail($id);

        return Inertia::render('ar-partners/Show', [
            'arPartner' => $arPartner
        ]);
    }

    public function update(UpdateArPartnerRequest $request, $id)
    {
        $arPartner = ArPartner::findOrFail($id);

        try {
            $arPartner->update($request->validated());

            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data AR Partner berhasil diperbarui');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal mengupdate data AR Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function destroy($id)
    {
        $arPartner = ArPartner::findOrFail($id);

        try {
            $arPartner->delete();
            return redirect()->route('ar-partners.index')
                           ->with('success', 'Data AR Partner berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Gagal menghapus data AR Partner: ' . $e->getMessage())
                           ->withInput();
        }
    }
}
