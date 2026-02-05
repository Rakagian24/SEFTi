<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Perihal;
use App\Models\Department;
use App\Models\PoAnggaran;
use Illuminate\Http\Request;
use App\Models\PoAnggaranLog;
use App\Models\PaymentVoucher;
use App\Models\PoAnggaranItem;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentNumberService;
use App\Services\ApprovalWorkflowService;
use App\Services\PoAnggaranWhatsappNotifier;

class PoAnggaranController extends Controller
{
    protected ApprovalWorkflowService $workflow;
    protected PoAnggaranWhatsappNotifier $poAnggaranWhatsappNotifier;

    public function __construct(
        ApprovalWorkflowService $workflow,
        PoAnggaranWhatsappNotifier $poAnggaranWhatsappNotifier
    ) {
        $this->workflow = $workflow;
        $this->poAnggaranWhatsappNotifier = $poAnggaranWhatsappNotifier;
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
        if ($perihalId = $request->get('perihal_id')) {
            $query->whereIn('perihal_id', (array) $perihalId);
        }
        if ($metode = $request->get('metode_pembayaran')) {
            $query->whereIn('metode_pembayaran', (array) $metode);
        }
        // Date range filter only when provided (use tanggal_start/tanggal_end from UI)
        $tanggalStart = $request->get('tanggal_start');
        $tanggalEnd = $request->get('tanggal_end');
        if ($tanggalStart && $tanggalEnd) {
            $query->whereBetween('tanggal', [$tanggalStart, $tanggalEnd]);
        }

        $perPage = (int)($request->get('per_page') ?? 10);
        $data = $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('po-anggaran/Index', [
            'poAnggarans' => $data,
            'filters' => $request->all(),
            'departments' => DepartmentService::getOptionsForFilter(),
            'perihals' => Perihal::active()->orderBy('nama')->get(['id', 'nama']),
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
        $action = (string) $request->get('action', 'draft');

        // If BP selected, normalize rekening fields from BP before validation so rules see final values
        if ($request->filled('bisnis_partner_id')) {
            $bp = \App\Models\BisnisPartner::with('bank')->find($request->input('bisnis_partner_id'));
            if ($bp) {
                $request->merge([
                    'bank_id' => $bp->bank_id,
                    'nama_rekening' => $bp->nama_rekening ?? ($bp->nama_bp ?? ''),
                    'no_rekening' => $bp->no_rekening_va ?? '',
                ]);
            }
        }

        // Validation rules: stricter when sending, more lenient for draft save
        if ($action === 'send') {
            $rules = [
                'department_id' => 'required|exists:departments,id',
                'perihal_id' => 'required|exists:perihals,id',
                'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
                'bank_id' => 'nullable|exists:banks,id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
                'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
                'credit_card_id' => 'nullable|exists:credit_cards,id',
                // For send, rekening data and detail items must be complete based on metode
                'nama_rekening' => 'required_if:metode_pembayaran,Transfer,Kredit|string',
                'no_rekening' => 'required_if:metode_pembayaran,Transfer|string',
                // no_giro & tanggal_giro only required when metode_pembayaran = Cek/Giro,
                // and should be allowed to be null/empty for Transfer/Kredit
                'no_giro' => 'nullable|required_if:metode_pembayaran,Cek/Giro|string',
                'tanggal_giro' => 'nullable|required_if:metode_pembayaran,Cek/Giro|date',
                'nominal' => 'required|numeric|min:0',
                'detail_keperluan' => 'nullable|string',
                'note' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
                'items.*.jenis_pengeluaran_text' => 'nullable|string',
                'items.*.keterangan' => 'nullable|string',
                'items.*.harga' => 'required|numeric|min:0',
                'items.*.qty' => 'required|numeric|min:0',
                'items.*.satuan' => 'nullable|string',
            ];
        } else {
            // Draft can be saved with partial data, keep rules more relaxed
            $rules = [
                'department_id' => 'required|exists:departments,id',
                'perihal_id' => 'required|exists:perihals,id',
                'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
                'bank_id' => 'nullable|exists:banks,id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
                'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
                'credit_card_id' => 'nullable|exists:credit_cards,id',
                'nama_rekening' => 'nullable|string',
                // 'no_rekening' => 'nullable|string',
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
            ];
        }

        // Tambahan aturan dinamis berdasarkan perihal untuk Supplier vs Bisnis Partner
        if (isset($rules['perihal_id']) && $request->filled('perihal_id')) {
            $perihal = Perihal::find($request->input('perihal_id'));
            $namaPerihal = $perihal ? strtolower(trim($perihal->nama)) : null;

            if ($action === 'send' && $namaPerihal && $request->input('metode_pembayaran') === 'Transfer') {
                // Perihal yang harus menggunakan Supplier
                if (in_array($namaPerihal, [
                    'permintaan pembayaran barang',
                    'permintaan pembayaran jasa',
                    'permintaan pembayaran barang/jasa',
                ], true)) {
                    $rules['supplier_id'] = 'required|exists:suppliers,id';
                    $rules['bank_supplier_account_id'] = 'required|exists:bank_supplier_accounts,id';
                    $rules['bisnis_partner_id'] = 'nullable|exists:bisnis_partners,id';
                } else {
                    // Perihal lain: wajib Bisnis Partner (untuk Transfer)
                    $rules['bisnis_partner_id'] = 'required|exists:bisnis_partners,id';
                    $rules['supplier_id'] = 'nullable|exists:suppliers,id';
                    $rules['bank_supplier_account_id'] = 'nullable|exists:bank_supplier_accounts,id';
                }
            }
        }

        $validated = $request->validate($rules);

        // Normalisasi field Supplier vs Bisnis Partner berdasarkan perihal
        try {
            $perihalName = null;
            if (!empty($validated['perihal_id'])) {
                $perihalModel = Perihal::find($validated['perihal_id']);
                if ($perihalModel) {
                    $perihalName = strtolower(trim($perihalModel->nama));
                }
            }

            if (in_array($perihalName, [
                'permintaan pembayaran barang',
                'permintaan pembayaran jasa',
                'permintaan pembayaran barang/jasa',
            ], true)) {
                // Mode Supplier: kosongkan Bisnis Partner
                $validated['bisnis_partner_id'] = null;
            } elseif ($perihalName) {
                // Mode Bisnis Partner: kosongkan Supplier
                $validated['supplier_id'] = null;
                $validated['bank_supplier_account_id'] = null;
            }
        } catch (\Throwable $e) {
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

        // Log hanya pembuatan draft; ketika langsung kirim, log akan dicatat sebagai 'sent' saja
        if ($action !== 'send') {
            PoAnggaranLog::create([
                'po_anggaran_id' => $po->id,
                'action' => 'saved_draft',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);
        }

        // Handle action send or draft
        if ($action === 'send') {
            // Assign number & date
            $dept = $po->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            $po->no_po_anggaran = DocumentNumberService::generateNumber('PO Anggaran', null, (int)$po->department_id, (string)$alias);
            $po->tanggal = now();
            // Determine initial status based on metode pembayaran & workflow
            if ($po->metode_pembayaran === 'Kredit') {
                // Kredit: langsung Approved saat dikirim
                $po->status = 'Approved';
            } else {
                $initialStatus = 'In Progress';
                $creatorRole = optional(Auth::user())->role->name;
                if (in_array($creatorRole, ['Kepala Toko','Kabag'], true)) {
                    $initialStatus = 'Verified';
                }
                $po->status = $initialStatus;
            }
            $po->save();

            PoAnggaranLog::create([
                'po_anggaran_id' => $po->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            try {
                $this->poAnggaranWhatsappNotifier->notifyFirstApproverOnCreated($po);
            } catch (\Throwable $e) {
                Log::error('PO Anggaran store - failed to send WhatsApp notification for first approver', [
                    'po_anggaran_id' => $po->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('po-anggaran.index')->with([
                'updated_pos' => [$po->id],
                'failed_pos' => [],
                'success' => 'PO Anggaran berhasil dibuat'
            ]);
        }

        // Default: draft saved, go back to index
        if ($po->status !== 'Draft') {
            $po->status = 'Draft';
            $po->save();
        }

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

        $action = (string) $request->get('action', 'draft');

        // Normalize BP fields before validation so conditional rules work correctly
        if ($request->filled('bisnis_partner_id')) {
            $bp = \App\Models\BisnisPartner::with('bank')->find($request->input('bisnis_partner_id'));
            if ($bp) {
                $request->merge([
                    'bank_id' => $bp->bank_id,
                    'nama_rekening' => $bp->nama_rekening ?? ($bp->nama_bp ?? ''),
                    'no_rekening' => $bp->no_rekening_va ?? '',
                ]);
            }
        }

        if ($action === 'send') {
            $rules = [
                'department_id' => 'required|exists:departments,id',
                'perihal_id' => 'required|exists:perihals,id',
                'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
                'bank_id' => 'nullable|exists:banks,id',
                'supplier_id' => 'nullable|exists:suppliers,id',
                'bank_supplier_account_id' => 'nullable|exists:bank_supplier_accounts,id',
                'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
                'credit_card_id' => 'nullable|exists:credit_cards,id',
                'nama_rekening' => 'required_if:metode_pembayaran,Transfer,Kredit|string',
                'no_rekening' => 'required_if:metode_pembayaran,Transfer|string',
                // no_giro & tanggal_giro only required when metode_pembayaran = Cek/Giro,
                // and should be allowed to be null/empty for Transfer/Kredit
                'no_giro' => 'nullable|required_if:metode_pembayaran,Cek/Giro|string',
                'tanggal_giro' => 'nullable|required_if:metode_pembayaran,Cek/Giro|date',
                'nominal' => 'required|numeric|min:0',
                'detail_keperluan' => 'nullable|string',
                'note' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.id' => 'nullable|exists:po_anggaran_items,id',
                'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
                'items.*.jenis_pengeluaran_text' => 'nullable|string',
                'items.*.keterangan' => 'nullable|string',
                'items.*.harga' => 'required|numeric|min:0',
                'items.*.qty' => 'required|numeric|min:0',
                'items.*.satuan' => 'nullable|string',
            ];
        } else {
            $rules = [
                'department_id' => 'required|exists:departments,id',
                'perihal_id' => 'required|exists:perihals,id',
                'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
                'bank_id' => 'nullable|exists:banks,id',
                'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
                'credit_card_id' => 'nullable|exists:credit_cards,id',
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
            ];
        }

        // Tambahan aturan dinamis berdasarkan perihal untuk Supplier vs Bisnis Partner
        if (isset($rules['perihal_id']) && $request->filled('perihal_id')) {
            $perihal = Perihal::find($request->input('perihal_id'));
            $namaPerihal = $perihal ? strtolower(trim($perihal->nama)) : null;

            if ($action === 'send' && $namaPerihal && $request->input('metode_pembayaran') === 'Transfer') {
                if (in_array($namaPerihal, [
                    'permintaan pembayaran barang',
                    'permintaan pembayaran jasa',
                    'permintaan pembayaran barang/jasa',
                ], true)) {
                    $rules['supplier_id'] = 'required|exists:suppliers,id';
                    $rules['bank_supplier_account_id'] = 'required|exists:bank_supplier_accounts,id';
                    $rules['bisnis_partner_id'] = 'nullable|exists:bisnis_partners,id';
                } else {
                    $rules['bisnis_partner_id'] = 'required|exists:bisnis_partners,id';
                    $rules['supplier_id'] = 'nullable|exists:suppliers,id';
                    $rules['bank_supplier_account_id'] = 'nullable|exists:bank_supplier_accounts,id';
                }
            }
        }

        $validated = $request->validate($rules);

        // Normalisasi field Supplier vs Bisnis Partner berdasarkan perihal
        try {
            $perihalName = null;
            if (!empty($validated['perihal_id'])) {
                $perihalModel = Perihal::find($validated['perihal_id']);
                if ($perihalModel) {
                    $perihalName = strtolower(trim($perihalModel->nama));
                }
            }

            if (in_array($perihalName, [
                'permintaan pembayaran barang',
                'permintaan pembayaran jasa',
                'permintaan pembayaran barang/jasa',
            ], true)) {
                $validated['bisnis_partner_id'] = null;
            } elseif ($perihalName) {
                $validated['supplier_id'] = null;
                $validated['bank_supplier_account_id'] = null;
            }
        } catch (\Throwable $e) {
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

        // Log perubahan hanya untuk save draft; ketika langsung kirim, log akan dicatat sebagai 'sent' saja
        if ($action !== 'send') {
            PoAnggaranLog::create([
                'po_anggaran_id' => $po_anggaran->id,
                'action' => 'saved_draft',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);
        }

        // Handle action for update (draft or send)
        if ($action === 'send') {
            if (!$po_anggaran->canBeSent()) {
                return redirect()->route('po-anggaran.index')->with('error', 'Status bukan Draft');
            }
            // Assign number & date (keep existing number when resending)
            $dept = $po_anggaran->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            if (empty($po_anggaran->no_po_anggaran)) {
                $po_anggaran->no_po_anggaran = DocumentNumberService::generateNumber('PO Anggaran', null, (int)$po_anggaran->department_id, (string)$alias);
            }
            $po_anggaran->tanggal = now();
            // Determine initial status based on metode pembayaran & workflow
            if ($po_anggaran->metode_pembayaran === 'Kredit') {
                $po_anggaran->status = 'Approved';
            } else {
                $initialStatus = 'In Progress';
                $creatorRole = optional(Auth::user())->role->name;
                if (in_array($creatorRole, ['Kepala Toko','Kabag'], true)) {
                    $initialStatus = 'Verified';
                }
                $po_anggaran->status = $initialStatus;
            }
            $po_anggaran->save();

            PoAnggaranLog::create([
                'po_anggaran_id' => $po_anggaran->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            try {
                $this->poAnggaranWhatsappNotifier->notifyFirstApproverOnCreated($po_anggaran);
            } catch (\Throwable $e) {
                Log::error('PO Anggaran update - failed to send WhatsApp notification for first approver', [
                    'po_anggaran_id' => $po_anggaran->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('po-anggaran.index')->with([
                'updated_pos' => [$po_anggaran->id],
                'failed_pos' => [],
                'success' => 'PO Anggaran berhasil dibuat'
            ]);
        }

        if ($po_anggaran->status !== 'Draft') {
            $po_anggaran->status = 'Draft';
            $po_anggaran->save();
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

            // Basic completeness check - align with store() rules when action === 'send'
            $errors = [];

            // Required core fields
            if (!$row->department_id) {
                $errors[] = 'Departemen kosong';
            }
            if (!$row->perihal_id) {
                $errors[] = 'Perihal belum dipilih';
            }

            // Metode pembayaran must be valid and present
            if (!$row->metode_pembayaran) {
                $errors[] = 'Metode pembayaran kosong';
            } elseif (!in_array($row->metode_pembayaran, ['Transfer', 'Cek/Giro', 'Kredit'], true)) {
                $errors[] = 'Metode pembayaran tidak valid';
            }

            // Normalize rekening fields from Bisnis Partner for older drafts, similar to store()/update()
            if ($row->bisnis_partner_id && in_array($row->metode_pembayaran, ['Transfer', 'Kredit'], true)) {
                if (!$row->nama_rekening || !$row->no_rekening) {
                    $bp = \App\Models\BisnisPartner::with('bank')->find($row->bisnis_partner_id);
                    if ($bp) {
                        if (!$row->nama_rekening) {
                            $row->nama_rekening = $bp->nama_rekening ?? ($bp->nama_bp ?? '');
                        }
                        if (!$row->no_rekening) {
                            $row->no_rekening = $bp->no_rekening_va ?? '';
                        }
                    }
                }
            }

            // Rekening rules, following send validation in store():
            // - nama_rekening required if metode_pembayaran in [Transfer, Kredit]
            // - no_rekening required if metode_pembayaran === Transfer
            if (in_array($row->metode_pembayaran, ['Transfer', 'Kredit'], true) && !$row->nama_rekening) {
                $errors[] = 'Nama rekening wajib diisi';
            }
            if ($row->metode_pembayaran === 'Transfer' && !$row->no_rekening) {
                $errors[] = 'No rekening wajib diisi';
            }

            // Giro rules for Cek/Giro: no_giro & tanggal_giro must be filled
            if ($row->metode_pembayaran === 'Cek/Giro' && (!$row->no_giro || !$row->tanggal_giro)) {
                $errors[] = 'Data giro belum lengkap';
            }

            // At least one detail item, same intent as items required|min:1
            if ($row->items()->count() === 0) {
                $errors[] = 'Detail anggaran belum diisi';
            }

            // Nominal must be non-negative number (store requires numeric|min:0)
            if (!is_numeric($row->nominal) || $row->nominal < 0) {
                $errors[] = 'Nominal tidak valid';
            }

            if ($errors) {
                // Log detail kenapa PO Anggaran gagal dikirim (untuk debugging)
                Log::warning('PO Anggaran gagal dikirim karena data tidak lengkap', [
                    'po_anggaran_id' => $row->id,
                    'no_po_anggaran' => $row->no_po_anggaran,
                    'status' => $row->status,
                    'department_id' => $row->department_id,
                    'perihal_id' => $row->perihal_id,
                    'metode_pembayaran' => $row->metode_pembayaran,
                    'nama_rekening' => $row->nama_rekening,
                    'no_rekening' => $row->no_rekening,
                    'no_giro' => $row->no_giro,
                    'tanggal_giro' => $row->tanggal_giro,
                    'items_count' => $row->items()->count(),
                    'nominal' => $row->nominal,
                    'errors' => $errors,
                ]);

                $failed[] = ['id' => $row->id, 'errors' => $errors, 'no_po_anggaran' => $row->no_po_anggaran];
                continue;
            }

            // Assign number & date (preserve existing number on resend)
            $dept = $row->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            if (empty($row->no_po_anggaran)) {
                $row->no_po_anggaran = DocumentNumberService::generateNumber('PO Anggaran', null, (int)$row->department_id, (string)$alias);
            }
            $row->tanggal = now();
            // Determine initial status based on metode pembayaran & workflow
            if ($row->metode_pembayaran === 'Kredit') {
                $row->status = 'Approved';
            } else {
                $initialStatus = 'In Progress';
                $wf = app(ApprovalWorkflowService::class);
                $workflow = $wf->getApprovalProgressForPoAnggaran($row); // keep for potential future use
                // Fallback using creator role and department via service
                // Auto-verify for creators: Kepala Toko, Kabag
                $creatorRole = optional(Auth::user())->role->name;
                $deptName = $row->department?->name ?? '';
                if (in_array($creatorRole, ['Kepala Toko','Kabag'], true)) {
                    $initialStatus = 'Verified';
                }
                $row->status = $initialStatus;
            }
            $row->save();

            PoAnggaranLog::create([
                'po_anggaran_id' => $row->id,
                'action' => 'sent',
                'meta' => null,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            try {
                app(\App\Services\PoAnggaranWhatsappNotifier::class)
                    ->notifyFirstApproverOnCreated($row);
            } catch (\Throwable $e) {
                Log::error('PO Anggaran bulk send - failed to send WhatsApp notification for first approver', [
                    'po_anggaran_id' => $row->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $updated[] = $row->id;
        }

        // Build flash messages based on actual results
        $flash = [
            'updated_pos' => $updated,
            'failed_pos' => $failed,
        ];

        $updatedCount = count($updated);
        $failedCount = count($failed);

        if ($updatedCount > 0 && $failedCount === 0) {
            // All selected PO Anggaran were successfully sent
            $flash['success'] = 'PO Anggaran berhasil dikirim';
        } elseif ($updatedCount > 0 && $failedCount > 0) {
            // Partial success: some sent, some failed – details are in failed_pos
            $flash['error'] = 'Sebagian PO Anggaran berhasil dikirim, sebagian gagal.';
        } elseif ($updatedCount === 0 && $failedCount > 0) {
            // All failed
            $flash['error'] = 'Semua PO Anggaran gagal dikirim.';
        }

        return redirect()->route('po-anggaran.index')->with($flash);
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
        if (!in_array($po_anggaran->status, ['Draft', 'Rejected'], true)) abort(403);
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

        $pvTransferDocs = PaymentVoucher::query()
            ->where('po_anggaran_id', $po_anggaran->id)
            ->whereIn('status', ['Closed', 'Approved'])
            ->with(['documents' => function ($q) {
                $q->where('type', 'bukti_transfer_bca')
                  ->where('active', true)
                  ->whereNotNull('path');
            }])
            ->get(['id', 'no_pv'])
            ->flatMap(function ($pv) {
                return $pv->documents->map(function ($doc) use ($pv) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->original_name ?? 'Bukti Transfer BCA.pdf',
                        'url' => url('/payment-voucher/documents/' . $doc->id . '/download'),
                        'no_pv' => $pv->no_pv,
                    ];
                });
            })
            ->filter()
            ->values();

        return Inertia::render('po-anggaran/Detail', [
            'poAnggaran' => $po_anggaran,
            'progress' => $progress,
            'userRole' => optional(Auth::user()->role)->name ?? '',
            'pvTransferDocs' => $pvTransferDocs,
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
