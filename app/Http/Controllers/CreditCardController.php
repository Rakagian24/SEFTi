<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\CreditCard;
use App\Models\Department;
use App\Services\DepartmentService;

class CreditCardController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditCard::with(['department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('no_kartu_kredit', 'like', "%$search%")
                  ->orWhere('nama_pemilik', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('id', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $perPage = $request->integer('per_page', 10);
        $creditCards = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($creditCards);
        }

        $departments = DepartmentService::getOptionsForFilter();

        return Inertia::render('credit-cards/Index', [
            'creditCards' => $creditCards,
            'departments' => $departments,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'department_id' => $request->department_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'no_kartu_kredit' => ['required', 'string', 'max:64'],
            'nama_pemilik' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        CreditCard::create($validated);
        return redirect()->back()->with('success', 'Kartu Kredit berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $creditCard = CreditCard::findOrFail($id);
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'no_kartu_kredit' => ['required', 'string', 'max:64'],
            'nama_pemilik' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);
        $creditCard->update($validated);
        return redirect()->back()->with('success', 'Kartu Kredit berhasil diperbarui');
    }

    public function destroy($id)
    {
        $creditCard = CreditCard::findOrFail($id);
        $creditCard->delete();
        return redirect()->back()->with('success', 'Kartu Kredit berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        $creditCard = CreditCard::findOrFail($id);
        $creditCard->status = $creditCard->status === 'active' ? 'inactive' : 'active';
        $creditCard->save();
        return redirect()->back()->with('success', 'Status Kartu Kredit berhasil diperbarui');
    }
}

