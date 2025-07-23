<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\Department;
use App\Models\ArPartner;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\BankMasukLog;

class BankMasukController extends Controller
{
    public function index(Request $request)
    {
        // Filter dinamis
        $query = BankMasuk::query()->with('bankAccount')->where('status', 'aktif');

        // Filter lain
        if ($request->filled('no_bm')) {
            $query->where('no_bm', 'like', '%' . $request->no_bm . '%');
        }
        if ($request->filled('no_pv')) {
            $query->where('purchase_order_id', $request->no_pv); // Placeholder, sesuaikan jika ada relasi PV
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('supplier_id')) {
            $query->whereHas('arPartner', function($q) use ($request) {
                $q->where('id', $request->supplier_id);
            });
        }

        // Search bebas
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('no_bm', 'like', "%$search%")
                  ->orWhere('purchase_order_id', 'like', "%$search%")
                  ->orWhere('tanggal', 'like', "%$search%")
                  ->orWhere('note', 'like', "%$search%")
                  ->orWhere('nilai', 'like', "%$search%")
                  ->orWhereHas('bankAccount', function($q2) use ($search) {
                      $q2->where('nama_pemilik', 'like', "%$search%")
                         ->orWhere('no_rekening', 'like', "%$search%");
                  })
                  ->orWhereHas('arPartner', function($q2) use ($search) {
                      $q2->where('nama_ap', 'like', "%$search%");
                  });
            });
        }

        // Rows per page (support entriesPerPage dari frontend)
        $perPage = $request->input('per_page', $request->input('entriesPerPage', 10));
        $bankMasuks = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        // Data filter dinamis
        $departments = Department::orderBy('name')->get();
        $arPartners = ArPartner::orderBy('nama_ap')->get();
        $bankAccounts = BankAccount::orderBy('no_rekening')->get();

        return Inertia::render('bank-masuk/Index', [
            'bankMasuks' => $bankMasuks,
            'departments' => $departments,
            'arPartners' => $arPartners,
            'bankAccounts' => $bankAccounts,
            'filters' => $request->all(),
        ]);
    }

    public function create()
    {
        // Data master
        $bankAccounts = BankAccount::with('department')->orderBy('no_rekening')->get();

        // Tidak perlu generate no_bm di sini, frontend akan handle
        return Inertia::render('bank-masuk/Form', [
            'bankAccounts' => $bankAccounts,
            'no_bm' => '',
            'default_tipe_po' => 'Reguler',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'terima_dari' => 'required|in:Customer,Karyawan,Penjualan Toko,Lainnya',
            'nilai' => 'required|numeric|min:0',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'note' => 'nullable|string',
            'purchase_order_id' => 'nullable|integer',
            'input_lainnya' => 'nullable|string',
        ], [
            'tanggal.required' => 'Tanggal wajib diisi',
            'tipe_po.required' => 'Tipe PO wajib diisi',
            'terima_dari.required' => 'Terima Dari wajib diisi',
            'nilai.required' => 'Nominal wajib diisi',
            'nilai.numeric' => 'Nominal harus berupa angka',
            'bank_account_id.required' => 'Rekening wajib diisi',
            'bank_account_id.exists' => 'Rekening tidak valid',
        ]);

        // Generate no BM otomatis sesuai format baru
        $bankAccount = \App\Models\BankAccount::find($validated['bank_account_id']);
        $namaBank = $bankAccount ? $bankAccount->nama_pemilik : 'XXX';
        $dt = \Carbon\Carbon::parse($validated['tanggal']);
        $bulanRomawi = $this->bulanRomawi($dt->format('n'));
        $tahun = $dt->format('Y');
        // Hitung nomor urut untuk bulan-tahun
        $like = "BM/%/{$bulanRomawi}-{$tahun}/%";
        $count = \App\Models\BankMasuk::where('no_bm', 'like', $like)->count();
        $autoNum = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $validated['no_bm'] = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$autoNum}";
        $validated['status'] = 'aktif';
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $bankMasuk = BankMasuk::create($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'create',
            'description' => 'Membuat dokumen Bank Masuk',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil disimpan.');
    }

    public function show(BankMasuk $bankMasuk)
    {
        $bankMasuk->load(['bankAccount', 'creator', 'updater']);
        return Inertia::render('bank-masuk/Detail', [
            'bankMasuk' => $bankMasuk,
        ]);
    }

    public function edit(BankMasuk $bankMasuk)
    {
        $bankAccounts = BankAccount::with('department')->orderBy('no_rekening')->get();
        return Inertia::render('bank-masuk/Form', [
            'bankMasuk' => $bankMasuk,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function update(Request $request, BankMasuk $bankMasuk)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_po' => 'required|in:Reguler,Anggaran,Lainnya',
            'terima_dari' => 'required|in:Customer,Karyawan,Penjualan Toko,Lainnya',
            'nilai' => 'required|numeric|min:0',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'note' => 'nullable|string',
            'purchase_order_id' => 'nullable|integer',
            'input_lainnya' => 'nullable|string',
        ], [
            'tanggal.required' => 'Tanggal wajib diisi',
            'tipe_po.required' => 'Tipe PO wajib diisi',
            'terima_dari.required' => 'Terima Dari wajib diisi',
            'nilai.required' => 'Nominal wajib diisi',
            'nilai.numeric' => 'Nominal harus berupa angka',
            'bank_account_id.required' => 'Rekening wajib diisi',
            'bank_account_id.exists' => 'Rekening tidak valid',
        ]);
        $validated['updated_by'] = Auth::id();
        $bankMasuk->update($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'update',
            'description' => 'Mengupdate dokumen Bank Masuk',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil diupdate.');
    }

    public function destroy(BankMasuk $bankMasuk)
    {
        $bankMasuk->update(['status' => 'batal', 'updated_by' => Auth::id()]);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'cancel',
            'description' => 'Membatalkan dokumen Bank Masuk',
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk dibatalkan.');
    }

    public function download(BankMasuk $bankMasuk)
    {
        // TODO: Implementasi download PDF/Excel
        return response()->json(['message' => 'Fitur download belum tersedia.']);
    }

    public function log(BankMasuk $bankMasuk)
    {
        // Cek apakah tabel log sudah ada
        if (Schema::hasTable('bank_masuk_logs')) {
            $logs = DB::table('bank_masuk_logs')
                ->where('bank_masuk_id', $bankMasuk->id)
                ->leftJoin('users', 'bank_masuk_logs.user_id', '=', 'users.id')
                ->select('bank_masuk_logs.*', 'users.name as user_name')
                ->orderByDesc('bank_masuk_logs.created_at')
                ->get();
        } else {
            $logs = [];
        }
        return Inertia::render('bank-masuk/Log', [
            'bankMasukLog' => $logs,
            'bankMasuk' => $bankMasuk,
        ]);
    }

    public function getNextNumber(Request $request)
    {
        $bank_account_id = $request->query('bank_account_id');
        $tanggal = $request->query('tanggal');
        $namaBank = 'XXX';
        if ($bank_account_id) {
            $bankAccount = \App\Models\BankAccount::find($bank_account_id);
            if ($bankAccount) {
                $namaBank = $bankAccount->nama_pemilik;
            }
        }
        $bulanRomawi = '';
        $tahun = '';
        if ($tanggal) {
            $dt = \Carbon\Carbon::parse($tanggal);
            $bulanRomawi = $this->bulanRomawi($dt->format('n'));
            $tahun = $dt->format('Y');
        }
        $like = "BM/%/{$bulanRomawi}-{$tahun}/%";
        $count = \App\Models\BankMasuk::where('no_bm', 'like', $like)->count();
        $autoNum = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $no_bm = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$autoNum}";
        return response()->json(['no_bm' => $no_bm]);
    }

    // Fungsi bantu bulan ke romawi
    private function bulanRomawi($bulan) {
        $romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romawi[intval($bulan)] ?? $bulan;
    }
}
