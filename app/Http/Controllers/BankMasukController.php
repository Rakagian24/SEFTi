<?php

namespace App\Http\Controllers;

use App\Models\BankMasuk;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\BankMasukLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankMasukController extends Controller
{
    public function index(Request $request)
    {
        // Filter dinamis
        $query = BankMasuk::query()->with(['bankAccount.bank'])->where('status', 'aktif');

        // Filter lain
        if ($request->filled('no_bm')) {
            $query->where('no_bm', 'like', '%' . $request->no_bm . '%');
        }
        if ($request->filled('no_pv')) {
            $query->where('purchase_order_id', $request->no_pv); // Placeholder, sesuaikan jika ada relasi PV
        }
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }
        if ($request->filled('terima_dari')) {
            $query->where('terima_dari', $request->terima_dari);
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
                  });
            });
        }
        // Filter rentang tanggal
        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('tanggal', [$request->start, $request->end]);
        } elseif ($request->filled('start')) {
            $query->where('tanggal', '>=', $request->start);
        } elseif ($request->filled('end')) {
            $query->where('tanggal', '<=', $request->end);
        }

        // Sorting
        $sortBy = $request->input('sortBy');
        $sortDirection = $request->input('sortDirection', 'asc');
        $allowedSorts = ['no_bm', 'purchase_order_id', 'tanggal', 'note', 'nilai', 'created_at'];
        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderByDesc('created_at');
        }
        // Rows per page (support entriesPerPage dari frontend)
        $perPage = $request->input('per_page', $request->input('entriesPerPage', 10));
        $bankMasuks = $query->paginate($perPage)->withQueryString();

        // Data filter dinamis
        $bankAccounts = BankAccount::with('bank')->where('status', 'active')->orderBy('no_rekening')->get();

        return Inertia::render('bank-masuk/Index', [
            'bankMasuks' => $bankMasuks,
            'bankAccounts' => $bankAccounts,
            'filters' => $request->all(),
        ]);
    }

    public function create()
    {
        // Data master
        $bankAccounts = BankAccount::with('bank')->where('status', 'active')->orderBy('no_rekening')->get();

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
        $bankMasuk->load(['bankAccount.bank', 'creator', 'updater']);
        $bankAccounts = BankAccount::with('bank')->where('status', 'active')->orderBy('no_rekening')->get();
        return Inertia::render('bank-masuk/Detail', [
            'bankMasuk' => $bankMasuk,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function edit(BankMasuk $bankMasuk)
    {
        $bankAccounts = BankAccount::with('bank')->where('status', 'active')->orderBy('no_rekening')->get();
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

        // Check if bank_account_id or tanggal has changed
        $shouldRegenerateNoBm = $bankMasuk->bank_account_id != $validated['bank_account_id'] ||
                               $bankMasuk->tanggal != $validated['tanggal'];

        if ($shouldRegenerateNoBm) {
            // Generate new no_bm
            $bankAccount = \App\Models\BankAccount::find($validated['bank_account_id']);
            $namaBank = $bankAccount ? $bankAccount->nama_pemilik : 'XXX';
            $dt = \Carbon\Carbon::parse($validated['tanggal']);
            $bulanRomawi = $this->bulanRomawi($dt->format('n'));
            $tahun = $dt->format('Y');
            // Hitung nomor urut untuk bulan-tahun, exclude current record
            $like = "BM/%/{$bulanRomawi}-{$tahun}/%";
            $count = \App\Models\BankMasuk::where('no_bm', 'like', $like)
                ->where('id', '!=', $bankMasuk->id)
                ->count();
            $autoNum = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $validated['no_bm'] = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$autoNum}";
        }

        $validated['updated_by'] = Auth::id();
        $bankMasuk->update($validated);

        // Log aktivitas
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'update',
            'description' => 'Mengupdate dokumen Bank Masuk' . ($shouldRegenerateNoBm ? ' (dengan perubahan nomor BM)' : ''),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil diupdate.');
    }

    public function destroy(BankMasuk $bankMasuk)
    {
        // Log aktivitas sebelum dihapus
        BankMasukLog::create([
            'bank_masuk_id' => $bankMasuk->id,
            'user_id' => Auth::id(),
            'action' => 'delete',
            'description' => 'Menghapus dokumen Bank Masuk',
            'ip_address' => request()->ip(),
        ]);

        // Hapus data secara permanen
        $bankMasuk->delete();

        return redirect()->route('bank-masuk.index')->with('success', 'Bank Masuk berhasil dihapus.');
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
        $exclude_id = $request->query('exclude_id'); // Untuk mode edit
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
        $query = \App\Models\BankMasuk::where('no_bm', 'like', $like);

        // Exclude current record if editing
        if ($exclude_id) {
            $query->where('id', '!=', $exclude_id);
        }

        $count = $query->count();
        $autoNum = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $no_bm = "BM/{$namaBank}/{$bulanRomawi}-{$tahun}/{$autoNum}";
        return response()->json(['no_bm' => $no_bm]);
    }

    public function exportExcel(Request $request)
    {
        $ids = $request->input('ids', []);
        $fields = $request->input('fields', []);
        if (empty($ids) || empty($fields)) {
            return response()->json(['message' => 'Pilih data dan kolom yang ingin diexport.'], 422);
        }
        $query = BankMasuk::with(['bankAccount', 'creator', 'updater'])
            ->whereIn('id', $ids);
        $rows = $query->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bank_masuk_export.csv"',
        ];
        $callback = function() use ($rows, $fields) {
            $out = fopen('php://output', 'w');
            // Header
            fputcsv($out, $fields);
            foreach ($rows as $row) {
                $data = [];
                foreach ($fields as $field) {
                    switch ($field) {
                        case 'bank_account':
                            $data[] = $row->bankAccount ? $row->bankAccount->nama_pemilik : '';
                            break;
                        case 'no_rekening':
                            $data[] = $row->bankAccount ? $row->bankAccount->no_rekening : '';
                            break;
                        case 'created_by':
                            $data[] = $row->creator ? $row->creator->name : '';
                            break;
                        case 'updated_by':
                            $data[] = $row->updater ? $row->updater->name : '';
                            break;
                        default:
                            $data[] = $row->{$field} ?? '';
                    }
                }
                fputcsv($out, $data);
            }
            fclose($out);
        };
        return new StreamedResponse($callback, 200, $headers);
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
