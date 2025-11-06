<?php

namespace App\Http\Controllers;

use App\Models\PoAnggaran;
use App\Models\PoAnggaranItem;
use App\Models\PoAnggaranLog;
use App\Models\Department;
use App\Services\DocumentNumberService;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class PoAnggaranController extends Controller
{
    protected ApprovalWorkflowService $workflow;

    public function __construct(ApprovalWorkflowService $workflow)
    {
        $this->workflow = $workflow;
    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');

        // Build base query with DepartmentScope by default; for Staff roles, bypass scope and restrict to own-created
        if (in_array($userRole, ['staff toko','staff digital marketing'], true)) {
            $query = PoAnggaran::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['department'])
                ->where('created_by', $user->id);
        } else {
            $query = PoAnggaran::query()->with(['department']);
        }

        // Filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('no_po_anggaran', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('nominal', 'like', "%$search%")
                  ->orWhere('detail_keperluan', 'like', "%$search%");
            });
        }
        if ($no = $request->get('no_po_anggaran')) {
            $query->where('no_po_anggaran', 'like', "%$no%");
        }
        if ($status = $request->get('status')) {
            $query->whereIn('status', (array)$status);
        }
        if ($dept = $request->get('department_id')) {
            $query->where('department_id', $dept);
        }
        // Date range filter only when provided
        $date = $request->get('date');
        if ($date && is_array($date) && count($date) === 2) {
            $query->whereBetween('tanggal', [$date[0], $date[1]]);
        }

        $perPage = (int)($request->get('per_page') ?? 10);
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('po-anggaran/Index', [
            'poAnggarans' => $data,
            'filters' => $request->all(),
            'departments' => Department::select('id','name')->orderBy('name')->get(),
            'columns' => [
                ['key' => 'no_po_anggaran', 'label' => 'No. PO Anggaran', 'checked' => true, 'sortable' => true],
                ['key' => 'department', 'label' => 'Departemen', 'checked' => true],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'checked' => true, 'sortable' => true],
                ['key' => 'nominal', 'label' => 'Nominal', 'checked' => true, 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'checked' => true, 'sortable' => true],
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('po-anggaran/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'required|string',
            'no_rekening' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'detail_keperluan' => 'nullable|string',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
        ]);

        $po = new PoAnggaran($validated);
        $po->status = 'Draft';
        $po->created_by = Auth::id();
        $po->save();

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $subtotal = (float)$item['harga'] * (float)$item['qty'];
                PoAnggaranItem::create(array_merge($item, [
                    'po_anggaran_id' => $po->id,
                    'subtotal' => $subtotal,
                ]));
            }
        }

        PoAnggaranLog::create([
            'po_anggaran_id' => $po->id,
            'action' => 'created',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return redirect()->route('po-anggaran.edit', $po->id)->with('success', 'Draft PO Anggaran dibuat');
    }

    public function edit(PoAnggaran $po_anggaran)
    {
        $po_anggaran->load(['items']);
        return Inertia::render('po-anggaran/Edit', [
            'poAnggaran' => $po_anggaran,
        ]);
    }

    public function update(Request $request, PoAnggaran $po_anggaran)
    {
        if (!$po_anggaran->canBeEdited()) abort(403);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'required|string',
            'no_rekening' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'detail_keperluan' => 'nullable|string',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:po_anggaran_items,id',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
        ]);

        $po_anggaran->fill($validated);
        $po_anggaran->updated_by = Auth::id();
        $po_anggaran->save();

        // Sync items (simple replace for now)
        $po_anggaran->items()->delete();
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $subtotal = (float)$item['harga'] * (float)$item['qty'];
                PoAnggaranItem::create(array_merge($item, [
                    'po_anggaran_id' => $po_anggaran->id,
                    'subtotal' => $subtotal,
                ]));
            }
        }

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'updated',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Draft PO Anggaran disimpan');
    }

    public function send(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];
        $updated = [];
        $failed = [];

        foreach (PoAnggaran::whereIn('id', $ids)->get() as $row) {
            if (!$row->canBeSent()) {
                $failed[] = ['id' => $row->id, 'errors' => ['Status bukan Draft']];
                continue;
            }

            // Basic completeness check
            $errors = [];
            if (!$row->department_id) $errors[] = 'Departemen kosong';
            if (!$row->metode_pembayaran) $errors[] = 'Metode pembayaran kosong';
            if (!$row->nama_rekening || !$row->no_rekening) $errors[] = 'Data rekening belum lengkap';
            if ($row->metode_pembayaran === 'Cek/Giro' && (!$row->no_giro || !$row->tanggal_giro)) {
                $errors[] = 'Data giro belum lengkap';
            }
            if ($row->items()->count() === 0) $errors[] = 'Detail anggaran belum diisi';

            if ($errors) {
                $failed[] = ['id' => $row->id, 'errors' => $errors, 'no_po_anggaran' => $row->no_po_anggaran];
                continue;
            }

            // Assign number & date
            $dept = $row->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            $row->no_po_anggaran = DocumentNumberService::generateNumber('PO Anggaran', null, (int)$row->department_id, (string)$alias);
            $row->tanggal = now();
            // Determine initial status based on workflow (auto-Verified/Approved if applicable)
            $initialStatus = 'In Progress';
            $wf = app(ApprovalWorkflowService::class);
            $workflow = $wf->getApprovalProgressForPoAnggaran($row); // use steps indirectly
            // Fallback using creator role and department via service
            // Auto-verify for creators: Kepala Toko, Kabag
            $creatorRole = optional(Auth::user())->role->name;
            $deptName = $row->department?->name ?? '';
            if (in_array($creatorRole, ['Kepala Toko','Kabag'], true)) {
                $initialStatus = 'Verified';
            }
            // Special case Realisasi doesn't apply here; for Po Anggaran no auto-approved on send
            $row->status = $initialStatus;
            $row->save();

            PoAnggaranLog::create([
                'po_anggaran_id' => $row->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            $updated[] = $row->id;
        }

        return back()->with([ 'updated_pos' => $updated, 'failed_pos' => $failed, 'success' => 'Kirim PO Anggaran selesai' ]);
    }

    public function verify(PoAnggaran $po_anggaran)
    {
        $user = Auth::user();
        if (!$this->workflow->canUserApprovePoAnggaran($user, $po_anggaran, 'verify')) abort(403);

        if ($po_anggaran->status !== 'In Progress') abort(422);

        $po_anggaran->status = 'Verified';
        $po_anggaran->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'verified',
            'meta' => null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'PO Anggaran diverifikasi');
    }

    public function validatePo(PoAnggaran $po_anggaran)
    {
        $user = Auth::user();
        if (!$this->workflow->canUserApprovePoAnggaran($user, $po_anggaran, 'validate')) abort(403);

        if ($po_anggaran->status !== 'Verified') abort(422);

        $po_anggaran->status = 'Validated';
        $po_anggaran->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'validated',
            'meta' => null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'PO Anggaran divalidasi');
    }

    public function approve(PoAnggaran $po_anggaran)
    {
        $user = Auth::user();
        if (!$this->workflow->canUserApprovePoAnggaran($user, $po_anggaran, 'approve')) abort(403);

        // Allow approve from Verified (special depts no validate) or Validated
        if (!in_array($po_anggaran->status, ['Validated','Verified'], true)) abort(422);

        $po_anggaran->status = 'Approved';
        $po_anggaran->approved_by = $user->id;
        $po_anggaran->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'approved',
            'meta' => null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'PO Anggaran disetujui');
    }

    public function reject(Request $request, PoAnggaran $po_anggaran)
    {
        $user = Auth::user();
        if (!$this->workflow->canUserApprovePoAnggaran($user, $po_anggaran, 'reject')) abort(403);

        if (!in_array($po_anggaran->status, ['In Progress','Verified','Validated'], true)) abort(422);

        $po_anggaran->status = 'Rejected';
        $po_anggaran->rejected_by = $user->id;
        $po_anggaran->rejection_reason = (string)$request->get('reason', '');
        $po_anggaran->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'rejected',
            'meta' => ['reason' => $po_anggaran->rejection_reason],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'PO Anggaran ditolak');
    }

    public function cancel(PoAnggaran $po_anggaran)
    {
        if ($po_anggaran->status !== 'Draft') abort(403);
        $po_anggaran->status = 'Canceled';
        $po_anggaran->canceled_by = Auth::id();
        $po_anggaran->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'canceled',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'PO Anggaran dibatalkan');
    }

    public function show(PoAnggaran $po_anggaran)
    {
        $po_anggaran->load(['items','department','bank']);
        return Inertia::render('po-anggaran/Detail', [
            'poAnggaran' => $po_anggaran,
        ]);
    }

    public function log(PoAnggaran $po_anggaran)
    {
        $logs = PoAnggaranLog::where('po_anggaran_id', $po_anggaran->id)->orderByDesc('created_at')->get();
        return Inertia::render('po-anggaran/Log', [
            'poAnggaran' => $po_anggaran,
            'logs' => $logs,
        ]);
    }

    public function download(PoAnggaran $po_anggaran)
    {
        if ($po_anggaran->status === 'Canceled') abort(403);
        // TODO: implement PDF
        return back()->with('success', 'Unduh PO Anggaran (PDF) belum diimplementasi');
    }
}
