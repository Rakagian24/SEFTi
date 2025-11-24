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

    /**
     * Get base64 encoded image for PDF embedding
     *
     * @param string $imagePath Relative path to image from public folder
     * @return string Base64 encoded image data
     */
    protected function getBase64Image($imagePath)
    {
        try {
            $path = public_path($imagePath);
            if (!file_exists($path)) {
                return '';
            }

            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);

            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error encoding image: ' . $e->getMessage());
            return '';
        }
    }
    public function poAnggaranOptions(Request $request)
    {
        $q = PoAnggaran::query()
            ->select('id', 'no_po_anggaran', 'department_id', 'nominal', 'status', 'created_at', 'nama_rekening', 'bank_id', 'bisnis_partner_id')
            // Hanya tampilkan PO Anggaran yang sudah berstatus Approved
            ->where('status', 'Approved')
            // Exclude PO Anggaran yang sudah punya Realisasi dengan status selain Canceled
            ->whereNotIn('id', function ($sub) {
                $sub->select('po_anggaran_id')
                    ->from('realisasis')
                    ->whereNull('deleted_at')
                    ->where('status', '!=', 'Canceled');
            })
            ->orderByDesc('created_at')
            ->limit(100);

        if ($request->filled('department_id')) {
            $q->where('department_id', $request->get('department_id'));
        }

        if ($request->filled('metode_pembayaran')) {
            $q->where('metode_pembayaran', $request->get('metode_pembayaran'));
        }

        if ($request->filled('bisnis_partner_id')) {
            $q->where('bisnis_partner_id', $request->get('bisnis_partner_id'));
        }

        if ($request->filled('bank_id')) {
            $q->where('bank_id', $request->get('bank_id'));
        }

        if ($request->filled('nama_rekening')) {
            $nama = (string)$request->get('nama_rekening');
            $q->where('nama_rekening', 'like', "%{$nama}%");
        }

        return response()->json($q->get());
    }

    public function poAnggaranDetail(Request $request, PoAnggaran $po_anggaran)
    {
        $po_anggaran->load(['items', 'department', 'bank', 'bisnisPartner']);

        $onlyOutstanding = $request->boolean('only_outstanding');

        $items = $po_anggaran->items;
        $totalOutstanding = 0;

        foreach ($items as $item) {
            $realisasiSum = RealisasiItem::where('po_anggaran_item_id', $item->id)
                ->whereHas('realisasi', function ($q) {
                    $q->where('status', '!=', 'Canceled');
                })
                ->sum('realisasi');

            $subtotal = (float)$item->subtotal;
            if (!$subtotal) {
                $subtotal = (float)$item->harga * (float)$item->qty;
            }

            $outstanding = max($subtotal - (float)$realisasiSum, 0);
            $item->realized = (float)$realisasiSum;
            $item->outstanding = $outstanding;
            $totalOutstanding += $outstanding;
        }

        if ($onlyOutstanding) {
            $items = $items->filter(function ($item) {
                return ($item->outstanding ?? 0) > 0;
            })->values();
        }

        $po_anggaran->setRelation('items', $items);
        $po_anggaran->outstanding = $totalOutstanding;

        return response()->json($po_anggaran);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = strtolower(optional($user->role)->name ?? '');

        // Build base query with DepartmentScope by default; for Staff roles, bypass scope and restrict to own-created
        if (in_array($userRole, ['staff toko','staff digital marketing'], true)) {
            $query = Realisasi::withoutGlobalScope(\App\Scopes\DepartmentScope::class)
                ->with(['department','poAnggaran'])
                ->where('created_by', $user->id);
        } else {
            $query = Realisasi::query()->with(['department','poAnggaran']);
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
        return Inertia::render('realisasi/Create', [
            'departments' => Department::select('id','name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_anggaran_id' => 'required|exists:po_anggarans,id',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'required|in:Transfer,Kredit',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'required|string',
            'no_rekening' => 'required|string',
            'total_anggaran' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.po_anggaran_item_id' => 'nullable|exists:po_anggaran_items,id',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
            'items.*.realisasi' => 'required|numeric|min:0',
        ]);
        // Tentukan status berdasarkan tombol yang diklik dan role pembuat
        $submitType = $request->get('submit_type'); // 'draft' atau 'send'

        $realisasi = new Realisasi($validated);
        $realisasi->created_by = Auth::id();

        // Hitung total_realisasi dari items
        $realisasi->total_realisasi = collect($validated['items'] ?? [])->sum(function ($it) {
            return (float)($it['realisasi'] ?? 0);
        });

        if ($submitType === 'draft') {
            // Simpan sebagai Draft, bisa diedit lagi
            $realisasi->status = 'Draft';
        } else {
            // Kirim langsung: isi nomor & tanggal + status awal tergantung role creator & departemen
            $user = Auth::user();
            $creatorRole = optional($user->role)->name;

            // Generate nomor realisasi berdasarkan department
            $dept = $realisasi->department ?: Department::find($realisasi->department_id);
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            $realisasi->no_realisasi = DocumentNumberService::generateNumber(
                'Realisasi',
                null,
                (int)$realisasi->department_id,
                (string)$alias
            );
            // Tanggal dokumen = hari ini
            $realisasi->tanggal = now();

            $deptName = $dept?->name ?? '';
            $isSpecialDept = ($deptName === 'Zi&Glo' || $deptName === 'Human Greatness');

            // Default In Progress untuk semua kecuali role tertentu
            $initialStatus = 'In Progress';

            if ($creatorRole === 'Kabag') {
                // Kabag creator: langsung Approved untuk semua departemen
                $initialStatus = 'Approved';
            } elseif ($creatorRole === 'Kepala Toko') {
                // Kepala Toko creator:
                // - Departemen Zi&Glo / Human Greatness: langsung Approved
                // - Departemen lain: langsung Verified (nanti disetujui Kadiv)
                $initialStatus = $isSpecialDept ? 'Approved' : 'Verified';
            }

            $realisasi->status = $initialStatus;
        }

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
        if ($submitType === 'draft') {
            return redirect()->route('realisasi.index')->with('success', 'Draft Realisasi disimpan');
        }

        return redirect()->route('realisasi.index')->with('success', 'Realisasi berhasil dikirim');
    }

    public function edit(Realisasi $realisasi)
    {
        $realisasi->load(['items', 'poAnggaran']);
        return Inertia::render('realisasi/Edit', [
            'realisasi' => $realisasi,
            'departments' => Department::select('id','name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Realisasi $realisasi)
    {
        if (!$realisasi->canBeEdited()) abort(403);

        $validated = $request->validate([
            'po_anggaran_id' => 'required|exists:po_anggarans,id',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'required|in:Transfer,Kredit',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
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

            // Set initial status per business rules berdasarkan role creator & departemen
            $creatorRole = optional($row->creator)->role->name ?? null;
            $deptName = $row->department?->name ?? '';
            $isSpecialDept = ($deptName === 'Zi&Glo' || $deptName === 'Human Greatness');

            // Default In Progress untuk semua kecuali role tertentu
            $initialStatus = 'In Progress';

            // Kabag creator => Approved langsung (semua departemen)
            if ($creatorRole === 'Kabag') {
                $initialStatus = 'Approved';
            } elseif ($creatorRole === 'Kepala Toko') {
                // Kepala Toko creator:
                // - departemen Zi&Glo / Human Greatness: langsung Approved
                // - departemen lain: langsung Verified (nanti disetujui Kadiv)
                $initialStatus = $isSpecialDept ? 'Approved' : 'Verified';
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
        $realisasi->load([
            'items',
            'department',
            'bank',
            'poAnggaran.items',
            'poAnggaran.department',
            'bisnisPartner',
            'bisnisPartner.bank',
        ]);

        $progress = $this->workflow->getApprovalProgressForRealisasi($realisasi);
        $user = Auth::user();
        $userRole = optional($user->role)->name ?? '';

        return Inertia::render('realisasi/Detail', [
            'realisasi' => $realisasi,
            'progress' => $progress,
            'userRole' => $userRole,
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
        try {
            \Illuminate\Support\Facades\Log::info('Realisasi Download - Starting download for Realisasi:', [
                'realisasi_id' => $realisasi->id,
                'user_id' => \Illuminate\Support\Facades\Auth::id()
            ]);

            if ($realisasi->status === 'Canceled') abort(403);

            $realisasi->load(['items', 'department', 'bank', 'poAnggaran', 'poAnggaran.department', 'creator.role']);

            // Calculate total
            $total = $realisasi->total_realisasi ?? 0;

            // Calculate sisa (difference between anggaran and realisasi)
            $sisa = ($realisasi->total_anggaran ?? 0) - ($realisasi->total_realisasi ?? 0);

            // Format date in Indonesian
            $tanggal = $realisasi->tanggal
                ? \Carbon\Carbon::parse($realisasi->tanggal)->locale('id')->translatedFormat('d F Y')
                : \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y');

            // Clean filename - remove invalid characters
            $filename = 'Realisasi_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $realisasi->no_realisasi ?? 'Draft') . '.pdf';

            \Illuminate\Support\Facades\Log::info('Realisasi Download - Generated filename:', ['filename' => $filename]);

            // Use base64 encoded images for PDF to avoid path issues
            $logoSrc = $this->getBase64Image('images/company-logo.png');
            $signatureSrc = $this->getBase64Image('images/signature.png');
            $approvedSrc = $this->getBase64Image('images/approved.png');

            // Prepare signature boxes berdasarkan workflow Realisasi
            $progress = $this->workflow->getApprovalProgressForRealisasi($realisasi);
            $signatureBoxes = [];

            // Department dokumen Realisasi
            $docDeptName = optional($realisasi->department)->name ?? '';
            $isBrandMgrDept = in_array($docDeptName, ['Human Greatness', 'Zi&Glo']);

            // 1. Dibuat Oleh (always)
            $creatorRole = optional(optional($realisasi->creator)->role)->name ?? '-';
            if ($creatorRole === 'Kepala Toko' && $isBrandMgrDept) {
                $creatorRole = 'Brand Manager';
            }

            $signatureBoxes[] = [
                'title' => 'Dibuat Oleh',
                'stamp' => $signatureSrc,
                'name' => optional($realisasi->creator)->name ?? '',
                'role' => $creatorRole,
                'date' => $realisasi->created_at ? \Carbon\Carbon::parse($realisasi->created_at)->format('d-m-Y') : '',
            ];

            // 2+. Box lainnya mengikuti step pada workflow (Verified / Approved)
            $labelMap = [
                'verified' => 'Diverifikasi Oleh',
                'approved' => 'Disetujui Oleh',
            ];

            foreach ($progress as $step) {
                $stepKey = $step['step'] ?? null;
                $title = $labelMap[$stepKey] ?? ucfirst((string)$stepKey);

                // Tentukan kapan stamp "approved" tampil di PDF
                $stamp = null;
                if ($stepKey === 'verified' && in_array($realisasi->status, ['Verified', 'Approved'], true)) {
                    $stamp = $approvedSrc;
                }
                if ($stepKey === 'approved' && $realisasi->status === 'Approved') {
                    $stamp = $approvedSrc;
                }

                $displayRole = $step['role'] ?? '-';
                if ($displayRole === 'Kepala Toko' && $isBrandMgrDept) {
                    $displayRole = 'Brand Manager';
                }

                $signatureBoxes[] = [
                    'title' => $title,
                    'stamp' => $stamp,
                    'name' => $step['role'] ?? '-',
                    'role' => $displayRole,
                    'date' => $realisasi->status === 'Approved' || $realisasi->status === 'Verified' ? $tanggal : '',
                ];
            }

            // Create PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('realisasi_pdf', [
                'realisasi' => $realisasi,
                'tanggal' => $tanggal,
                'total' => $total,
                'sisa' => $sisa,
                'logoSrc' => $logoSrc,
                'approvedSrc' => $approvedSrc,
                'signatureSrc' => $signatureSrc,
                'signatureBoxes' => $signatureBoxes,
            ])
            ->setOptions(config('dompdf.options'))
            ->setPaper('a4', 'portrait');

            \Illuminate\Support\Facades\Log::info('Realisasi Download - PDF generated successfully, returning download response');

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Realisasi Download - Error occurred:', [
                'realisasi_id' => $realisasi->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }
}
