<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\CreditCard;
use App\Models\CreditCardLog;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Auth;

class CreditCardController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditCard::with(['department', 'bank']);

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

        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
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
            'bank_id' => ['required', 'exists:banks,id'],
            'no_kartu_kredit' => ['required', 'string', 'max:64', 'regex:/^[\d\s]+$/'],
            'nama_pemilik' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Remove spaces from card number before saving
        $validated['no_kartu_kredit'] = preg_replace('/\s+/', '', $validated['no_kartu_kredit']);

        $creditCard = CreditCard::create($validated);

        // Log activity
        CreditCardLog::create([
            'credit_card_id' => $creditCard->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Membuat data Kartu Kredit ' . ($creditCard->no_kartu_kredit ?? ''),
            'ip_address' => $request->ip(),
        ]);
        return redirect()->back()->with('success', 'Kartu Kredit berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $creditCard = CreditCard::findOrFail($id);
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'bank_id' => ['required', 'exists:banks,id'],
            'no_kartu_kredit' => ['required', 'string', 'max:64', 'regex:/^[\d\s]+$/'],
            'nama_pemilik' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Remove spaces from card number before saving
        $validated['no_kartu_kredit'] = preg_replace('/\s+/', '', $validated['no_kartu_kredit']);

        $creditCard->update($validated);

        // Log activity
        CreditCardLog::create([
            'credit_card_id' => $creditCard->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => 'Mengubah data Kartu Kredit ' . ($creditCard->no_kartu_kredit ?? ''),
            'ip_address' => $request->ip(),
        ]);
        return redirect()->back()->with('success', 'Kartu Kredit berhasil diperbarui');
    }

    public function destroy($id)
    {
        $creditCard = CreditCard::findOrFail($id);
        $creditCard->delete();

        // Log activity
        CreditCardLog::create([
            'credit_card_id' => $creditCard->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'description' => 'Menghapus data Kartu Kredit ' . ($creditCard->no_kartu_kredit ?? ''),
            'ip_address' => request()->ip(),
        ]);
        return redirect()->back()->with('success', 'Kartu Kredit berhasil dihapus');
    }

    public function toggleStatus($id)
    {
        try {
            $creditCard = CreditCard::findOrFail($id);
            $creditCard->status = $creditCard->status === 'active' ? 'inactive' : 'active';
            $creditCard->save();

            // Log activity
            CreditCardLog::create([
                'credit_card_id' => $creditCard->id,
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Mengubah status Kartu Kredit ' . ($creditCard->no_kartu_kredit ?? '') . ' menjadi ' . $creditCard->status,
                'ip_address' => request()->ip(),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status Kartu Kredit berhasil diperbarui',
                    'status' => $creditCard->status
                ]);
            }

            return redirect()->back()->with('success', 'Status Kartu Kredit berhasil diperbarui');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui status Kartu Kredit'
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal memperbarui status Kartu Kredit');
        }
    }

    public function logs(CreditCard $credit_card, Request $request)
    {
        // No DepartmentScope on the main entity for log view
        $credit_card = \App\Models\CreditCard::withoutGlobalScopes()->findOrFail($credit_card->id);

        $logs = CreditCardLog::with(['user.department', 'user.role'])
            ->where('credit_card_id', $credit_card->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 10));

        $roleOptions = \App\Models\Role::select('id', 'name')->orderBy('name')->get();
        $departmentOptions = DepartmentService::getOptionsForFilter();
        $actionOptions = CreditCardLog::where('credit_card_id', $credit_card->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('credit-cards/Log', [
            'creditCard' => $credit_card->load(['bank', 'department']),
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'date', 'per_page']),
            'roleOptions' => $roleOptions,
            'departmentOptions' => $departmentOptions,
            'actionOptions' => $actionOptions,
        ]);
    }
}

