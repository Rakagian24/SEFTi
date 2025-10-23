<?php

namespace App\Http\Controllers;

use App\Models\Realisasi;
use App\Models\RealisasiItem;
use App\Models\RealisasiLog;
use App\Models\Department;
use App\Models\PoAnggaran;
use App\Services\DocumentNumberService;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class RealisasiController extends Controller
{
    protected ApprovalWorkflowService $workflow;

    public function __construct(ApprovalWorkflowService $workflow)
    {
        $this->workflow = $workflow;
    }
    public function poAnggaranOptions(Request $request)
    {
        $q = PoAnggaran::query()
            ->select('id', 'no_po_anggaran', 'department_id', 'nominal', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(100);

        if ($request->filled('department_id')) {
            $q->where('department_id', $request->get('department_id'));
        }

        return response()->json($q->get());
    }

    public function poAnggaranDetail(PoAnggaran $po_anggaran)
    {
        $po_anggaran->load(['items', 'department', 'bank']);
        return response()->json($po_anggaran);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');

        $query = Realisasi::query()->with(['department','poAnggaran']);

        // Staff Toko & Staff Digital Marketing: only see own-created
        if (in_array($userRole, ['staff toko','staff digital marketing'], true)) {
            $query->where('created_by', $user->id);
        }

        // Filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('no_realisasi', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('total_anggaran', 'like', "%$search%")
                  ->orWhere('total_realisasi', 'like', "%$search%");
            });
        }
        if ($no = $request->get('no_realisasi')) {
            $query->where('no_realisasi', 'like', "%$no%");
        }
        if ($status = $request->get('status')) {
            $query->whereIn('status', (array)$status);
        }
        if ($dept = $request->get('department_id')) {
            $query->where('department_id', $dept);
        }
        // Default to current month if no date filter
        $date = $request->get('date');
        if ($date && is_array($date) && count($date) === 2) {
            $query->whereBetween('tanggal', [$date[0], $date[1]]);
        } else {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        }

        $perPage = (int)($request->get('per_page') ?? 10);
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('realisasi/Index', [
            'realisasis' => $data,
            'filters' => $request->all(),
            'departments' => Department::select('id','name')->orderBy('name')->get(),
            'columns' => [
                ['key' => 'no_realisasi', 'label' => 'No. Realisasi', 'checked' => true, 'sortable' => true],
                ['key' => 'no_po_anggaran', 'label' => 'No. PO Anggaran', 'checked' => true],
                ['key' => 'department', 'label' => 'Departemen', 'checked' => true],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'checked' => true, 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'checked' => true, 'sortable' => true],
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('realisasi/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_anggaran_id' => 'required|exists:po_anggarans,id',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'required|in:Transfer',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'required|string',
            'no_rekening' => 'required|string',
            'total_anggaran' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
            'items.*.realisasi' => 'required|numeric|min:0',
        ]);

        $realisasi = new Realisasi($validated);
        $realisasi->status = 'Draft';
        $realisasi->created_by = Auth::id();
        // total_realisasi dari items
        $realisasi->total_realisasi = collect($validated['items'] ?? [])->sum(function ($it) { return (float)($it['realisasi'] ?? 0); });
        $realisasi->save();

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $subtotal = (float)$item['harga'] * (float)$item['qty'];
                RealisasiItem::create(array_merge($item, [
                    'realisasi_id' => $realisasi->id,
                    'subtotal' => $subtotal,
                ]));
            }
        }

        RealisasiLog::create([
            'realisasi_id' => $realisasi->id,
            'action' => 'created',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('realisasi.edit', $realisasi->id)->with('success', 'Draft Realisasi dibuat');
    }

    public function edit(Realisasi $realisasi)
    {
        $realisasi->load(['items', 'poAnggaran']);
        return Inertia::render('realisasi/Edit', [
            'realisasi' => $realisasi,
        ]);
    }

    public function update(Request $request, Realisasi $realisasi)
    {
        if (!$realisasi->canBeEdited()) abort(403);

        $validated = $request->validate([
            'po_anggaran_id' => 'required|exists:po_anggarans,id',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'required|in:Transfer',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'required|string',
            'no_rekening' => 'required|string',
            'total_anggaran' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:realisasi_items,id',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
            'items.*.realisasi' => 'required|numeric|min:0',
        ]);

        $realisasi->fill($validated);
        $realisasi->updated_by = Auth::id();
        $realisasi->total_realisasi = collect($validated['items'] ?? [])->sum(function ($it) { return (float)($it['realisasi'] ?? 0); });
        $realisasi->save();

        // Sync items (replace)
        $realisasi->items()->delete();
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $subtotal = (float)$item['harga'] * (float)$item['qty'];
                RealisasiItem::create(array_merge($item, [
                    'realisasi_id' => $realisasi->id,
                    'subtotal' => $subtotal,
                ]));
            }
        }

        RealisasiLog::create([
            'realisasi_id' => $realisasi->id,
            'action' => 'updated',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Draft Realisasi disimpan');
    }

    public function send(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];
        $updated = [];
        $failed = [];

        foreach (Realisasi::whereIn('id', $ids)->get() as $row) {
            if (!$row->canBeSent()) {
                $failed[] = ['id' => $row->id, 'errors' => ['Status bukan Draft']];
                continue;
            }

            // completeness check
            $errors = [];
            if (!$row->po_anggaran_id) $errors[] = 'PO Anggaran belum dipilih';
            if (!$row->department_id) $errors[] = 'Departemen kosong';
            if (!$row->metode_pembayaran) $errors[] = 'Metode pembayaran kosong';
            if (!$row->nama_rekening || !$row->no_rekening) $errors[] = 'Data rekening belum lengkap';
            if ($row->items()->count() === 0) $errors[] = 'Detail pengeluaran belum diisi';
            if ($row->items()->whereNull('realisasi')->orWhere('realisasi', '<', 0)->count() > 0) $errors[] = 'Nilai realisasi item tidak valid';

            if ($errors) {
                $failed[] = ['id' => $row->id, 'errors' => $errors, 'no_realisasi' => $row->no_realisasi];
                continue;
            }

            // Assign number & date
            $dept = $row->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            $row->no_realisasi = DocumentNumberService::generateNumber('Realisasi', null, (int)$row->department_id, (string)$alias);
            $row->tanggal = now();
            // Set initial status per business rules
            $initialStatus = 'In Progress';
            $creatorRole = optional(Auth::user())->role->name;
            $deptName = $row->department?->name ?? '';
            // Kabag creator => Approved langsung
            if ($creatorRole === 'Kabag') {
                $initialStatus = 'Approved';
            } elseif ($creatorRole === 'Kepala Toko') {
                // Kepala Toko creator => Verified langsung
                $initialStatus = 'Verified';
            }
            $row->status = $initialStatus;
            $row->save();

            RealisasiLog::create([
                'realisasi_id' => $row->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            $updated[] = $row->id;
        }

        return back()->with([ 'updated_realisasis' => $updated, 'failed_realisasis' => $failed, 'success' => 'Kirim Realisasi selesai' ]);
    }

    public function verify(Realisasi $realisasi)
    {
        $user = Auth::user();
        if (!$this->workflow->canUserApproveRealisasi($user, $realisasi, 'verify')) abort(403);
        if ($realisasi->status !== 'In Progress') abort(422);

        $realisasi->status = 'Verified';
        $realisasi->save();

        RealisasiLog::create([
            'realisasi_id' => $realisasi->id,
            'action' => 'verified',
            'meta' => null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Realisasi diverifikasi');
    }

    public function approve(Realisasi $realisasi)
    {
        $user = Auth::user();
        if (!$this->workflow->canUserApproveRealisasi($user, $realisasi, 'approve')) abort(403);
        if (!in_array($realisasi->status, ['Verified','In Progress'], true)) abort(422);

        // If workflow has verify step, require Verified; otherwise allow from In Progress
        $realisasi->status = 'Approved';
        $realisasi->approved_by = $user->id;
        $realisasi->save();

        RealisasiLog::create([
            'realisasi_id' => $realisasi->id,
            'action' => 'approved',
            'meta' => null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Realisasi disetujui');
    }

    public function cancel(Realisasi $realisasi)
    {
        if ($realisasi->status !== 'Draft') abort(403);
        $realisasi->status = 'Canceled';
        $realisasi->canceled_by = Auth::id();
        $realisasi->save();

        RealisasiLog::create([
            'realisasi_id' => $realisasi->id,
            'action' => 'canceled',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Realisasi dibatalkan');
    }

    public function show(Realisasi $realisasi)
    {
        $realisasi->load(['items','department','bank','poAnggaran']);
        return Inertia::render('realisasi/Detail', [
            'realisasi' => $realisasi,
        ]);
    }

    public function log(Realisasi $realisasi)
    {
        $logs = RealisasiLog::where('realisasi_id', $realisasi->id)->orderByDesc('created_at')->get();
        return Inertia::render('realisasi/Log', [
            'realisasi' => $realisasi,
            'logs' => $logs,
        ]);
    }

    public function download(Realisasi $realisasi)
    {
        if ($realisasi->status === 'Canceled') abort(403);
        // TODO: implement PDF
        return back()->with('success', 'Unduh Realisasi (PDF) belum diimplementasi');
    }
}
