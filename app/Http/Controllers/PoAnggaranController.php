<?php

namespace App\Http\Controllers;

use App\Models\PoAnggaran;
use App\Models\PoAnggaranItem;
use App\Models\PoAnggaranLog;
use App\Models\Department;
use App\Services\DocumentNumberService;
use App\Services\ApprovalWorkflowService;
use App\Services\DepartmentService;
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

        // Build base query with DepartmentScope by default.
        // - Staff Toko & Kepala Toko: Po Anggaran created by Staff Toko or Kepala Toko in their departments
        // - Staff Digital Marketing: Po Anggaran created by Staff Digital Marketing in their departments
        // - Other roles: rely only on DepartmentScope
        $query = PoAnggaran::query()->with(['department','perihal','bank','bisnisPartner','creator']);

        if (in_array($userRole, ['staff toko','kepala toko'], true)) {
            $query->whereHas('creator.role', function ($q) {
                $q->whereIn('name', ['Staff Toko', 'Kepala Toko']);
            });
        }

        if ($userRole === 'staff digital marketing') {
            $query->whereHas('creator.role', function ($q) {
                $q->where('name', 'Staff Digital Marketing');
            });
        }

        // Filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('no_po_anggaran', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%")
                  ->orWhere('nominal', 'like', "%$search%")
                  ->orWhere('metode_pembayaran', 'like', "%$search%")
                  ->orWhere('detail_keperluan', 'like', "%$search%")
                  ->orWhere('note', 'like', "%$search%")
                  ->orWhere('nama_rekening', 'like', "%$search%")
                  ->orWhere('no_rekening', 'like', "%$search%")
                  ->orWhere('no_giro', 'like', "%$search%")
                  ->orWhere('tanggal', 'like', "%$search%")
                  ->orWhere('tanggal_giro', 'like', "%$search%")
                  ->orWhere('tanggal_cair', 'like', "%$search%")
                  ->orWhereHas('department', function ($d) use ($search) { $d->where('name', 'like', "%$search%"); })
                  ->orWhereHas('perihal', function ($p) use ($search) { $p->where('nama', 'like', "%$search%"); })
                  ->orWhereHas('bank', function ($b) use ($search) { $b->where('nama_bank', 'like', "%$search%")->orWhere('singkatan', 'like', "%$search%"); })
                  ->orWhereHas('bisnisPartner', function ($bp) use ($search) { $bp->where('nama_bp', 'like', "%$search%"); })
                  ->orWhereHas('creator', function ($u) use ($search) { $u->where('name', 'like', "%$search%"); });
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
        // Date range filter only when provided (use tanggal_start/tanggal_end from UI)
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        if ($tanggalStart && $tanggalEnd) {
            $query->whereBetween('tanggal', [$tanggalStart, $tanggalEnd]);
        }

        $perPage = (int)($request->get('per_page') ?? 10);
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('po-anggaran/Index', [
            'poAnggarans' => $data,
            'filters' => $request->all(),
            'departments' => DepartmentService::getOptionsForFilter(),
            'columns' => [
                ['key' => 'no_po_anggaran', 'label' => 'No. PO Anggaran', 'checked' => true, 'sortable' => true],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'checked' => true, 'sortable' => true],
                ['key' => 'department', 'label' => 'Departemen', 'checked' => true],
                ['key' => 'perihal', 'label' => 'Perihal', 'checked' => true],
                ['key' => 'metode_pembayaran', 'label' => 'Metode Pembayaran', 'checked' => false],
                ['key' => 'bank', 'label' => 'Bank', 'checked' => false],
                ['key' => 'bisnis_partner', 'label' => 'Bisnis Partner', 'checked' => false],
                ['key' => 'nama_rekening', 'label' => 'Nama Rekening', 'checked' => false],
                ['key' => 'no_rekening', 'label' => 'No. Rekening', 'checked' => false],
                ['key' => 'no_giro', 'label' => 'No. Giro', 'checked' => false],
                ['key' => 'tanggal_giro', 'label' => 'Tanggal Giro', 'checked' => false],
                ['key' => 'tanggal_cair', 'label' => 'Tanggal Cair', 'checked' => false],
                ['key' => 'nominal', 'label' => 'Nominal', 'checked' => true, 'sortable' => true],
                ['key' => 'detail_keperluan', 'label' => 'Detail Keperluan', 'checked' => false],
                ['key' => 'note', 'label' => 'Catatan', 'checked' => false],
                ['key' => 'created_by', 'label' => 'Dibuat Oleh', 'checked' => false],
                ['key' => 'created_at', 'label' => 'Dibuat Tanggal', 'checked' => false],
                ['key' => 'status', 'label' => 'Status', 'checked' => true, 'sortable' => true],
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('po-anggaran/Create', [
            'departments' => DepartmentService::getOptionsForForm(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'perihal_id' => 'required|exists:perihals,id',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
            'bank_id' => 'nullable|exists:banks,id',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
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

        // If BP selected, normalize rekening fields from BP to ensure consistency
        if (!empty($validated['bisnis_partner_id'])) {
            $bp = \App\Models\BisnisPartner::with('bank')->find($validated['bisnis_partner_id']);
            if ($bp) {
                $validated['bank_id'] = $bp->bank_id;
                $validated['nama_rekening'] = $bp->nama_rekening ?? ($bp->nama_bp ?? '');
                $validated['no_rekening'] = $bp->no_rekening_va ?? '';
            }
        }

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

        // Recalculate nominal based on current items to ensure consistency
        $po->nominal = $po->items()->sum('subtotal');
        $po->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po->id,
            'action' => 'created',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        // Handle action send or draft
        $action = (string)$request->get('action', 'draft');
        if ($action === 'send') {
            // Basic completeness check (same as send())
            $errors = [];
            if (!$po->department_id) $errors[] = 'Departemen kosong';
            if (!$po->metode_pembayaran) $errors[] = 'Metode pembayaran kosong';
            if (!$po->perihal_id) $errors[] = 'Perihal belum dipilih';
            if (!$po->nama_rekening || !$po->no_rekening) $errors[] = 'Data rekening belum lengkap';
            if ($po->metode_pembayaran === 'Cek/Giro' && (!$po->no_giro || !$po->tanggal_giro)) {
                $errors[] = 'Data giro belum lengkap';
            }
            if ($po->items()->count() === 0) $errors[] = 'Detail anggaran belum diisi';

            if ($errors) {
                return redirect()->route('po-anggaran.index')->with([
                    'failed_pos' => [['id' => $po->id, 'errors' => $errors, 'no_po_anggaran' => $po->no_po_anggaran]],
                    'success' => 'PO Anggaran berhasil dibuat'
                ]);
            }

            // Assign number & date
            $dept = $po->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            $po->no_po_anggaran = DocumentNumberService::generateNumber('PO Anggaran', null, (int)$po->department_id, (string)$alias);
            $po->tanggal = now();
            // Determine initial status based on workflow (auto-Verified/Approved if applicable)
            $initialStatus = 'In Progress';
            $creatorRole = optional(Auth::user())->role->name;
            if (in_array($creatorRole, ['Kepala Toko','Kabag'], true)) {
                $initialStatus = 'Verified';
            }
            $po->status = $initialStatus;
            $po->save();

            PoAnggaranLog::create([
                'po_anggaran_id' => $po->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            return redirect()->route('po-anggaran.index')->with([
                'updated_pos' => [$po->id],
                'failed_pos' => [],
                'success' => 'PO Anggaran berhasil dibuat'
            ]);
        }

        // Default: draft saved, go back to index
        return redirect()->route('po-anggaran.index')->with('success', 'PO Anggaran berhasil di simpan sebagai Draft');
    }

    public function edit(PoAnggaran $po_anggaran)
    {
        $po_anggaran->load(['items']);
        return Inertia::render('po-anggaran/Edit', [
            'poAnggaran' => $po_anggaran,
            'departments' => DepartmentService::getOptionsForForm(),
        ]);
    }

    public function update(Request $request, PoAnggaran $po_anggaran)
    {
        if (!$po_anggaran->canBeEdited()) abort(403);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'perihal_id' => 'required|exists:perihals,id',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
            'bank_id' => 'nullable|exists:banks,id',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
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

        // If BP selected, normalize rekening fields
        if (!empty($validated['bisnis_partner_id'])) {
            $bp = \App\Models\BisnisPartner::with('bank')->find($validated['bisnis_partner_id']);
            if ($bp) {
                $validated['bank_id'] = $bp->bank_id;
                $validated['nama_rekening'] = $bp->nama_rekening ?? ($bp->nama_bp ?? '');
                $validated['no_rekening'] = $bp->no_rekening_va ?? '';
            }
        }

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

        // Recalculate nominal based on updated items to ensure it always matches detail rows
        $po_anggaran->nominal = $po_anggaran->items()->sum('subtotal');
        $po_anggaran->save();

        PoAnggaranLog::create([
            'po_anggaran_id' => $po_anggaran->id,
            'action' => 'updated',
            'meta' => null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        // Handle action for update (draft or send)
        $action = (string)$request->get('action', 'draft');
        if ($action === 'send') {
            if (!$po_anggaran->canBeSent()) {
                return redirect()->route('po-anggaran.index')->with('error', 'Status bukan Draft');
            }
            // Completeness check
            $errors = [];
            if (!$po_anggaran->department_id) $errors[] = 'Departemen kosong';
            if (!$po_anggaran->metode_pembayaran) $errors[] = 'Metode pembayaran kosong';
            if (!$po_anggaran->perihal_id) $errors[] = 'Perihal belum dipilih';
            if (!$po_anggaran->nama_rekening || !$po_anggaran->no_rekening) $errors[] = 'Data rekening belum lengkap';
            if ($po_anggaran->metode_pembayaran === 'Cek/Giro' && (!$po_anggaran->no_giro || !$po_anggaran->tanggal_giro)) {
                $errors[] = 'Data giro belum lengkap';
            }
            if ($po_anggaran->items()->count() === 0) $errors[] = 'Detail anggaran belum diisi';

            if ($errors) {
                return redirect()->route('po-anggaran.index')->with([
                    'failed_pos' => [['id' => $po_anggaran->id, 'errors' => $errors, 'no_po_anggaran' => $po_anggaran->no_po_anggaran]],
                    'success' => 'PO Anggaran berhasil dibuat'
                ]);
            }

            // Assign number & date
            $dept = $po_anggaran->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            $po_anggaran->no_po_anggaran = DocumentNumberService::generateNumber('PO Anggaran', null, (int)$po_anggaran->department_id, (string)$alias);
            $po_anggaran->tanggal = now();
            // Determine initial status based on workflow (auto-Verified for certain roles)
            $initialStatus = 'In Progress';
            $creatorRole = optional(Auth::user())->role->name;
            if (in_array($creatorRole, ['Kepala Toko','Kabag'], true)) {
                $initialStatus = 'Verified';
            }
            $po_anggaran->status = $initialStatus;
            $po_anggaran->save();

            PoAnggaranLog::create([
                'po_anggaran_id' => $po_anggaran->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            return redirect()->route('po-anggaran.index')->with([
                'updated_pos' => [$po_anggaran->id],
                'failed_pos' => [],
                'success' => 'PO Anggaran berhasil dibuat'
            ]);
        }

        return redirect()->route('po-anggaran.index')->with('success', 'PO Anggaran berhasil di simpan sebagai Draft');
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
            if (!$row->perihal_id) $errors[] = 'Perihal belum dipilih';
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

        return redirect()->route('po-anggaran.index')->with([ 'updated_pos' => $updated, 'failed_pos' => $failed, 'success' => 'PO Anggaran berhasil dibuat' ]);
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
        $po_anggaran->load(['items','department','bank','perihal','bisnisPartner','bisnisPartner.bank']);
        $progress = $this->workflow->getApprovalProgressForPoAnggaran($po_anggaran);
        return Inertia::render('po-anggaran/Detail', [
            'poAnggaran' => $po_anggaran,
            'progress' => $progress,
            'userRole' => optional(Auth::user()->role)->name ?? '',
        ]);
    }

    public function log(PoAnggaran $po_anggaran)
    {
        $po_anggaran->loadMissing('department');

        $logs = PoAnggaranLog::where('po_anggaran_id', $po_anggaran->id)
            ->with(['user.department', 'user.role'])
            ->orderByDesc('created_at')
            ->get();
        return Inertia::render('po-anggaran/Log', [
            'poAnggaran' => $po_anggaran,
            'logs' => $logs,
        ]);
    }

    public function download(PoAnggaran $po_anggaran)
    {
        if ($po_anggaran->status === 'Canceled') abort(403);

        try {
            // Load necessary relationships
            $po_anggaran->load(['items', 'department', 'bank', 'perihal', 'bisnisPartner', 'creator', 'creator.role']);

            // Calculate total
            $total = $po_anggaran->items->sum('subtotal');

            // Format date in Indonesian
            $tanggal = $po_anggaran->tanggal
                ? \Carbon\Carbon::parse($po_anggaran->tanggal)->locale('id')->translatedFormat('d F Y')
                : \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y');

            // Clean filename - remove invalid characters
            $filename = 'POAnggaran_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $po_anggaran->no_po_anggaran ?? 'Draft') . '.pdf';

            // Get base64 encoded images
            $signatureSrc= $this->getBase64Image('images/signature.png');
            $logoSrc = $this->getBase64Image('images/company-logo.png');
            $approvedSrc = $this->getBase64Image('images/approved.png');

            // Create PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('po_anggaran_pdf', [
                'poAnggaran' => $po_anggaran,
                'tanggal' => $tanggal,
                'total' => $total,
                'logoSrc' => $logoSrc,
                'signatureSrc' => $signatureSrc,
                'approvedSrc' => $approvedSrc,
            ])
            ->setOptions(config('dompdf.options'))
            ->setPaper('a4', 'portrait');

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('POAnggaran Download - Error occurred:', [
                'po_anggaran_id' => $po_anggaran->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengunduh PDF: ' . $e->getMessage());
        }
    }

    /**
     * Get base64 encoded image for embedding in PDF
     *
     * @param string $imagePath Relative path from public directory
     * @return string Base64 encoded image data
     */
    protected function getBase64Image($imagePath)
    {
        $path = public_path($imagePath);
        if (!file_exists($path)) {
            return '';
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
