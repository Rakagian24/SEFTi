<?php

namespace App\Http\Controllers;

use App\Models\MemoPembayaran;
use App\Models\Department;
use App\Models\Perihal;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Bank;
use App\Models\Pph;
use App\Services\DepartmentService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MemoPembayaranLog;
use Inertia\Inertia;
use Carbon\Carbon;

class MemoPembayaranController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(\App\Models\MemoPembayaran::class, 'memo_pembayaran');
}

    // List + filter
    public function index(Request $request)
    {
        $user = Auth::user();

        // Use DepartmentScope (do NOT bypass) so 'All' access works and multi-department users are respected
        $query = MemoPembayaran::query()->with(['department', 'perihal', 'purchaseOrders', 'purchaseOrder', 'supplier', 'bank', 'pph']);

        // Filter dinamis
        if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
            $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
}
        if ($request->filled('no_mb')) {
            $query->where('no_mb', 'like', '%'.$request->no_mb.'%');
}
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
}
        if ($request->filled('status')) {
            $query->where('status', $request->status);
}
        if ($request->filled('perihal_id')) {
            $query->where('perihal_id', $request->perihal_id);
}
        if ($request->filled('metode_pembayaran')) {
            $query->where('metode_pembayaran', $request->metode_pembayaran);
}
        // Free text search across common columns
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_mb', 'like', '%'.$search.'%')
                  ->orWhere('detail_keperluan', 'like', '%'.$search.'%')
                  ->orWhere('keterangan', 'like', '%'.$search.'%')
                  ->orWhere('status', 'like', '%'.$search.'%')
                  ->orWhere('tanggal', 'like', '%'.$search.'%')
                  ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ['%'.$search.'%'])
                  ->orWhereHas('department', function ($q) use ($search) {
                      $q->where('name', 'like', '%'.$search.'%');
})
                  ->orWhereHas('perihal', function ($q) use ($search) {
                      $q->where('nama', 'like', '%'.$search.'%');
})
                  ->orWhereHas('purchaseOrders', function ($q) use ($search) {
                      $q->where('no_po', 'like', '%'.$search.'%');
});
});
}

        // Default filter: current month data
        if (!$request->filled('tanggal_start') && !$request->filled('tanggal_end')) {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
}

        // Pagination
        $perPage = $request->get('per_page', 10);
        $memoPembayarans = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get filter options
        $departments = DepartmentService::getOptionsForFilter();
        $perihals = Perihal::where('status', 'active')->orderBy('nama')->get();

        // Get unique values for dynamic filters
        $statusOptions = MemoPembayaran::select('status')->distinct()->pluck('status')->filter();
        $metodePembayaranOptions = MemoPembayaran::select('metode_pembayaran')->distinct()->pluck('metode_pembayaran')->filter();

        return Inertia::render('memo-pembayaran/Index', [
            'memoPembayarans' => $memoPembayarans,
            'filters' => $request->only(['tanggal_start', 'tanggal_end', 'no_mb', 'department_id', 'status', 'perihal_id', 'metode_pembayaran', 'search', 'per_page']),
            'departments' => $departments,
            'perihals' => $perihals,
            'statusOptions' => $statusOptions,
            'metodePembayaranOptions' => $metodePembayaranOptions,
        ]);
}

    public function create()
    {
        $user = Auth::user();
        $perihals = Perihal::where('status', 'active')->orderBy('nama')->get();
        $purchaseOrders = PurchaseOrder::where('status', 'Approved')
            ->with('perihal')
            ->orderBy('created_at', 'desc')
            ->get();
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        return Inertia::render('memo-pembayaran/Create', [
            'perihals' => $perihals,
            'purchaseOrders' => $purchaseOrders,
            'banks' => $banks,
        ]);
}

    /**
     * Search approved Purchase Orders for Memo Pembayaran selection
     */
    public function searchPurchaseOrders(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 20);

        $query = PurchaseOrder::where('status', 'Approved')->with('perihal');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('no_po', 'like', "%{$search
}%")
                  ->orWhereHas('perihal', function($qp) use ($search) {
                      $qp->where('nama', 'like', "%{$search
}%");
});
});
}

        $purchaseOrders = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(function($po) {
                return [
                    'id' => $po->id,
                    'no_po' => $po->no_po,
                    'perihal' => $po->perihal ? ['id' => $po->perihal->id, 'nama' => $po->perihal->nama] : null,
                    'total' => $po->total,
                    'metode_pembayaran' => $po->metode_pembayaran,
                    'bank_id' => $po->bank_id,
                    'nama_rekening' => $po->nama_rekening,
                    'no_rekening' => $po->no_rekening,
                ];
});

        return response()->json([
            'success' => true,
            'data' => $purchaseOrders->items(),
            'current_page' => $purchaseOrders->currentPage(),
            'last_page' => $purchaseOrders->lastPage(),
        ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'perihal_id' => 'required|exists:perihals,id',
            'purchase_order_ids' => 'nullable|array',
            'purchase_order_ids.*' => 'exists:purchase_orders,id',
            'total' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
            // Transfer-only
            'bank_id' => 'required_if:metode_pembayaran,Transfer|nullable|exists:banks,id',
            'nama_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
            'no_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
            // Cek/Giro-only
            'no_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|string',
            'tanggal_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
            'tanggal_cair' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
            // Kredit-only
            'no_kartu_kredit' => 'required_if:metode_pembayaran,Kredit|nullable|string',
            'keterangan' => 'nullable|string|max:65535',
            'action' => 'required|in:draft,send',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $department = $user->department;

            // Determine status and auto-fill fields based on action
            $status = $request->action === 'send' ? 'In Progress' : 'Draft';
            $noMb = null;
            $tanggal = null;

            if ($request->action === 'send') {
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);
                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                $tanggal = now()->toDateString();
}

            $memoPembayaran = MemoPembayaran::create([
                'no_mb' => $noMb,
                'department_id' => $department->id,
                'perihal_id' => $request->perihal_id,
                'detail_keperluan' => 'Memo Pembayaran untuk ' . $request->perihal_id,
                'total' => $request->total,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank_id' => $request->bank_id,
                'nama_rekening' => $request->nama_rekening,
                'no_rekening' => $request->no_rekening,
                'no_giro' => $request->no_giro,
                'no_kartu_kredit' => $request->no_kartu_kredit,
                'tanggal_giro' => $request->tanggal_giro,
                'tanggal_cair' => $request->tanggal_cair,
                'keterangan' => $request->keterangan,
                'diskon' => 0,
                'ppn' => false,
                'ppn_nominal' => 0,
                'pph_nominal' => 0,
                'grand_total' => $request->total,
                'tanggal' => $tanggal,
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Attach purchase orders if provided
            if ($request->purchase_order_ids && is_array($request->purchase_order_ids)) {
                $memoPembayaran->purchaseOrders()->attach($request->purchase_order_ids);
}

            DB::commit();

            $message = $request->action === 'send'
                ? 'Memo Pembayaran berhasil dikirim'
                : 'Memo Pembayaran berhasil disimpan sebagai draft';

            return redirect()->route('memo-pembayaran.index')->with('success', $message);
} catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating Memo Pembayaran: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat membuat Memo Pembayaran']);
}
}

    public function show(MemoPembayaran $memoPembayaran)
    {
        $memoPembayaran->load(['department', 'perihal', 'purchaseOrders', 'supplier', 'bank', 'pph', 'creator', 'updater', 'canceler', 'approver', 'rejecter']);

        return Inertia::render('memo-pembayaran/Detail', [
            'memoPembayaran' => $memoPembayaran,
        ]);
}

    public function edit(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeEdited()) {
            return redirect()->route('memo-pembayaran.index')->with('error', 'Memo Pembayaran tidak dapat diedit');
}

        $perihals = Perihal::where('status', 'active')->orderBy('nama')->get();
        $purchaseOrders = PurchaseOrder::where('status', 'Approved')
            ->with('perihal')
            ->orderBy('created_at', 'desc')
            ->get();
        $banks = Bank::where('status', 'active')->orderBy('nama_bank')->get();

        $memoPembayaran->load(['department', 'perihal', 'purchaseOrders', 'bank']);

        return Inertia::render('memo-pembayaran/Edit', [
            'memoPembayaran' => $memoPembayaran,
            'perihals' => $perihals,
            'purchaseOrders' => $purchaseOrders,
            'banks' => $banks,
        ]);
}

    public function update(Request $request, MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeEdited()) {
            return redirect()->route('memo-pembayaran.index')->with('error', 'Memo Pembayaran tidak dapat diedit');
}

        $request->validate([
            'perihal_id' => 'required|exists:perihals,id',
            'purchase_order_ids' => 'nullable|array',
            'purchase_order_ids.*' => 'exists:purchase_orders,id',
            'total' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:Transfer,Cek/Giro,Kredit',
            // Transfer-only
            'bank_id' => 'required_if:metode_pembayaran,Transfer|nullable|exists:banks,id',
            'nama_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
            'no_rekening' => 'required_if:metode_pembayaran,Transfer|nullable|string',
            // Cek/Giro-only
            'no_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|string',
            'tanggal_giro' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
            'tanggal_cair' => 'required_if:metode_pembayaran,Cek/Giro|nullable|date',
            // Kredit-only
            'no_kartu_kredit' => 'required_if:metode_pembayaran,Kredit|nullable|string',
            'keterangan' => 'nullable|string|max:65535',
            'action' => 'required|in:draft,send',
        ]);

        try {
            DB::beginTransaction();

            // Determine status and auto-fill fields based on action
            $status = $request->action === 'send' ? 'In Progress' : 'Draft';
            $noMb = $memoPembayaran->no_mb;
            $tanggal = $memoPembayaran->tanggal;

            if ($request->action === 'send' && $memoPembayaran->status === 'Draft') {
                $department = $memoPembayaran->department;
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);
                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);
                $tanggal = now()->toDateString();
}

            $memoPembayaran->update([
                'no_mb' => $noMb,
                'perihal_id' => $request->perihal_id,
                'detail_keperluan' => 'Memo Pembayaran untuk ' . $request->perihal_id,
                'total' => $request->total,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank_id' => $request->bank_id,
                'nama_rekening' => $request->nama_rekening,
                'no_rekening' => $request->no_rekening,
                'no_giro' => $request->no_giro,
                'no_kartu_kredit' => $request->no_kartu_kredit,
                'tanggal_giro' => $request->tanggal_giro,
                'tanggal_cair' => $request->tanggal_cair,
                'keterangan' => $request->keterangan,
                'diskon' => 0,
                'ppn' => false,
                'ppn_nominal' => 0,
                'pph_nominal' => 0,
                'grand_total' => $request->total,
                'tanggal' => $tanggal,
                'status' => $status,
                'updated_by' => Auth::id(),
            ]);

            // Update purchase orders relationship
            if ($request->purchase_order_ids && is_array($request->purchase_order_ids)) {
                $memoPembayaran->purchaseOrders()->sync($request->purchase_order_ids);
} else {
                $memoPembayaran->purchaseOrders()->detach();
}

            DB::commit();

            $message = $request->action === 'send'
                ? 'Memo Pembayaran berhasil dikirim'
                : 'Memo Pembayaran berhasil diperbarui';

            return redirect()->route('memo-pembayaran.index')->with('success', $message);
} catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating Memo Pembayaran: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui Memo Pembayaran']);
}
}

    public function destroy(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeDeleted()) {
            return redirect()->route('memo-pembayaran.index')->with('error', 'Memo Pembayaran tidak dapat dibatalkan');
}

        try {
            DB::beginTransaction();

            $memoPembayaran->update([
                'status' => 'Canceled',
                'canceled_by' => Auth::id(),
                'canceled_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('memo-pembayaran.index')->with('success', 'Memo Pembayaran berhasil dibatalkan');
} catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error canceling Memo Pembayaran: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat membatalkan Memo Pembayaran']);
}
}

    public function send(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:memo_pembayarans,id',
        ]);

        try {
            DB::beginTransaction();

            $memoPembayarans = MemoPembayaran::whereIn('id', $request->ids)
                ->where('status', 'Draft')
                ->get();

            if ($memoPembayarans->isEmpty()) {
                return back()->withErrors(['error' => 'Tidak ada Memo Pembayaran yang dapat dikirim']);
}

            foreach ($memoPembayarans as $memoPembayaran) {
                // Generate document number
                $department = $memoPembayaran->department;
                $departmentAlias = $department->alias ?? substr($department->name, 0, 3);

                $noMb = DocumentNumberService::generateNumber('MP', null, $department->id, $departmentAlias);

                $memoPembayaran->update([
                    'no_mb' => $noMb,
                    'tanggal' => now(),
                    'status' => 'In Progress',
                    'updated_by' => Auth::id(),
                ]);

                // Log the send action
                MemoPembayaranLog::create([
                    'memo_pembayaran_id' => $memoPembayaran->id,
                    'action' => 'sent',
                    'description' => 'Memo Pembayaran dikirim dengan nomor ' . $noMb,
                    'user_id' => Auth::id(),
                    'new_values' => ['status' => 'In Progress', 'no_mb' => $noMb, 'tanggal' => now()],
                ]);
}

            DB::commit();

            return back()->with('success', count($memoPembayarans) . ' Memo Pembayaran berhasil dikirim');
} catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sending Memo Pembayaran: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengirim Memo Pembayaran']);
}
}

    public function download(MemoPembayaran $memoPembayaran)
    {
        if (!$memoPembayaran->canBeDownloaded()) {
            return back()->withErrors(['error' => 'Memo Pembayaran tidak dapat diunduh']);
}

        $memoPembayaran->load(['department', 'perihal', 'purchaseOrders', 'supplier', 'bank', 'pph', 'creator', 'approver']);

        $pdf = Pdf::loadView('memo_pembayaran_pdf', [
            'memo' => $memoPembayaran,
            'tanggal' => $memoPembayaran->tanggal ? Carbon::parse($memoPembayaran->tanggal)->isoFormat('D MMMM Y') : '-',
            'logoSrc' => base64_encode(file_get_contents(public_path('images/company-logo.png'))),
            'signatureSrc' => base64_encode(file_get_contents(public_path('images/signature.png'))),
            'approvedSrc' => base64_encode(file_get_contents(public_path('images/approved.png'))),
        ])
        ->setOptions(config('dompdf.options'))
        ->setPaper([0, 0, 595.28, 935.43], 'portrait');

        return $pdf->download('Memo_Pembayaran_' . $memoPembayaran->no_mb . '.pdf');
}

    public function log(MemoPembayaran $memoPembayaran)
    {
        $logs = $memoPembayaran->logs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('memo-pembayaran/Log', [
            'memoPembayaran' => $memoPembayaran,
            'logs' => $logs,
        ]);
}

    public function getPreviewNumber(Request $request)
    {
        // department_id optional; default to current user's department
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $department = $request->filled('department_id')
            ? Department::find($request->department_id)
            : Auth::user()?->department;

        if (!$department) {
            return response()->json(['error' => 'Department tidak valid'], 422);
}

        $departmentAlias = $department->alias ?? substr($department->name ?? '', 0, 3);

        // Use form preview generator to exclude drafts and include current draft context
        $previewNumber = DocumentNumberService::generateFormPreviewNumber('Memo Pembayaran', null, $department->id, $departmentAlias);

        return response()->json(['preview_number' => $previewNumber]);
}
}
