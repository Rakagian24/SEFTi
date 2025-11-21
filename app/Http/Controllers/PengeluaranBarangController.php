<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Department;
use App\Models\PengeluaranBarang;
use App\Models\PengeluaranBarangItem;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class PengeluaranBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');
        $isAdmin = $userRole === 'admin';
        $isStaffToko = $userRole === 'staff toko';
        $isBranchManager = $userRole === 'branch manager';

        // Check if user has permission to access this module
        if (!($isAdmin || $isStaffToko || $isBranchManager)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke modul ini');
        }

        // Build base query
        $query = PengeluaranBarang::with(['department', 'createdBy']);

        // Filter by department if user only has one department
        $userDepartments = $user->departments;
        if ($userDepartments->count() === 1) {
            $departmentId = $userDepartments->first()->id;
            $query->where('department_id', $departmentId);

            // Default filter to current month for single department users
            if (!$request->filled('date')) {
                $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');
                $query->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);
            }
        }

        // Apply filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('no_pengeluaran', 'like', "%$search%")
                  ->orWhereHas('department', function ($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  })
                  ->orWhereHas('createdBy', function ($q) use ($search) {
                      $q->where('name', 'like', "%$search%");
                  });
            });
        }

        if ($departmentId = $request->get('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($jenisPengeluaran = $request->get('jenis_pengeluaran')) {
            $query->where('jenis_pengeluaran', $jenisPengeluaran);
        }

        // Date range filter
        $date = $request->get('date');
        if ($date && is_array($date) && count($date) === 2) {
            $query->whereBetween('tanggal', [$date[0], $date[1]]);
        }

        $perPage = (int)($request->get('per_page') ?? 10);
        $data = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('pengeluaran-barang/Index', [
            'pengeluaranBarang' => $data,
            'filters' => $request->all(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'jenisPengeluaran' => [
                ['id' => 'Produksi', 'name' => 'Produksi'],
                ['id' => 'Penjualan', 'name' => 'Penjualan'],
                ['id' => 'Transfer Gudang', 'name' => 'Transfer Gudang'],
                ['id' => 'Retur Supplier', 'name' => 'Retur Supplier'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $userDepartments = $user->departments;

        return Inertia::render('pengeluaran-barang/Create', [
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'jenisPengeluaran' => [
                ['id' => 'Produksi', 'name' => 'Produksi'],
                ['id' => 'Penjualan', 'name' => 'Penjualan'],
                ['id' => 'Transfer Gudang', 'name' => 'Transfer Gudang'],
                ['id' => 'Retur Supplier', 'name' => 'Retur Supplier'],
            ],
            'userDepartments' => $userDepartments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'jenis_pengeluaran' => 'required|in:Produksi,Penjualan,Transfer Gudang,Retur Supplier',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.keterangan' => 'nullable|string',
        ]);

        // Check stock availability for each item
        foreach ($validated['items'] as $item) {
            $barang = Barang::findOrFail($item['barang_id']);
            $availableStock = $this->getAvailableStock($barang->id, $validated['department_id']);

            if ($item['qty'] > $availableStock) {
                return back()->withErrors([
                    'items' => "Stok tidak mencukupi untuk barang {$barang->nama_barang}. Stok tersedia: {$availableStock}"
                ]);
            }
        }

        DB::beginTransaction();
        try {
            // Generate unique document number
            $noPengeluaran = $this->generateNoPengeluaran($validated['department_id']);

            // Create pengeluaran barang header
            $pengeluaranBarang = PengeluaranBarang::create([
                'no_pengeluaran' => $noPengeluaran,
                'tanggal' => $validated['tanggal'],
                'department_id' => $validated['department_id'],
                'jenis_pengeluaran' => $validated['jenis_pengeluaran'],
                'keterangan' => $validated['keterangan'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Create pengeluaran barang items
            foreach ($validated['items'] as $item) {
                $pengeluaranItem = PengeluaranBarangItem::create([
                    'pengeluaran_barang_id' => $pengeluaranBarang->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'keterangan' => $item['keterangan'] ?? null,
                ]);

                // Create stock mutation (decrease stock)
                StockMutation::create([
                    'tanggal' => $validated['tanggal'],
                    'barang_id' => $item['barang_id'],
                    'department_id' => $validated['department_id'],
                    'qty' => -$item['qty'], // Negative for outgoing stock
                    'referensi' => $noPengeluaran,
                    'keterangan' => 'Pengeluaran Barang: ' . $validated['jenis_pengeluaran'],
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();
            return redirect()->route('pengeluaran-barang.index')->with('success', 'Pengeluaran barang berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pengeluaranBarang = PengeluaranBarang::with(['department', 'createdBy', 'items.barang'])->findOrFail($id);

        return Inertia::render('pengeluaran-barang/Show', [
            'pengeluaranBarang' => $pengeluaranBarang
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pengeluaranBarang = PengeluaranBarang::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete related stock mutations
            StockMutation::where('referensi', $pengeluaranBarang->no_pengeluaran)->delete();

            // Delete pengeluaran barang and its items (cascade delete will handle items)
            $pengeluaranBarang->delete();

            DB::commit();
            return redirect()->route('pengeluaran-barang.index')->with('success', 'Pengeluaran barang berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Get available stock for a specific barang in a department
     */
    private function getAvailableStock($barangId, $departmentId)
    {
        return StockMutation::where('barang_id', $barangId)
            ->where('department_id', $departmentId)
            ->sum('qty');
    }

    /**
     * Generate unique document number for pengeluaran barang
     */
    private function generateNoPengeluaran($departmentId)
    {
        $department = Department::findOrFail($departmentId);
        $departmentCode = substr(strtoupper($department->name), 0, 2);

        $now = Carbon::now();
        $month = $this->getRomanMonth($now->month);
        $year = $now->year;

        // Get last number in the sequence for this department, month and year
        $lastPengeluaran = PengeluaranBarang::where('department_id', $departmentId)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $now->month)
            ->orderBy('no_pengeluaran', 'desc')
            ->first();

        $sequence = 1;
        if ($lastPengeluaran) {
            $parts = explode('/', $lastPengeluaran->no_pengeluaran);
            if (count($parts) === 5) {
                $sequence = (int)$parts[4] + 1;
            }
        }

        return sprintf("PB/%s/%s/%s/%04d", $departmentCode, $month, $year, $sequence);
    }

    /**
     * Convert month number to Roman numeral
     */
    private function getRomanMonth($month)
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romans[$month] ?? 'I';
    }

    /**
     * Get barang data with available stock
     */
    public function getBarangWithStock(Request $request)
    {
        $departmentId = $request->get('department_id');
        if (!$departmentId) {
            return response()->json(['error' => 'Department ID is required'], 400);
        }

        $search = $request->get('search', '');
        $perPage = (int)($request->get('per_page', 10));

        $query = Barang::query()
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%$search%")
                  ->orWhere('kode_barang', 'like', "%$search%");
            });

        $barangs = $query->paginate($perPage);

        // Add available stock information
        $barangsWithStock = $barangs->through(function ($barang) use ($departmentId) {
            $availableStock = $this->getAvailableStock($barang->id, $departmentId);
            $barang->stok_tersedia = $availableStock;
            return $barang;
        });

        return response()->json($barangsWithStock);
    }
}
