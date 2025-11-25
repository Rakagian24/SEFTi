<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BpbItem;
use App\Models\Department;
use App\Models\PengeluaranBarang;
use App\Models\PengeluaranBarangItem;
use App\Models\StockMutation;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Exports\PengeluaranBarangExport;
use Maatwebsite\Excel\Facades\Excel;

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

        $isKepalaToko = $userRole === 'kepala toko';

        // Check if user has permission to access this module
        if (!($isAdmin || $isStaffToko || $isBranchManager || $isKepalaToko)) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke modul ini');
        }

        // Additional department-based restriction: only Human Greatness and Zi&Glo
        // Admin can bypass this restriction and access the module from any department
        if (!$isAdmin) {
            $primaryDepartment = $user ? ($user->department ?: $user->departments->first()) : null;
            $departmentName = optional($primaryDepartment)->name;
            $normalizedDept = $departmentName ? strtolower(trim($departmentName)) : '';
            if (!in_array($normalizedDept, ['human greatness', 'zi&glo'], true)) {
                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke modul ini');
            }
        }

        // Build base query (no default department/date filter; filters applied only from request)
        $query = PengeluaranBarang::with(['department', 'createdBy']);

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

    public function exportExcel(Request $request)
    {
        $ids = $request->input('ids');
        $filters = $request->all();

        $dateFilter = $request->input('date');
        if (is_array($dateFilter) && count($dateFilter) === 2) {
            $dateLabel = $dateFilter[0] . '_to_' . $dateFilter[1];
        } else {
            $dateLabel = Carbon::today()->toDateString();
        }

        $departmentPart = '';
        $departmentId = $request->input('department_id');
        if ($departmentId) {
            $department = Department::find($departmentId);
            if ($department) {
                $departmentSlug = str_replace(' ', '_', $department->name);
                $departmentPart = '-' . $departmentSlug;
            }
        }

        $fileName = 'pengeluaran_barang-' . $dateLabel . $departmentPart . '.xlsx';

        return Excel::download(new PengeluaranBarangExport($ids, $filters), $fileName);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            return back()->with('error', 'Tidak ada data pengeluaran barang yang dipilih untuk dihapus');
        }

        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');
        $isAdmin = $userRole === 'admin';
        $isStaffToko = $userRole === 'staff toko';
        $isBranchManager = $userRole === 'branch manager';
        $isKepalaToko = $userRole === 'kepala toko';

        if (!($isAdmin || $isStaffToko || $isBranchManager || $isKepalaToko)) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menghapus pengeluaran barang');
        }

        // Additional department-based restriction: only Human Greatness and Zi&Glo
        // Admin can bypass this restriction and access the module from any department
        if (!$isAdmin) {
            $primaryDepartment = $user ? ($user->department ?: $user->departments->first()) : null;
            $departmentName = optional($primaryDepartment)->name;
            $normalizedDept = $departmentName ? strtolower(trim($departmentName)) : '';
            if (!in_array($normalizedDept, ['human greatness', 'zi&glo'], true)) {
                return back()->with('error', 'Anda tidak memiliki akses ke modul ini');
            }
        }

        DB::beginTransaction();
        try {
            $records = PengeluaranBarang::whereIn('id', $ids)->get();

            foreach ($records as $pengeluaranBarang) {
                StockMutation::where('referensi', $pengeluaranBarang->no_pengeluaran)->delete();
                $pengeluaranBarang->delete();
            }

            DB::commit();

            return back()->with('success', 'Pengeluaran barang terpilih berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan saat menghapus pengeluaran barang: ' . $e->getMessage());
        }
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
            'jenis_pengeluaran' => 'nullable|in:Produksi,Penjualan,Transfer Gudang,Retur Supplier',
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
            // Generate unique document number using DocumentNumberService
            $department = Department::findOrFail($validated['department_id']);
            // Use explicit alias if available (e.g. HG), fallback to first 2 letters of name
            $departmentAlias = $department->alias ?: substr(strtoupper($department->name), 0, 2);
            $noPengeluaran = DocumentNumberService::generateNumber('Pengeluaran Barang', null, $validated['department_id'], $departmentAlias);

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

        return response()->json($pengeluaranBarang);
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
     *
     * Stock is calculated as:
     *   total BPB qty (Approved, same department, same barang) -
     *   total Pengeluaran Barang qty (same department, same barang)
     */
    private function getAvailableStock($barangId, $departmentId)
    {
        // Total masuk dari BPB Approved
        $totalBpbQty = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->leftJoin('barangs', 'barangs.nama_barang', '=', 'bpb_items.nama_barang')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $departmentId)
            ->where('barangs.id', $barangId)
            ->sum('bpb_items.qty');

        // Total sudah dikeluarkan melalui Pengeluaran Barang
        $totalPengeluaranQty = PengeluaranBarangItem::query()
            ->join('pengeluaran_barang', 'pengeluaran_barang.id', '=', 'pengeluaran_barang_items.pengeluaran_barang_id')
            ->where('pengeluaran_barang.department_id', $departmentId)
            ->where('pengeluaran_barang_items.barang_id', $barangId)
            ->sum('pengeluaran_barang_items.qty');

        return (float) $totalBpbQty - (float) $totalPengeluaranQty;
    }

    /**
     * Generate preview document number for form display
     */
    public function generatePreviewNumber(Request $request)
    {
        $departmentId = $request->get('department_id');
        if (!$departmentId) {
            return response()->json(['error' => 'Department ID is required'], 400);
        }

        $department = Department::findOrFail($departmentId);
        // Use explicit alias if available (e.g. HG), fallback to first 2 letters of name
        $departmentAlias = $department->alias ?: substr(strtoupper($department->name), 0, 2);

        // Use DocumentNumberService to generate a preview number
        $previewNumber = DocumentNumberService::generateFormPreviewNumber('Pengeluaran Barang', null, $departmentId, $departmentAlias);

        return response()->json(['no_pengeluaran' => $previewNumber]);
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

        $query = BpbItem::query()
            ->join('bpbs', 'bpbs.id', '=', 'bpb_items.bpb_id')
            ->leftJoin('barangs', 'barangs.nama_barang', '=', 'bpb_items.nama_barang')
            ->where('bpbs.status', 'Approved')
            ->where('bpbs.department_id', $departmentId)
            ->whereNotNull('barangs.id')
            ->when($search !== '', function ($q) use ($search) {
                $q->where('bpb_items.nama_barang', 'like', "%$search%");
            });

        $rows = $query
            ->selectRaw('barangs.id, bpb_items.nama_barang, bpb_items.satuan, SUM(bpb_items.qty) as stock_qty')
            ->groupBy('barangs.id', 'bpb_items.nama_barang', 'bpb_items.satuan')
            ->orderBy('bpb_items.nama_barang')
            ->paginate($perPage);

        // Map to expected structure for frontend
        $rows->getCollection()->transform(function ($row) use ($departmentId) {
            // Use the same stock logic used for validation:
            // total BPB Approved (masuk) - total Pengeluaran Barang (keluar)
            $availableStock = $this->getAvailableStock($row->id, $departmentId);

            return [
                'id' => $row->id,
                'nama_barang' => $row->nama_barang,
                'stok_tersedia' => (float) $availableStock,
                'satuan' => $row->satuan,
            ];
        });

        return response()->json($rows);
    }
}
