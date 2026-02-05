<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Realisasi;
use App\Models\Department;
use App\Models\PoAnggaran;
use App\Models\RealisasiLog;
use Illuminate\Http\Request;
use App\Models\RealisasiItem;
use Illuminate\Validation\Rule;
use App\Models\RealisasiDocument;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\Storage;
use App\Services\ApprovalWorkflowService;
use App\Services\RealisasiWhatsappNotifier;

class RealisasiController extends Controller
{
    protected ApprovalWorkflowService $workflow;
    protected RealisasiWhatsappNotifier $realisasiWhatsappNotifier;

    public function __construct(ApprovalWorkflowService $workflow, RealisasiWhatsappNotifier $realisasiWhatsappNotifier)
    {
        $this->workflow = $workflow;
        $this->realisasiWhatsappNotifier = $realisasiWhatsappNotifier;
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
            ->select(
                'id',
                'no_po_anggaran',
                'tanggal',
                'department_id',
                'perihal_id',
                'nominal',
                'status',
                'created_at',
                'nama_rekening',
                'bank_id',
                'bisnis_partner_id'
            )
            // Hanya tampilkan PO Anggaran yang sudah berstatus Approved
            ->where('status', 'Approved')
            // Exclude PO Anggaran yang sudah punya Realisasi dengan status selain Canceled
            ->whereNotIn('id', function ($sub) {
                $sub->select('po_anggaran_id')
                    ->from('realisasis')
                    ->whereNull('deleted_at')
                    ->where('status', '!=', 'Canceled');
            })
            ->with(['perihal'])
            ->orderByDesc('created_at')
            ->limit(100);

        if ($request->filled('department_id')) {
            $departmentId = $request->get('department_id');
            static $allDepartmentId = null;
            if ($allDepartmentId === null) {
                $allDepartmentId = Department::whereRaw('LOWER(name) = ?', ['all'])->value('id');
            }

            $q->where(function ($subQuery) use ($departmentId, $allDepartmentId) {
                $subQuery->where('department_id', $departmentId);
                if ($allDepartmentId) {
                    $subQuery->orWhere('department_id', $allDepartmentId);
                }
            });
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

        $pos = $q->get();

        // Hitung outstanding per PO Anggaran agar bisa ditampilkan di modal seleksi Realisasi
        foreach ($pos as $po) {
            $items = $po->items;
            $totalOutstanding = 0;

            foreach ($items as $item) {
                $realisasiSum = RealisasiItem::where('po_anggaran_item_id', $item->id)
                    ->whereHas('realisasi', function ($q) {
                        $q->where('status', '!=', 'Canceled');
                    })
                    ->sum('realisasi');

                $subtotal = (float) $item->subtotal;
                if (!$subtotal) {
                    $subtotal = (float) $item->harga * (float) $item->qty;
                }

                $outstanding = max($subtotal - (float) $realisasiSum, 0);
                $totalOutstanding += $outstanding;
            }

            $po->outstanding = $totalOutstanding;
        }

        return response()->json($pos);
    }

    public function poAnggaranDetail(Request $request, PoAnggaran $po_anggaran)
    {
        // Tambahkan relasi perihal agar FE bisa menampilkan nama perihal di kartu Informasi PO Anggaran
        $po_anggaran->load(['items', 'department', 'bank', 'bisnisPartner', 'perihal']);

        $onlyOutstanding = $request->boolean('only_outstanding');
        $excludeRealisasiId = $request->input('exclude_realisasi_id');

        $items = $po_anggaran->items;
        $totalOutstanding = 0;

        foreach ($items as $item) {
            $realisasiSum = RealisasiItem::where('po_anggaran_item_id', $item->id)
                ->whereHas('realisasi', function ($q) use ($excludeRealisasiId) {
                    $q->where('status', '!=', 'Canceled');
                    if ($excludeRealisasiId) {
                        $q->where('id', '!=', $excludeRealisasiId);
                    }
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
                ->with(['department', 'poAnggaran', 'bisnisPartner'])
                ->where('created_by', $user->id);
        } else {
            $query = Realisasi::query()->with(['department', 'poAnggaran', 'bisnisPartner']);
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

        // Date range filter
        // 1) New API: tanggal_start / tanggal_end (align with PO & approval modules)
        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
        } elseif ($request->filled('tanggal_start')) {
            $query->where('tanggal', '>=', $request->tanggal_start);
        } elseif ($request->filled('tanggal_end')) {
            $query->where('tanggal', '<=', $request->tanggal_end);
        } else {
            // 2) Backward compatibility: old `date[]` array parameter
            $date = $request->get('date');
            if ($date && is_array($date) && count($date) === 2) {
                $query->whereBetween('tanggal', [$date[0], $date[1]]);
            }
        }

        $perPage = (int)($request->get('per_page') ?? 10);
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('realisasi/Index', [
            'realisasis' => $data,
            'filters' => $request->all(),
            'departments' => DepartmentService::getOptionsForFilter(),
            'columns' => [
                ['key' => 'no_realisasi', 'label' => 'No. Realisasi', 'checked' => true, 'sortable' => true],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'checked' => true, 'sortable' => true],
                ['key' => 'no_po_anggaran', 'label' => 'No. PO Anggaran', 'checked' => true],
                ['key' => 'department', 'label' => 'Departemen', 'checked' => true],
                ['key' => 'metode_pembayaran', 'label' => 'Metode Pembayaran', 'checked' => true],
                ['key' => 'bisnis_partner', 'label' => 'Bisnis Partner', 'checked' => true],
                ['key' => 'total_anggaran', 'label' => 'Total Anggaran', 'checked' => true, 'sortable' => true],
                ['key' => 'total_realisasi', 'label' => 'Total Realisasi', 'checked' => true, 'sortable' => true],
                // Kolom Sisa (Total Anggaran - Total Realisasi)
                ['key' => 'sisa', 'label' => 'Sisa', 'checked' => true, 'sortable' => false],
                ['key' => 'status', 'label' => 'Status', 'checked' => true, 'sortable' => true],
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('realisasi/Create', [
            'departments' => DepartmentService::getOptionsForForm(),
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
            'items.*.keterangan_realisasi' => 'nullable|string',
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
                // Kabag creator: status awal selalu Verified (nanti disetujui Direksi)
                $initialStatus = 'Verified';
            } elseif ($creatorRole === 'Kepala Toko') {
                // Kepala Toko creator (semua departemen): status awal Verified
                // - Departemen biasa: lalu disetujui Kadiv
                // - Zi&Glo / Human Greatness: lalu disetujui Direksi
                $initialStatus = 'Verified';
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

        try {
            $this->realisasiWhatsappNotifier->notifyFirstApproverOnCreated($realisasi);
        } catch (\Throwable $e) {
            Log::error('Realisasi store - failed to send WhatsApp notification for first approver', [
                'realisasi_id' => $realisasi->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('realisasi.index')->with('success', 'Realisasi berhasil dikirim');
    }

    /**
     * JSON endpoint: simpan Realisasi sebagai Draft dan kembalikan ID.
     * Dipakai oleh flow SPA (Create.vue) agar bisa upload dokumen sebelum kirim.
     */
    public function storeDraft(Request $request)
    {
        // Untuk draft, izinkan form belum lengkap. Hanya department yang wajib,
        // field lain boleh diisi nanti sebelum dikirim.
        $request->merge([
            'po_anggaran_id' => $request->input('po_anggaran_id') === '' ? null : $request->input('po_anggaran_id'),
            'bisnis_partner_id' => $request->input('bisnis_partner_id') === '' ? null : $request->input('bisnis_partner_id'),
            'credit_card_id' => $request->input('credit_card_id') === '' ? null : $request->input('credit_card_id'),
            'bank_id' => $request->input('bank_id') === '' ? null : $request->input('bank_id'),
        ]);
        $validated = $request->validate([
            'po_anggaran_id' => 'nullable|exists:po_anggarans,id',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'nullable|in:Transfer,Kredit',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'nullable|string',
            'no_rekening' => 'nullable|string',
            'total_anggaran' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.po_anggaran_item_id' => 'nullable|exists:po_anggaran_items,id',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'nullable|numeric|min:0',
            'items.*.qty' => 'nullable|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
            'items.*.realisasi' => 'nullable|numeric|min:0',
        ]);
        // Reuse existing Draft for this user + department + PO (if any) to avoid
        // creating duplicate drafts when the frontend triggers multiple requests.
        $userId = Auth::id();

        $query = Realisasi::query()
            ->where('status', 'Draft')
            ->where('created_by', $userId)
            ->where('department_id', $validated['department_id']);

        // Jika ada po_anggaran_id, jadikan bagian dari kunci draft
        if (!empty($validated['po_anggaran_id'])) {
            $query->where('po_anggaran_id', $validated['po_anggaran_id']);
        }

        $realisasi = $query->first();

        if ($realisasi) {
            // Update draft yang sudah ada
            $realisasi->fill($validated);
            $realisasi->updated_by = $userId;
        } else {
            // Buat draft baru jika belum ada
            $realisasi = new Realisasi($validated);
            $realisasi->created_by = $userId;
            $realisasi->status = 'Draft';
        }

        $realisasi->total_realisasi = collect($validated['items'] ?? [])->sum(function ($it) {
            return (float)($it['realisasi'] ?? 0);
        });
        $realisasi->save();

        // Sync items: hapus item lama dan buat ulang dari payload terbaru
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

        // Log hanya dibuat sekali saat draft pertama kali dibuat; untuk update berikutnya
        // cukup mengandalkan log dari updateDraft() jika diperlukan.
        if (!$realisasi->wasRecentlyCreated) {
            // nothing; avoid spam logs
        } else {
            RealisasiLog::create([
                'realisasi_id' => $realisasi->id,
                'action' => 'created',
                'meta' => null,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'id' => $realisasi->id]);
    }

    public function edit(Realisasi $realisasi)
    {
        $realisasi->load(['items', 'poAnggaran', 'documents']);
        return Inertia::render('realisasi/Edit', [
            'realisasi' => $realisasi,
            'departments' => DepartmentService::getOptionsForForm(),
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

    /**
     * JSON endpoint: update Draft Realisasi dan kembalikan ID.
     * Dipakai oleh flow SPA (Create.vue) untuk update draft sebelum kirim.
     */
    public function updateDraft(Request $request, Realisasi $realisasi)
    {
        if (!$realisasi->canBeEdited()) abort(403);

        $request->merge([
            'po_anggaran_id' => $request->input('po_anggaran_id') === '' ? null : $request->input('po_anggaran_id'),
            'bisnis_partner_id' => $request->input('bisnis_partner_id') === '' ? null : $request->input('bisnis_partner_id'),
            'credit_card_id' => $request->input('credit_card_id') === '' ? null : $request->input('credit_card_id'),
            'bank_id' => $request->input('bank_id') === '' ? null : $request->input('bank_id'),
        ]);

        $validated = $request->validate([
            // Untuk update draft, gunakan aturan yang sama longgarnya dengan storeDraft:
            // hanya department_id yang wajib, field lain boleh diisi nanti sebelum dikirim.
            'po_anggaran_id' => 'nullable|exists:po_anggarans,id',
            'department_id' => 'required|exists:departments,id',
            'metode_pembayaran' => 'nullable|in:Transfer,Kredit',
            'bisnis_partner_id' => 'nullable|exists:bisnis_partners,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'bank_id' => 'nullable|exists:banks,id',
            'nama_rekening' => 'nullable|string',
            'no_rekening' => 'nullable|string',
            'total_anggaran' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:realisasi_items,id',
            'items.*.jenis_pengeluaran_id' => 'nullable|exists:pengeluarans,id',
            'items.*.jenis_pengeluaran_text' => 'nullable|string',
            'items.*.keterangan' => 'nullable|string',
            'items.*.harga' => 'nullable|numeric|min:0',
            'items.*.qty' => 'nullable|numeric|min:0',
            'items.*.satuan' => 'nullable|string',
            'items.*.realisasi' => 'nullable|numeric|min:0',
        ]);

        $realisasi->fill($validated);
        $realisasi->updated_by = Auth::id();
        $realisasi->total_realisasi = collect($validated['items'] ?? [])->sum(function ($it) { return (float)($it['realisasi'] ?? 0); });
        // Pastikan tetap Draft sampai dikirim melalui endpoint send()
        if ($realisasi->status !== 'Draft') {
            $realisasi->status = 'Draft';
        }
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

        return response()->json(['success' => true, 'id' => $realisasi->id]);
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

            // Dokumen pendukung wajib:
            // - Jenis standar: transport, konsumsi, hotel, uang_saku
            // - Hanya yang di-ceklis (active = true) yang dianggap wajib
            // - Jenis "lainnya" tidak pernah diwajibkan walaupun di-ceklis
            try {
                $standardTypes = ['transport', 'konsumsi', 'hotel', 'uang_saku'];

                // Ambil seluruh dokumen standar untuk Realisasi ini
                $docRows = $row->documents()
                    ->whereIn('type', $standardTypes)
                    ->get(['type', 'active', 'path']);

                // Secara default, yang wajib adalah dokumen standar yang aktif di database
                $requiredTypes = $docRows
                    ->filter(function ($d) { return (bool) $d->active; })
                    ->pluck('type')
                    ->all();

                // Untuk kompatibilitas dengan alur create/edit yang mengirim documents_active,
                // jika daftar tersebut ada, batasi kewajiban hanya pada jenis yang termasuk di dalamnya.
                $activesFromRequest = (array) ($request->input('documents_active') ?? []);
                if (!empty($activesFromRequest)) {
                    $requiredTypes = array_values(array_intersect($requiredTypes, $activesFromRequest));
                }

                if (!empty($requiredTypes)) {
                    // Dokumen dianggap sudah di-upload bila active = true dan path tidak kosong
                    $uploadedTypes = $docRows
                        ->filter(function ($d) { return (bool) $d->active && !empty($d->path); })
                        ->pluck('type')
                        ->all();

                    $missingTypes = array_values(array_diff($requiredTypes, $uploadedTypes));
                    if (!empty($missingTypes)) {
                        $errors[] = 'Dokumen pendukung belum lengkap';
                    }
                }
            } catch (\Throwable $e) {
                // Jangan blokir proses jika ada error tak terduga pada validasi dokumen, tapi log jika perlu
            }

            if ($errors) {
                $failed[] = ['id' => $row->id, 'errors' => $errors, 'no_realisasi' => $row->no_realisasi];
                continue;
            }

            // Assign number & date (preserve existing number when resending)
            $dept = $row->department;
            $alias = $dept?->alias ?? ($dept?->name ?? 'DEPT');
            if (empty($row->no_realisasi)) {
                $row->no_realisasi = DocumentNumberService::generateNumber('Realisasi', null, (int)$row->department_id, (string)$alias);
            }
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

        $updatedCount = count($updated);
        if ($updatedCount > 0) {
            $message = $updatedCount . ' Realisasi berhasil dikirim!';
        } else {
            $message = 'Tidak ada Realisasi yang berhasil dikirim.';
        }

        // Jika dipanggil dari SPA (axios) yang mengharapkan JSON, kembalikan JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => empty($failed) && $updatedCount > 0,
                'updated' => $updated,
                'failed' => $failed,
                'message' => $message,
            ]);
        }

        // Default: behavior lama menggunakan redirect + flash message
        return back()->with([
            'updated_realisasis' => $updated,
            'failed_realisasis' => $failed,
            'success' => $message,
        ]);
    }

    /** Upload dokumen pendukung Realisasi (PDF) */
    public function uploadDocument(Request $request, string $id)
    {
        $realisasi = Realisasi::findOrFail($id);

        $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in(['transport','konsumsi','hotel','uang_saku','lainnya']),
            ],
            'file' => ['required','file','mimes:pdf','max:10240'], // 10MB
        ]);

        $type = $request->input('type');
        $file = $request->file('file');

        $path = $file->store('realisasi-documents');

        // Replace existing document of the same type if present
        $doc = RealisasiDocument::where('realisasi_id', $realisasi->id)
            ->where('type', $type)
            ->first();

        if ($doc && $doc->path) {
            try { Storage::delete($doc->path); } catch (\Throwable $e) {}
        }

        if (!$doc) {
            $doc = new RealisasiDocument();
            $doc->realisasi_id = $realisasi->id;
            $doc->type = $type;
        }

        $doc->active = true;
        $doc->path = $path;
        $doc->original_name = $file->getClientOriginalName();
        $doc->save();

        return back()->with('success', 'Dokumen Realisasi berhasil diunggah');
    }

    /** Set status aktif/non-aktif dokumen Realisasi per jenis */
    public function setDocumentActive(Request $request, string $id)
    {
        $realisasi = Realisasi::findOrFail($id);
        $data = $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in(['transport','konsumsi','hotel','uang_saku','lainnya']),
            ],
            'active' => ['required','boolean'],
        ]);

        $doc = RealisasiDocument::where('realisasi_id', $realisasi->id)
            ->where('type', $data['type'])
            ->first();

        if (!$doc) {
            $doc = new RealisasiDocument();
            $doc->realisasi_id = $realisasi->id;
            $doc->type = $data['type'];
        }

        $doc->active = (bool) $data['active'];
        $doc->save();

        return back(303);
    }

    /** Download dokumen Realisasi */
    public function downloadDocument(RealisasiDocument $document)
    {
        if (empty($document->path) || !Storage::exists($document->path)) {
            abort(404);
        }

        $filename = $document->original_name ?: 'document.pdf';
        return Storage::download($document->path, $filename);
    }

    /** View dokumen Realisasi inline di browser */
    public function viewDocument(RealisasiDocument $document)
    {
        if (empty($document->path) || !Storage::exists($document->path)) {
            abort(404);
        }

        $mime = Storage::mimeType($document->path) ?: 'application/pdf';
        $filename = $document->original_name ?: 'document.pdf';
        $stream = Storage::readStream($document->path);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '\"', $filename) . '"',
        ]);
    }

    /** Hapus dokumen Realisasi */
    public function deleteDocument(RealisasiDocument $document)
    {
        if ($document->path) {
            try { Storage::delete($document->path); } catch (\Throwable $e) {}
        }
        $document->delete();

        return back();
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

    /**
     * Close an approved Realisasi (set status to Closed).
     * Only creator or Admin may perform this action.
     */
    public function close(Request $request, Realisasi $realisasi)
    {
        $user = Auth::user();

        if ($realisasi->status !== 'Approved') {
            return back()->with('error', 'Hanya Realisasi berstatus Approved yang dapat ditutup.');
        }

        $userRole = strtolower(optional($user->role)->name ?? '');
        $isAdmin = $userRole === 'admin';
        $isCreator = (int) ($realisasi->created_by ?? 0) === (int) $user->id;

        if (!$isAdmin && !$isCreator) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menutup Realisasi ini.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        $realisasi->status = 'Closed';
        $realisasi->closed_reason = $reason;
        $realisasi->updated_by = $user->id;
        $realisasi->save();

        RealisasiLog::create([
            'realisasi_id' => $realisasi->id,
            'action' => 'closed',
            'meta' => ['reason' => $reason],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Realisasi berhasil ditutup.');
    }

    public function show(Realisasi $realisasi)
    {
        $realisasi->load([
            'items',
            'department',
            'bank',
            'poAnggaran.items',
            'poAnggaran.department',
            'poAnggaran.perihal',
            'bisnisPartner',
            'bisnisPartner.bank',
            'documents',
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
        $realisasi->loadMissing('department');

        $logs = RealisasiLog::where('realisasi_id', $realisasi->id)
            ->with(['user.department', 'user.role'])
            ->orderByDesc('created_at')
            ->get();
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
                'validated' => 'Divalidasi Oleh',
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

                $displayRole = $step['role'] ?? '';
                if ($displayRole === 'Kepala Toko' && $isBrandMgrDept) {
                    $displayRole = 'Brand Manager';
                }

                // Ambil nama dan tanggal aktual dari progress (completed_by & completed_at) jika tersedia
                $completedBy = $step['completed_by']['name'] ?? null;
                $completedAt = $step['completed_at'] ?? null;

                // Jika step belum pernah diproses (belum ada completed_by), jangan tampilkan nama/tanggal
                // namun tetap tampilkan role agar label posisi persetujuan terlihat di PDF
                if (!$completedBy) {
                    $name = '';
                    $date = '';
                } else {
                    $name = $completedBy;

                    $date = '';
                    if ($completedAt) {
                        try {
                            $date = \Carbon\Carbon::parse($completedAt)
                                ->locale('id')
                                ->translatedFormat('d F Y');
                        } catch (\Throwable $e) {
                            $date = $tanggal;
                        }
                    }
                }

                $signatureBoxes[] = [
                    'title' => $title,
                    'stamp' => $stamp,
                    'name' => $name,
                    'role' => $displayRole,
                    'date' => $date,
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
