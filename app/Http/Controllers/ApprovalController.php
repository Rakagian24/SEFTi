<?php

namespace App\Http\Controllers {

    use Carbon\Carbon;
    use App\Models\Bpb;
    use App\Models\Role;
    use App\Models\User;
    use Inertia\Inertia;
    use App\Models\BpbLog;
    use App\Models\Realisasi;
    use App\Models\Department;
    use App\Models\PoAnggaran;
    use App\Models\Bpb as _Bpb;
    use App\Models\RealisasiLog;
    use Illuminate\Http\Request;
    use App\Models\PoAnggaranLog;
    use App\Models\PurchaseOrder;
    use App\Models\MemoPembayaran;
    use App\Models\PaymentVoucher;
    use App\Models\PurchaseOrderLog;
    use App\Models\MemoPembayaranLog;
    use App\Models\PaymentVoucherLog;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Support\Facades\DB;
    use App\Services\DepartmentService;
    use Illuminate\Support\Facades\Log;
    use App\Models\PurchaseOrder as _Po;
    use Illuminate\Support\Facades\Auth;
    use App\Models\PaymentVoucher as _Pv;
    use App\Models\MemoPembayaran as _Memo;
    use App\Services\ApprovalWorkflowService;

    class ApprovalController extends Controller
    {
        use ApprovalActionInference;
        protected $approvalWorkflowService;

        public function __construct(ApprovalWorkflowService $approvalWorkflowService)
        {
            $this->approvalWorkflowService = $approvalWorkflowService;
        }

        /**
         * Validate a Payment Voucher (Kadiv step for Pajak)
         */
        public function validatePaymentVoucher(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $paymentVoucher = PaymentVoucher::findOrFail($id);

            if (!$this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $paymentVoucher, 'validate')) {
                return response()->json(['error' => 'Unauthorized to validate this payment voucher'], 403);
            }

            try {
                DB::beginTransaction();

                $paymentVoucher->update([
                    'status' => 'Validated',
                    'validated_by' => $user->id,
                    'validated_at' => now(),
                    'validation_notes' => $request->input('notes', '')
                ]);

                // Log validation activity
                $this->logApprovalActivity($user, $paymentVoucher, 'validated');

                DB::commit();

                return response()->json([
                    'message' => 'Payment Voucher validated successfully',
                    'payment_voucher' => $paymentVoucher->fresh(['validator'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to validate Payment Voucher'], 500);
            }
        }

        /**
         * Display PO Anggaran approval page
         */
        public function poAnggarans()
        {
            $user = Auth::user();
            $departments = DepartmentService::getOptionsForFilter();

            return inertia('approval/PoAnggaranApproval', [
                'departments' => $departments,
                'userRole' => $user->role->name ?? ''
            ]);
        }

        /**
         * Display approval-specific detail page for PO Anggaran
         */
        public function poAnggaranDetail(PoAnggaran $po_anggaran)
        {
            $po_anggaran->load(['items', 'department', 'bank', 'perihal', 'bisnisPartner', 'bisnisPartner.bank', 'creator.role']);
            $user = Auth::user();
            $progress = $this->approvalWorkflowService->getApprovalProgressForPoAnggaran($po_anggaran);

            // Default permission dari workflow
            $canVerify = $this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po_anggaran, 'verify');
            $canValidate = $this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po_anggaran, 'validate');
            $canApprove = $this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po_anggaran, 'approve');
            $canReject = $this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po_anggaran, 'reject');

            // Khusus Admin: hanya tampilkan 1 aksi utama (bertahap), mengikuti pola di PoAnggaranApproval.vue
            $userRole = $user->role->name ?? '';
            if (strcasecmp($userRole, 'Admin') === 0) {
                $status = $po_anggaran->status;
                $creatorRole = optional($po_anggaran->creator->role)->name;

                $primary = null; // 'verify' | 'validate' | 'approve'

                if ($status === 'In Progress') {
                    // DM flow: langsung validate; lainnya verify
                    $primary = ($creatorRole === 'Staff Digital Marketing') ? 'validate' : 'verify';
                } elseif ($status === 'Verified') {
                    // Finance path boleh langsung approve, lainnya validate
                    if (in_array($creatorRole, ['Staff Akunting & Finance', 'Kabag'], true)) {
                        $primary = 'approve';
                    } else {
                        $primary = 'validate';
                    }
                } elseif ($status === 'Validated') {
                    $primary = 'approve';
                }

                // Reset semua dulu, lalu hidupkan hanya yang primary (jika memang diizinkan workflow)
                $origVerify = $canVerify;
                $origValidate = $canValidate;
                $origApprove = $canApprove;

                $canVerify = $canValidate = $canApprove = false;

                if ($primary === 'verify' && $origVerify) {
                    $canVerify = true;
                } elseif ($primary === 'validate' && $origValidate) {
                    $canValidate = true;
                } elseif ($primary === 'approve' && $origApprove) {
                    $canApprove = true;
                }
            }

            return inertia('approval/PoAnggaranApprovalDetail', [
                'poAnggaran' => $po_anggaran,
                'progress' => $progress,
                'userRole' => $userRole ?? ($user->role->name ?? ''),
                'canVerify' => $canVerify,
                'canValidate' => $canValidate,
                'canApprove' => $canApprove,
                'canReject' => $canReject,
            ]);
        }

        /**
         * Display Realisasi approval page
         */
        public function realisasis()
        {
            $user = Auth::user();
            $departments = Department::all();

            return inertia('approval/RealisasiApproval', [
                'departments' => $departments,
                'userRole' => $user->role->name ?? ''
            ]);
        }

        /**
         * Display approval-specific detail page for Realisasi
         */
        public function realisasiDetail(\App\Models\Realisasi $realisasi)
        {
            $realisasi->load(['items', 'department', 'bank', 'poAnggaran', 'poAnggaran.department', 'creator.role']);
            $user = Auth::user();
            $progress = $this->approvalWorkflowService->getApprovalProgressForRealisasi($realisasi);
            $canVerify = $this->approvalWorkflowService->canUserApproveRealisasi($user, $realisasi, 'verify');
            $canApprove = $this->approvalWorkflowService->canUserApproveRealisasi($user, $realisasi, 'approve');

            return inertia('approval/RealisasiApprovalDetail', [
                'realisasi' => $realisasi,
                'progress' => $progress,
                'userRole' => $user->role->name ?? '',
                'canVerify' => $canVerify,
                'canApprove' => $canApprove,
            ]);
        }

        // ================= PO ANGGARAN APPROVAL API =================

        public function getPoAnggaranCount(Request $request)
        {
            try {
                $user = Auth::user();
                if (!$user) return response()->json(['count' => 0]);

                $query = \App\Models\PoAnggaran::query()
                    ->whereIn('status', ['In Progress', 'Verified', 'Validated']);

                $userRole = $user->role->name ?? '';

                // Untuk Admin: hitung semua dokumen sesuai status (bukan hanya yang actionable)
                if ($userRole === 'Admin') {
                    $total = (clone $query)->count();
                    return response()->json(['count' => $total]);
                }

                // Default (non-admin): hanya hitung dokumen yang actionable
                $actionable = 0;
                $query->orderBy('id')
                    ->chunk(500, function ($items) use ($user, &$actionable) {
                        foreach ($items as $po) {
                            foreach (['verify', 'validate', 'approve'] as $act) {
                                if ($this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po, $act)) {
                                    $actionable++;
                                    break;
                                }
                            }
                        }
                    });

                return response()->json(['count' => $actionable]);
            } catch (\Exception $e) {
                return response()->json(['count' => 0]);
            }
        }

        public function getPoAnggarans(Request $request)
        {
            $q = \App\Models\PoAnggaran::query()
                ->with(['department', 'perihal', 'bank', 'bisnisPartner', 'creator.role'])
                ->whereIn('status', ['In Progress', 'Verified', 'Validated'])
                ->orderByDesc('created_at');

            if ($s = $request->get('search')) {
                $q->where(function ($w) use ($s) {
                    $w->where('no_po_anggaran', 'like', "%$s%")
                        ->orWhere('status', 'like', "%$s%")
                        ->orWhere('nominal', 'like', "%$s%")
                        ->orWhere('metode_pembayaran', 'like', "%$s%")
                        ->orWhere('detail_keperluan', 'like', "%$s%")
                        ->orWhere('note', 'like', "%$s%")
                        ->orWhere('nama_rekening', 'like', "%$s%")
                        ->orWhere('no_rekening', 'like', "%$s%")
                        ->orWhere('no_giro', 'like', "%$s%")
                        ->orWhere('tanggal', 'like', "%$s%")
                        ->orWhere('tanggal_giro', 'like', "%$s%")
                        ->orWhere('tanggal_cair', 'like', "%$s%")
                        ->orWhereHas('department', fn($d) => $d->where('name', 'like', "%$s%"))
                        ->orWhereHas('perihal', fn($p) => $p->where('nama', 'like', "%$s%"))
                        ->orWhereHas('bank', fn($b) => $b->where('nama_bank', 'like', "%$s%")->orWhere('singkatan', 'like', "%$s%"))
                        ->orWhereHas('bisnisPartner', fn($bp) => $bp->where('nama_bp', 'like', "%$s%"))
                        ->orWhereHas('creator', fn($u) => $u->where('name', 'like', "%$s%"));
                });
            }
            if ($status = $request->get('status')) {
                $q->where('status', $status);
            }
            if ($dept = $request->get('department_id')) {
                $q->where('department_id', $dept);
            }

            $perPage = (int)($request->get('per_page', 10));
            $currentPage = (int)($request->get('page', 1));

            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            // Untuk Admin: tampilkan semua dokumen sesuai filter (bukan hanya yang actionable)
            if ($userRole === 'Admin') {
                $total = (clone $q)->count();
                $lastPage = (int) max(1, (int) ceil($total / max(1, $perPage)));
                $currentPage = min(max(1, $currentPage), $lastPage);

                $offset = ($currentPage - 1) * $perPage;

                $items = (clone $q)
                    ->skip($offset)
                    ->take($perPage)
                    ->get()
                    ->map(function ($po) {
                        return [
                            'id' => $po->id,
                            'no_po_anggaran' => $po->no_po_anggaran,
                            'status' => $po->status,
                            'tanggal' => optional($po->tanggal)->toDateString(),
                            'tanggal_giro' => optional($po->tanggal_giro)->toDateString(),
                            'tanggal_cair' => optional($po->tanggal_cair)->toDateString(),
                            'metode_pembayaran' => $po->metode_pembayaran,
                            'nama_rekening' => $po->nama_rekening,
                            'no_rekening' => $po->no_rekening,
                            'no_giro' => $po->no_giro,
                            'detail_keperluan' => $po->detail_keperluan,
                            'note' => $po->note,
                            'created_at' => optional($po->created_at)->toDateTimeString(),
                            'nominal' => $po->nominal,
                            'department' => $po->department ? ['id' => $po->department->id, 'name' => $po->department->name] : null,
                            'perihal' => $po->perihal ? ['id' => $po->perihal->id, 'nama' => $po->perihal->nama] : null,
                            'bank' => $po->bank ? ['id' => $po->bank->id, 'nama_bank' => $po->bank->nama_bank, 'singkatan' => $po->bank->singkatan] : null,
                            'bisnisPartner' => $po->bisnisPartner ? ['id' => $po->bisnisPartner->id, 'nama_bp' => $po->bisnisPartner->nama_bp] : null,
                            'creator' => $po->creator ? ['id' => $po->creator->id, 'name' => $po->creator->name, 'role' => ['name' => $po->creator->role->name ?? null]] : null,
                        ];
                    })
                    ->values();

                $from = $total > 0 ? ($offset + 1) : 0;
                $to = $offset + $items->count();

                return response()->json([
                    'data' => $items,
                    'pagination' => [
                        'total' => $total,
                        'per_page' => $perPage,
                        'current_page' => $currentPage,
                        'last_page' => $lastPage,
                        'from' => $from,
                        'to' => $to,
                        'links' => [],
                        'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                        'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                    ],
                ]);
            }

            // Default (non-admin): Actionable-first pagination seperti sebelumnya
            $actionableIds = [];
            (clone $q)
                ->orderBy('id')
                ->chunk(500, function ($items) use (&$actionableIds) {
                    $user = Auth::user();
                    foreach ($items as $po) {
                        foreach (['verify', 'validate', 'approve'] as $act) {
                            if ($this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po, $act)) {
                                $actionableIds[] = $po->id;
                                break;
                            }
                        }
                    }
                });

            $total = count($actionableIds);
            $lastPage = (int) max(1, (int) ceil($total / max(1, $perPage)));
            $currentPage = min(max(1, $currentPage), $lastPage);

            $offset = ($currentPage - 1) * $perPage;
            $pageIds = array_slice($actionableIds, $offset, $perPage);

            $items = collect();
            if (!empty($pageIds)) {
                $items = \App\Models\PoAnggaran::with(['department', 'perihal', 'bank', 'bisnisPartner', 'creator.role'])
                    ->whereIn('id', $pageIds)
                    ->get()
                    ->sortBy(function ($m) use ($pageIds) {
                        return array_search($m->id, $pageIds);
                    })
                    ->map(function ($po) {
                        return [
                            'id' => $po->id,
                            'no_po_anggaran' => $po->no_po_anggaran,
                            'status' => $po->status,
                            'tanggal' => optional($po->tanggal)->toDateString(),
                            'tanggal_giro' => optional($po->tanggal_giro)->toDateString(),
                            'tanggal_cair' => optional($po->tanggal_cair)->toDateString(),
                            'metode_pembayaran' => $po->metode_pembayaran,
                            'nama_rekening' => $po->nama_rekening,
                            'no_rekening' => $po->no_rekening,
                            'no_giro' => $po->no_giro,
                            'detail_keperluan' => $po->detail_keperluan,
                            'note' => $po->note,
                            'created_at' => optional($po->created_at)->toDateTimeString(),
                            'nominal' => $po->nominal,
                            'department' => $po->department ? ['id' => $po->department->id, 'name' => $po->department->name] : null,
                            'perihal' => $po->perihal ? ['id' => $po->perihal->id, 'nama' => $po->perihal->nama] : null,
                            'bank' => $po->bank ? ['id' => $po->bank->id, 'nama_bank' => $po->bank->nama_bank, 'singkatan' => $po->bank->singkatan] : null,
                            'bisnisPartner' => $po->bisnisPartner ? ['id' => $po->bisnisPartner->id, 'nama_bp' => $po->bisnisPartner->nama_bp] : null,
                            'creator' => $po->creator ? ['id' => $po->creator->id, 'name' => $po->creator->name, 'role' => ['name' => $po->creator->role->name ?? null]] : null,
                        ];
                    })
                    ->values();
            }

            $from = $total > 0 ? ($offset + 1) : 0;
            $to = $offset + $items->count();

            return response()->json([
                'data' => $items,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'from' => $from,
                    'to' => $to,
                    'links' => [],
                    'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                    'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                ],
            ]);
        }

        public function verifyPoAnggaran($id)
        {
            $po = \App\Models\PoAnggaran::findOrFail($id);
            $user = Auth::user();
            if (!$this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po, 'verify')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            if ($po->status !== 'In Progress') return response()->json(['error' => 'Invalid status'], 422);
            $po->status = 'Verified';
            $po->save();
            \App\Models\PoAnggaranLog::create(['po_anggaran_id' => $po->id, 'action' => 'verified', 'meta' => null, 'created_by' => $user->id, 'created_at' => now()]);
            return response()->json(['success' => true]);
        }

        public function validatePoAnggaran($id)
        {
            $po = \App\Models\PoAnggaran::findOrFail($id);
            $user = Auth::user();
            if (!$this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po, 'validate')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            // Status precondition is enforced inside canUserApprovePoAnggaran.
            $po->status = 'Validated';
            $po->save();
            \App\Models\PoAnggaranLog::create(['po_anggaran_id' => $po->id, 'action' => 'validated', 'meta' => null, 'created_by' => $user->id, 'created_at' => now()]);
            return response()->json(['success' => true]);
        }

        public function approvePoAnggaran($id)
        {
            $po = \App\Models\PoAnggaran::findOrFail($id);
            $user = Auth::user();
            if (!$this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po, 'approve')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            if (!in_array($po->status, ['Verified', 'Validated'], true)) return response()->json(['error' => 'Invalid status'], 422);
            $po->status = 'Approved';
            $po->approved_by = $user->id;
            $po->save();
            \App\Models\PoAnggaranLog::create(['po_anggaran_id' => $po->id, 'action' => 'approved', 'meta' => null, 'created_by' => $user->id, 'created_at' => now()]);
            return response()->json(['success' => true]);
        }

        public function rejectPoAnggaran($id, Request $request)
        {
            $po = \App\Models\PoAnggaran::findOrFail($id);
            $user = Auth::user();
            if (!$this->approvalWorkflowService->canUserApprovePoAnggaran($user, $po, 'reject')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            if (!in_array($po->status, ['In Progress', 'Verified', 'Validated'], true)) return response()->json(['error' => 'Invalid status'], 422);
            $po->status = 'Rejected';
            $po->rejected_by = $user->id;
            $po->rejection_reason = (string)$request->get('reason', '');
            $po->save();
            \App\Models\PoAnggaranLog::create(['po_anggaran_id' => $po->id, 'action' => 'rejected', 'meta' => ['reason' => $po->rejection_reason], 'created_by' => $user->id, 'created_at' => now()]);
            return response()->json(['success' => true]);
        }

        // ================= REALISASI APPROVAL API =================

        public function getRealisasiCount(Request $request)
        {
            try {
                $user = Auth::user();
                if (!$user) return response()->json(['count' => 0]);

                $query = \App\Models\Realisasi::query()->whereIn('status', ['In Progress', 'Verified']);

                $actionable = 0;
                $query->orderBy('id')->chunk(500, function ($items) use ($user, &$actionable) {
                    foreach ($items as $r) {
                        foreach (['verify', 'approve'] as $act) {
                            if ($this->approvalWorkflowService->canUserApproveRealisasi($user, $r, $act)) {
                                $actionable++;
                                break;
                            }
                        }
                    }
                });

                return response()->json(['count' => $actionable]);
            } catch (\Exception $e) {
                return response()->json(['count' => 0]);
            }
        }

        public function getRealisasis(Request $request)
        {
            $q = \App\Models\Realisasi::query()
                ->with(['department', 'creator.role'])
                ->whereIn('status', ['In Progress', 'Verified'])
                ->orderByDesc('created_at');

            if ($s = $request->get('search')) {
                $q->where(function ($w) use ($s) {
                    $w->where('no_realisasi', 'like', "%$s%")
                        ->orWhere('status', 'like', "%$s%");
                });
            }
            if ($status = $request->get('status')) {
                $q->where('status', $status);
            }
            if ($dept = $request->get('department_id')) {
                $q->where('department_id', $dept);
            }
            // Optional date range filter for approval listing
            if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
                $q->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
            } elseif ($request->filled('tanggal_start')) {
                $q->whereDate('tanggal', '>=', $request->input('tanggal_start'));
            } elseif ($request->filled('tanggal_end')) {
                $q->whereDate('tanggal', '<=', $request->input('tanggal_end'));
            }

            $perPage = (int)($request->get('per_page', 10));
            $currentPage = (int)($request->get('page', 1));

            // Actionable-first pagination
            $actionableIds = [];
            (clone $q)
                ->orderBy('id')
                ->chunk(500, function ($items) use (&$actionableIds) {
                    $user = Auth::user();
                    foreach ($items as $r) {
                        foreach (['verify', 'approve'] as $act) {
                            if ($this->approvalWorkflowService->canUserApproveRealisasi($user, $r, $act)) {
                                $actionableIds[] = $r->id;
                                break;
                            }
                        }
                    }
                });

            $total = count($actionableIds);
            $lastPage = (int) max(1, (int) ceil($total / max(1, $perPage)));
            $currentPage = min(max(1, $currentPage), $lastPage);

            $offset = ($currentPage - 1) * $perPage;
            $pageIds = array_slice($actionableIds, $offset, $perPage);

            $items = collect();
            if (!empty($pageIds)) {
                $items = \App\Models\Realisasi::with(['department', 'creator.role'])
                    ->whereIn('id', $pageIds)
                    ->get()
                    ->sortBy(function ($m) use ($pageIds) {
                        return array_search($m->id, $pageIds);
                    })
                    ->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'no_realisasi' => $r->no_realisasi,
                            'status' => $r->status,
                            'tanggal' => optional($r->tanggal)->toDateString(),
                            'department' => $r->department ? ['id' => $r->department->id, 'name' => $r->department->name] : null,
                            'creator' => $r->creator ? ['id' => $r->creator->id, 'name' => $r->creator->name, 'role' => ['name' => $r->creator->role->name ?? null]] : null,
                        ];
                    })
                    ->values();
            }

            $from = $total > 0 ? ($offset + 1) : 0;
            $to = $offset + $items->count();

            return response()->json([
                'data' => $items,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'from' => $from,
                    'to' => $to,
                    'links' => [],
                    'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                    'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                ],
            ]);
        }

        public function verifyRealisasi($id)
        {
            $doc = \App\Models\Realisasi::findOrFail($id);
            $user = Auth::user();
            if (!$this->approvalWorkflowService->canUserApproveRealisasi($user, $doc, 'verify')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            if ($doc->status !== 'In Progress') return response()->json(['error' => 'Invalid status'], 422);
            $doc->status = 'Verified';
            $doc->save();
            \App\Models\RealisasiLog::create(['realisasi_id' => $doc->id, 'action' => 'verified', 'meta' => null, 'created_by' => $user->id, 'created_at' => now()]);
            return response()->json(['success' => true]);
        }

        public function approveRealisasi($id)
        {
            $doc = \App\Models\Realisasi::findOrFail($id);
            $user = Auth::user();
            if (!$this->approvalWorkflowService->canUserApproveRealisasi($user, $doc, 'approve')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            if (!in_array($doc->status, ['Verified', 'In Progress'], true)) return response()->json(['error' => 'Invalid status'], 422);
            $doc->status = 'Approved';
            $doc->approved_by = $user->id;
            $doc->save();
            \App\Models\RealisasiLog::create(['realisasi_id' => $doc->id, 'action' => 'approved', 'meta' => null, 'created_by' => $user->id, 'created_at' => now()]);
            return response()->json(['success' => true]);
        }
        /**
         * Display the approval dashboard
         */
        public function index()
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            return inertia('approval/Index', [
                'userRole' => $userRole,
                'userPermissions' => $user->role->permissions ?? []
            ]);
        }

        /**
         * Display Purchase Order approval page
         */
        public function purchaseOrders()
        {
            $user = Auth::user();
            $departments = Department::all();

            return inertia('approval/PurchaseOrderApproval', [
                'departments' => $departments,
                'userRole' => $user->role->name ?? ''
            ]);
        }

        /**
         * Get Purchase Orders for approval with counts
         */
        /**
         * Get Purchase Orders for approval with counts
         */
        public function getPurchaseOrders(Request $request): JsonResponse
        {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                $userRole = $user->role->name ?? '';

                if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }

                // Apply DepartmentScope for all roles including Kadiv
                $query = PurchaseOrder::with(['department', 'supplier', 'perihal', 'creator.role'])
                    ->whereNotIn('status', ['Draft', 'Canceled']);

                // Filter workflow
                $this->applyRoleStatusFilter($query, 'purchase_order', $userRole);

                // Direksi special visibility for Zi&Glo/Human Greatness
                if (strtolower($userRole) === 'direksi') {
                    $query->where(function ($q) {
                        $q->orWhere(function ($sub) {
                            // Include Validated for all departments (e.g., DM flow Kadiv -> Direksi)
                            $sub->where('status', 'Validated');
                        })->orWhere(function ($sub) {
                            // Include Verified for Zi&Glo/Human Greatness (e.g., Staff Toko/Kabag flows -> Direksi)
                            $sub->where('status', 'Verified')
                                ->whereHas('department', fn($d) => $d->whereIn('name', ['Zi&Glo', 'Human Greatness']));
                        });
                    });
                }

                Log::info("PO Query Debug", [
                    'user_role'   => $userRole,
                    'sql'         => $query->toSql(),
                    'bindings'    => $query->getBindings(),
                    'total_found' => $query->count()
                ]);

                // Filter tambahan dari request
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('department_id')) {
                    $query->where('department_id', $request->department_id);
                }
                if ($request->filled('tipe_po')) {
                    $query->where('tipe_po', $request->tipe_po);
                }
                if ($request->filled('tanggal_start')) {
                    $query->where('tanggal', '>=', $request->tanggal_start);
                }
                if ($request->filled('tanggal_end')) {
                    $query->where('tanggal', '<=', $request->tanggal_end);
                }
                if ($request->filled('perihal_id')) {
                    $query->where('perihal_id', $request->perihal_id);
                }
                if ($request->filled('metode_pembayaran')) {
                    $query->where('metode_pembayaran', $request->metode_pembayaran);
                }
                if ($request->filled('search')) {
                    // Case-insensitive search by lowercasing both sides
                    $search             = mb_strtolower((string) $request->get('search'));
                    $selectedColumnsRaw = $request->get('search_columns', '');
                    $selectedKeys       = array_filter(array_map('trim', explode(',', (string) $selectedColumnsRaw)));

                    $columnMap = [
                        'no_po'             => ['type' => 'column', 'name' => 'no_po'],
                        'no_invoice'        => ['type' => 'column', 'name' => 'no_invoice'],
                        'tipe_po'           => ['type' => 'column', 'name' => 'tipe_po'],
                        'tanggal'           => ['type' => 'column', 'name' => 'tanggal'],
                        'department'        => ['type' => 'relation', 'relation' => 'department', 'field' => 'name'],
                        'perihal'           => ['type' => 'relation', 'relation' => 'perihal', 'field' => 'nama'],
                        'supplier'          => ['type' => 'relation', 'relation' => 'supplier', 'field' => 'nama_supplier'],
                        'metode_pembayaran' => ['type' => 'column', 'name' => 'metode_pembayaran'],
                        'total'             => ['type' => 'column', 'name' => 'total'],
                        'diskon'            => ['type' => 'column', 'name' => 'diskon'],
                        'ppn'               => ['type' => 'column', 'name' => 'ppn_nominal'],
                        'pph'               => ['type' => 'column', 'name' => 'pph_nominal'],
                        'grand_total'       => ['type' => 'column', 'name' => 'grand_total'],
                        'status'            => ['type' => 'column', 'name' => 'status'],
                        'created_by'        => ['type' => 'relation', 'relation' => 'creator', 'field' => 'name'],
                        'created_at'        => ['type' => 'column', 'name' => 'created_at'],
                    ];

                    if (empty($selectedKeys)) {
                        $selectedKeys = ['no_po', 'supplier', 'perihal'];
                    }

                    $query->where(function ($q) use ($search, $selectedKeys, $columnMap) {
                        foreach ($selectedKeys as $key) {
                            if (!isset($columnMap[$key])) continue;

                            $config = $columnMap[$key];
                            if ($config['type'] === 'column') {
                                $q->orWhereRaw('LOWER(purchase_orders.' . $config['name'] . ") LIKE ?", ['%' . $search . '%']);
                            } elseif ($config['type'] === 'relation') {
                                $q->orWhereHas($config['relation'], function ($sq) use ($config, $search) {
                                    $sq->whereRaw('LOWER(' . $config['field'] . ") LIKE ?", ['%' . $search . '%']);
                                });
                            }
                        }
                    });
                }

                $counts   = $this->getPurchaseOrderCounts($user, $userRole);
                $perPage  = (int) $request->get('per_page', 15);
                $currentPage = (int) $request->get('page', 1);

                // 1) Kumpulkan ID yang actionable
                $actionableIds = [];
                (clone $query)
                    ->with(['department', 'creator.role'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->chunk(500, function ($items) use ($user, &$actionableIds) {
                        foreach ($items as $po) {
                            $action = $this->inferActionForPo($po->status, $po);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApprove($user, $po, $action)) {
                                $actionableIds[] = $po->id;
                            }
                        }
                    });

                $actionableTotal = count($actionableIds);
                $lastPage = (int) max(1, (int) ceil($actionableTotal / max(1, $perPage)));
                $currentPage = min(max(1, $currentPage), $lastPage);

                // 2) Ambil ID untuk halaman saat ini
                $offset = ($currentPage - 1) * $perPage;
                $pageIds = array_slice($actionableIds, $offset, $perPage);

                $items = collect();
                if (!empty($pageIds)) {
                    $poQuery = PurchaseOrder::with(['department', 'supplier', 'perihal', 'creator.role']);
                    $items = $poQuery
                        ->whereIn('id', $pageIds)
                        ->get()
                        ->sortBy(function ($m) use ($pageIds) {
                            return array_search($m->id, $pageIds);
                        })
                        ->values();
                }

                $from = $actionableTotal > 0 ? ($offset + 1) : 0;
                $to = $offset + $items->count();

                // Build Laravel-like pagination links (Previous, pages, Next)
                $prevUrl = $currentPage > 1
                    ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage])
                    : null;
                $nextUrl = $currentPage < $lastPage
                    ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage])
                    : null;
                $links = [];
                $links[] = ['url' => $prevUrl, 'label' => '&laquo; Previous', 'active' => false];
                for ($i = 1; $i <= $lastPage; $i++) {
                    $links[] = [
                        'url' => request()->fullUrlWithQuery(['page' => $i, 'per_page' => $perPage]),
                        'label' => (string) $i,
                        'active' => $i === $currentPage,
                    ];
                }
                $links[] = ['url' => $nextUrl, 'label' => 'Next &raquo;', 'active' => false];

                return response()->json([
                    'data'       => $items,
                    'pagination' => [
                        'current_page'  => $currentPage,
                        'last_page'     => $lastPage,
                        'per_page'      => $perPage,
                        'total'         => $actionableTotal,
                        'from'          => $from,
                        'to'            => $to,
                        'links'         => $links,
                        'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                        'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                    ],
                    'counts' => $counts,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
            }
        }

        /**
         * Get Purchase Order count for dashboard
         */
        public function getPurchaseOrderCount(): JsonResponse
        {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['count' => 0]);
                }

                $userRole = $user->role->name ?? '';

                if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                    return response()->json(['count' => 0]);
                }

                // Base status filter similar to previous implementation
                $statuses = $this->getStatusesForRole('purchase_order', $userRole);
                if ($statuses === []) {
                    return response()->json(['count' => 0]);
                }

                $query = PurchaseOrder::query()
                    ->whereNotIn('status', ['Draft', 'Canceled']);

                if (is_array($statuses)) {
                    if (strtolower($userRole) === 'direksi') {
                        // Direksi special rule:
                        // - include Validated for all departments (DM flow Kadiv -> Direksi)
                        // - include Verified for Zi&Glo/Human Greatness (Creator->Kepala Toko/Kabag -> Direksi)
                        $query->where(function ($q) use ($statuses) {
                            $q->whereIn('status', $statuses)
                                ->orWhere(function ($sub) {
                                    $sub->where('status', 'Validated');
                                })
                                ->orWhere(function ($sub) {
                                    $sub->where('status', 'Verified')
                                        ->whereHas('department', fn($d) => $d->whereIn('name', ['Zi&Glo', 'Human Greatness']));
                                });
                        });
                    } else {
                        $query->whereIn('status', $statuses);
                    }
                }

                // Actionable-only count using workflow permission checks
                $actionable = 0;
                $query->with(['department', 'creator.role'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->chunk(500, function ($items) use ($user, &$actionable) {
                        foreach ($items as $po) {
                            $action = $this->inferActionForPo($po->status, $po);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApprove($user, $po, $action)) {
                                $actionable++;
                            }
                        }
                    });

                return response()->json(['count' => $actionable]);
            } catch (\Exception $e) {
                return response()->json(['count' => 0, 'error' => $e->getMessage()]);
            }
        }

        /**
         * Get Memo Pembayaran count for dashboard
         */
        public function getMemoPembayaranCount(): JsonResponse
        {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['count' => 0]);
                }

                $userRole = $user->role->name ?? '';

                if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                    return response()->json(['count' => 0]);
                }

                $statuses = $this->getStatusesForRole('memo_pembayaran', $userRole);
                if ($statuses === []) {
                    return response()->json(['count' => 0]);
                }

                $query = MemoPembayaran::query();
                if (is_array($statuses)) {
                    $query->whereIn('status', $statuses);
                }

                // Actionable-only count using workflow permission checks
                $actionable = 0;
                $query->with(['department', 'creator.role'])
                    ->orderBy('id')
                    ->chunk(500, function ($items) use ($user, &$actionable) {
                        foreach ($items as $memo) {
                            $action = $this->inferActionForMemo($memo->status, $memo);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memo, $action)) {
                                $actionable++;
                            }
                        }
                    });

                return response()->json(['count' => $actionable]);
            } catch (\Exception $e) {
                return response()->json(['count' => 0, 'error' => $e->getMessage()]);
            }
        }


        /**
         * Approve a Purchase Order
         */
        public function approvePurchaseOrder(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Check if user can approve this PO using workflow service
            if (!$this->approvalWorkflowService->canUserApprove($user, $purchaseOrder, 'approve')) {
                return response()->json(['error' => 'Unauthorized to approve this document'], 403);
            }

            try {
                DB::beginTransaction();

                $purchaseOrder->update([
                    'status' => 'Approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => $request->input('notes', '')
                ]);

                // Log approval activity
                $this->logApprovalActivity($user, $purchaseOrder, 'approved');

                DB::commit();

                return response()->json([
                    'message' => 'Purchase Order approved successfully',
                    'purchase_order' => $purchaseOrder
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to approve Purchase Order'], 500);
            }
        }

        /**
         * Verify a Purchase Order
         */
        public function verifyPurchaseOrder(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Check if user can verify this PO
            if (!$this->approvalWorkflowService->canUserApprove($user, $purchaseOrder, 'verify')) {
                return response()->json(['error' => 'Unauthorized to verify this document'], 403);
            }

            try {
                DB::beginTransaction();

                $purchaseOrder->update([
                    'status' => 'Verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'verification_notes' => $request->input('notes', '')
                ]);

                // Log verification activity
                $this->logApprovalActivity($user, $purchaseOrder, 'verified');

                DB::commit();

                return response()->json([
                    'message' => 'Purchase Order verified successfully',
                    'purchase_order' => $purchaseOrder
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to verify Purchase Order'], 500);
            }
        }

        /**
         * Validate a Purchase Order
         */
        public function validatePurchaseOrder(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Check if user can validate this PO
            if (!$this->approvalWorkflowService->canUserApprove($user, $purchaseOrder, 'validate')) {
                return response()->json(['error' => 'Unauthorized to validate this document'], 403);
            }

            try {
                DB::beginTransaction();

                $purchaseOrder->update([
                    'status' => 'Validated',
                    'validated_by' => $user->id,
                    'validated_at' => now(),
                    'validation_notes' => $request->input('notes', '')
                ]);

                // Log validation activity
                $this->logApprovalActivity($user, $purchaseOrder, 'validated');

                DB::commit();

                return response()->json([
                    'message' => 'Purchase Order validated successfully',
                    'purchase_order' => $purchaseOrder
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to validate Purchase Order'], 500);
            }
        }

        /**
         * Reject a Purchase Order
         */
        public function rejectPurchaseOrder(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            // Check if user can reject this PO using workflow service
            if (!$this->approvalWorkflowService->canUserApprove($user, $purchaseOrder, 'reject')) {
                return response()->json(['error' => 'Unauthorized to reject this document'], 403);
            }

            try {
                DB::beginTransaction();

                $purchaseOrder->update([
                    'status' => 'Rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $request->input('reason', '')
                ]);

                // Log rejection activity
                $this->logApprovalActivity($user, $purchaseOrder, 'rejected');

                DB::commit();

                return response()->json([
                    'message' => 'Purchase Order rejected successfully',
                    'purchase_order' => $purchaseOrder
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to reject Purchase Order'], 500);
            }
        }

        /**
         * Bulk approve Purchase Orders
         */
        public function bulkApprovePurchaseOrders(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $poIds = $request->input('po_ids', []);

            if (empty($poIds)) {
                return response()->json(['error' => 'No Purchase Orders selected'], 400);
            }

            // Fetch selected POs without forcing a single status; we'll decide per-PO below
            $purchaseOrders = PurchaseOrder::whereIn('id', $poIds)->get();

            $approvedCount = 0;
            $errors = [];

            try {
                DB::beginTransaction();

                foreach ($purchaseOrders as $po) {
                    Log::info("Checking approval for PO #{$po->no_po}", [
                        'user_role' => $userRole,
                        'user_id' => $user->id,
                        'po_department_id' => $po->department_id,
                        'user_departments' => $user->departments->pluck('id')->toArray(),
                        'can_approve' => $this->canApproveDocument($user, $userRole, $po)
                    ]);

                    // Only allow actual final approval transitions here
                    $canApprove = $this->approvalWorkflowService->canUserApprove($user, $po, 'approve');

                    $isSpecialDept = in_array(optional($po->department)->name, ['Zi&Glo', 'Human Greatness'], true);
                    $creatorRole = optional(optional($po->creator)->role)->name;

                    $eligibleStatus = in_array($po->status, ['Validated', 'Verified'], true)
                        || ($po->status === 'In Progress' && $isSpecialDept && $creatorRole === 'Staff Digital Marketing');

                    if ($canApprove && $eligibleStatus) {
                        // Approve when:
                        // - Status Validated (normal flow)
                        // - Status Verified for Human Greatness/Zi&Glo (special flow)
                        $po->update([
                            'status' => 'Approved',
                            'approved_by' => $user->id,
                            'approved_at' => now()
                        ]);

                        $this->logApprovalActivity($user, $po, 'approved');
                        $approvedCount++;
                    } else {
                        $errors[] = "Cannot approve PO #{$po->no_po} at status {$po->status}";
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => "Successfully approved {$approvedCount} Purchase Orders",
                    'approved_count' => $approvedCount,
                    'errors' => $errors
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk approve Purchase Orders'], 500);
            }
        }

        /**
         * Bulk reject Purchase Orders
         */
        public function bulkRejectPurchaseOrders(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $poIds = $request->input('po_ids', []);
            $reason = $request->input('reason', '');

            if (empty($poIds)) {
                return response()->json(['error' => 'No Purchase Orders selected'], 400);
            }

            // Fetch selected POs without restricting to a single status.
            // We'll enforce allowed statuses per-PO below so rejection works for
            // In Progress, Verified, and Validated as per workflow rules.
            $purchaseOrders = PurchaseOrder::whereIn('id', $poIds)->get();

            $rejectedCount = 0;
            $errors = [];

            try {
                DB::beginTransaction();

                foreach ($purchaseOrders as $po) {
                    // Only allow reject on active stages
                    if (!in_array($po->status, ['In Progress', 'Verified', 'Validated'], true)) {
                        $errors[] = "Cannot reject PO #{$po->no_po} at status {$po->status}";
                        continue;
                    }

                    // Use workflow service to validate reject permission
                    $canReject = $this->approvalWorkflowService->canUserApprove($user, $po, 'reject');

                    if ($canReject) {
                        $po->update([
                            'status' => 'Rejected',
                            'rejected_by' => $user->id,
                            'rejected_at' => now(),
                            'rejection_reason' => $reason
                        ]);

                        $this->logApprovalActivity($user, $po, 'rejected');
                        $rejectedCount++;
                    } else {
                        $errors[] = "Unauthorized to reject PO #{$po->no_po}";
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => "Successfully rejected {$rejectedCount} Purchase Orders",
                    'rejected_count' => $rejectedCount,
                    'errors' => $errors
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk reject Purchase Orders'], 500);
            }
        }

        /**
         * Get recent approval activities
         */
        public function getRecentActivities(): JsonResponse
        {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['activities' => []]);
                }

                $userRole = $user->role->name ?? '';

                // This would typically come from an approval_activities table
                // For now, we'll return recent PO activities
                $query = PurchaseOrder::with(['department'])
                    ->whereIn('status', ['Approved', 'Rejected'])
                    ->orderBy('updated_at', 'desc')
                    ->limit(10);

                // Note: DepartmentScope already filters by user's departments automatically

                $activities = $query->get()->map(function ($po) {
                    return [
                        'id' => $po->id,
                        'document_type' => 'Purchase Order',
                        'document_number' => $po->no_po,
                        'department' => $po->department->name ?? 'Unknown',
                        'status' => $po->status,
                        'created_at' => $po->updated_at
                    ];
                });

                return response()->json(['activities' => $activities]);
            } catch (\Exception $e) {
                return response()->json(['activities' => [], 'error' => $e->getMessage()]);
            }
        }

        /**
         * Get approval progress for a Purchase Order
         */
        public function getApprovalProgress($id): JsonResponse
        {
            try {
                $purchaseOrder = PurchaseOrder::with(['department', 'verifier', 'validator', 'approver'])
                    ->findOrFail($id);

                $progress = $this->approvalWorkflowService->getApprovalProgress($purchaseOrder);

                return response()->json([
                    'purchase_order' => $purchaseOrder,
                    'progress' => $progress
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to get approval progress'], 500);
            }
        }

        /**
         * Display Purchase Order logs within Approval module
         */
        public function purchaseOrderLog(PurchaseOrder $purchase_order, Request $request)
        {
            // Use DepartmentScope automatically (do NOT bypass) so 'All' access works and multi-department users are respected
            $po = $purchase_order;

            $logsQuery = PurchaseOrderLog::with(['user.department', 'user.role'])
                ->where('purchase_order_id', $po->id);

            // Filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $logsQuery->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                        ->orWhere('action', 'like', "%$search%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%$search%");
                        });
                });
            }
            if ($request->filled('action')) {
                $logsQuery->where('action', $request->input('action'));
            }
            if ($request->filled('role')) {
                $roleId = $request->input('role');
                $logsQuery->whereHas('user.role', function ($q) use ($roleId) {
                    $q->where('id', $roleId);
                });
            }
            if ($request->filled('department')) {
                $departmentId = $request->input('department');
                $logsQuery->whereHas('user.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            }
            if ($request->filled('date')) {
                $logsQuery->whereDate('created_at', $request->input('date'));
            }

            $perPage = (int) $request->input('per_page', 10);
            $logs = $logsQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

            $roleOptions = Role::select('id', 'name')->orderBy('name')->get();
            $departmentOptions = DepartmentService::getOptionsForFilter();
            $actionOptions = PurchaseOrderLog::where('purchase_order_id', $po->id)
                ->select('action')
                ->distinct()
                ->pluck('action');

            $filters = $request->only(['search', 'action', 'role', 'department', 'date', 'per_page']);

            if ($request->wantsJson()) {
                return response()->json([
                    'purchaseOrder' => $po,
                    'logs' => $logs,
                    'filters' => $filters,
                    'roleOptions' => $roleOptions,
                    'departmentOptions' => $departmentOptions,
                    'actionOptions' => $actionOptions,
                ]);
            }

            return inertia('approval/PurchaseOrderApprovalLog', [
                'purchaseOrder' => $po,
                'logs' => $logs,
                'filters' => $filters,
                'roleOptions' => $roleOptions,
                'departmentOptions' => $departmentOptions,
                'actionOptions' => $actionOptions,
            ]);
        }

        public function memoPembayaranLog(MemoPembayaran $id, Request $request)
        {
            $logs = $id->logs()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return Inertia::render('approval/MemoPembayaranApprovalLog', [
                'memoPembayaran' => $id,
                'logs' => $logs,
            ]);
        }

        public function poAnggaranLog(PoAnggaran $poAnggaran, Request $request)
        {
            $logsQuery = PoAnggaranLog::query()
                ->where('po_anggaran_id', $poAnggaran->id)
                ->with('poAnggaran', 'user');

            if ($search = $request->get('search')) {
                $logsQuery->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('meta', 'like', "%{$search}%");
                });
            }

            if ($action = $request->get('action')) {
                $logsQuery->where('action', $action);
            }

            $perPage = (int) $request->input('per_page', 10);
            $logs = $logsQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

            $filters = $request->only(['search', 'action', 'per_page']);

            return inertia('approval/PoAnggaranApprovalLog', [
                'poAnggaran' => $poAnggaran,
                'logs' => $logs,
                'filters' => $filters,
            ]);
        }

        public function realisasiLog(Realisasi $realisasi, Request $request)
        {
            $logsQuery = RealisasiLog::query()
                ->where('realisasi_id', $realisasi->id)
                ->with('realisasi', 'user');

            if ($search = $request->get('search')) {
                $logsQuery->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('meta', 'like', "%{$search}%");
                });
            }

            if ($action = $request->get('action')) {
                $logsQuery->where('action', $action);
            }

            $perPage = (int) $request->input('per_page', 10);
            $logs = $logsQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

            $filters = $request->only(['search', 'action', 'per_page']);

            return inertia('approval/RealisasiApprovalLog', [
                'realisasi' => $realisasi,
                'logs' => $logs,
                'filters' => $filters,
            ]);
        }

        /**
         * Check if user can access specific document type based on role
         */
        private function canAccessDocumentType(string $userRole, string $documentType): bool
        {
            switch ($userRole) {
                case 'Admin':
                    // Admin has access to all document types
                    return true;

                case 'Kepala Toko':
                    return in_array($documentType, ['purchase_order', 'payment_voucher', 'anggaran', 'bpb', 'realisasi', 'memo_pembayaran']);

                case 'Kabag':
                    return in_array($documentType, ['purchase_order', 'payment_voucher', 'anggaran', 'bpb', 'realisasi', 'memo_pembayaran']);

                case 'Staff Akunting & Finance':
                    return in_array($documentType, ['purchase_order', 'payment_voucher', 'anggaran', 'bpb', 'realisasi', 'memo_pembayaran']);

                case 'Kadiv':
                    return in_array($documentType, ['purchase_order', 'payment_voucher', 'anggaran', 'memo_pembayaran']);

                case 'Direksi':
                    return in_array($documentType, ['purchase_order', 'anggaran', 'payment_voucher', 'realisasi', 'memo_pembayaran']);

                default:
                    return false;
            }
        }


        /**
         * Check if user can approve/reject specific document
         */
        private function canApproveDocument(User $user, string $userRole, $document): bool
        {
            // Super admin and high-level roles can approve everything
            if (in_array($userRole, ['Admin', 'Kabag', 'Direksi'])) {
                return true;
            }

            // For other roles, check if they can access the document type
            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return false;
            }

            // Check if user belongs to the document's department
            if ($document->department_id) {
                return $user->departments->contains('id', $document->department_id);
            }

            // If document has no department, allow approval for users with access
            return true;
        }

        /**
         * Get Purchase Order counts for different statuses
         */
        private function getPurchaseOrderCounts(User $user, string $userRole): array
        {
            try {
                $query = PurchaseOrder::query();

                if (strtolower($userRole) === 'direksi') {
                    $inProgress = (clone $query)->where('status', 'In Progress')->count();
                    $verified   = (clone $query)->where('status', 'Verified')
                        ->whereHas('department', fn($d) => $d->whereIn('name', ['Zi&Glo', 'Human Greatness']))
                        ->count();
                    $validated  = (clone $query)->where('status', 'Validated')->count();
                } else {
                    $inProgress = (clone $query)->where('status', 'In Progress')->count();
                    $verified   = (clone $query)->where('status', 'Verified')->count();
                    $validated  = (clone $query)->where('status', 'Validated')->count();
                }

                $approved = (clone $query)->where('status', 'Approved')->count();
                $rejected = (clone $query)->where('status', 'Rejected')->count();

                return [
                    'in_progress' => $inProgress,
                    'verified'    => $verified,
                    'validated'   => $validated,
                    'approved'    => $approved,
                    'rejected'    => $rejected,
                    'pending'     => $inProgress + $verified + $validated,
                ];
            } catch (\Exception $e) {
                return [
                    'in_progress' => 0,
                    'verified'    => 0,
                    'validated'   => 0,
                    'approved'    => 0,
                    'rejected'    => 0,
                    'pending'     => 0,
                ];
            }
        }

        private function getWorkflowMapping(): array
        {
            return [
                'purchase_order' => [
                    'admin'       => null,
                    'staff toko'  => 'In Progress',
                    'kepala toko' => 'In Progress',
                    'kadiv'       => 'Verified',
                    'kabag'       => 'In Progress',
                    'direksi'     => 'Validated',
                ],
                'memo_pembayaran' => [
                    'admin'       => null,
                    'staff toko'  => 'In Progress',
                    'kepala toko' => 'In Progress',
                    'kadiv'       => 'Verified',
                    'kabag'       => 'Verified',
                    'direksi'     => null, // direksi tidak approve memo
                ],
                'payment_voucher' => [
                    'admin'       => null,
                    'kabag'       => 'In Progress',
                    // Kadiv harus melihat PV Pajak yang sudah di-verify (oleh Kabag)
                    'kadiv'       => 'Verified',
                    'direksi'     => ['Verified', 'Validated'],
                ],
                'bpb' => [
                    'admin'       => null,
                    'kepala toko' => 'In Progress',
                    'kabag'       => 'In Progress',
                ],
            ];
        }

        private function getStatusesForRole(string $docType, string $role): array|null
        {
            $mapping = $this->getWorkflowMapping();

            if (!isset($mapping[$docType])) {
                return [];
            }

            $roleMapping = array_change_key_case($mapping[$docType], CASE_LOWER);
            $roleLower   = strtolower($role);

            if (!array_key_exists($roleLower, $roleMapping)) {
                return [];
            }

            $statuses = $roleMapping[$roleLower];

            // null = bypass (admin)
            if ($statuses === null) {
                return null;
            }

            return is_array($statuses) ? $statuses : [$statuses];
        }

        /**
         * Log approval activity (placeholder for future implementation)
         */
        private function logApprovalActivity(User $user, $document, string $action): void
        {
            // Determine the document type and log accordingly
            if ($document instanceof \App\Models\PurchaseOrder) {
                PurchaseOrderLog::create([
                    'purchase_order_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => $action,
                    'description' => $this->getActionDescription($action, $document, $user),
                    'ip_address' => request()->ip(),
                ]);
            } elseif ($document instanceof \App\Models\MemoPembayaran) {
                MemoPembayaranLog::create([
                    'memo_pembayaran_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => $action,
                    'description' => $this->getActionDescription($action, $document, $user),
                    'old_values' => null,
                    'new_values' => null,
                ]);
            } elseif ($document instanceof \App\Models\PaymentVoucher) {
                PaymentVoucherLog::create([
                    'payment_voucher_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => $action,
                    'note' => $this->getActionDescription($action, $document, $user),
                ]);
            } elseif ($document instanceof \App\Models\Bpb) {
                BpbLog::create([
                    'bpb_id' => $document->id,
                    'user_id' => $user->id,
                    'action' => $action,
                    'description' => $this->getActionDescription($action, $document, $user),
                    'ip_address' => request()->ip(),
                ]);
            }
        }

        private function getActionDescription(string $action, $document, User $user): string
        {
            $userName = $user->name ?? 'Unknown User';

            if ($document instanceof \App\Models\PurchaseOrder) {
                $documentType = 'Purchase Order';
                $documentNumber = $document->no_po ?? 'N/A';
            } elseif ($document instanceof \App\Models\MemoPembayaran) {
                $documentType = 'Memo Pembayaran';
                $documentNumber = $document->no_mb ?? 'N/A';
            } elseif ($document instanceof \App\Models\PaymentVoucher) {
                $documentType = 'Payment Voucher';
                $documentNumber = $document->no_pv ?? 'N/A';
            } elseif ($document instanceof \App\Models\Bpb) {
                $documentType = 'BPB';
                $documentNumber = $document->no_bpb ?? 'N/A';
            } else {
                $documentType = 'Document';
                $documentNumber = 'N/A';
            }

            switch ($action) {
                case 'verified':
                    return "{$userName} verified {$documentType} {$documentNumber}";
                case 'validated':
                    return "{$userName} validated {$documentType} {$documentNumber}";
                case 'approved':
                    return "{$userName} approved {$documentType} {$documentNumber}";
                case 'rejected':
                    return "{$userName} rejected {$documentType} {$documentNumber}";
                default:
                    return "{$userName} performed {$action} on {$documentType} {$documentNumber}";
            }
        }

        /**
         * Display Purchase Order detail within Approval module
         */
        public function purchaseOrderDetail(PurchaseOrder $purchase_order)
        {
            $po = $purchase_order->load([
                'department',
                'perihal',
                // Load supplier without DepartmentScope so it's visible regardless of supplier.department_id
                'supplier' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'bank',
                'pph',
                'termin',
                'items',
                'creator.role',
                'updater',
                'verifier',
                'validator',
                'approver',
                'canceller',
                'rejecter',
                'bankSupplierAccount.bank',
            ]);
            return inertia('approval/PurchaseOrderApprovalDetail', [
                'purchaseOrder' => $po,
            ]);
        }

    // ==================== MEMO PEMBAYARAN APPROVAL METHODS ====================

        /**
         * Display Memo Pembayaran approval page
         */
        public function memoPembayarans()
        {
            $user = Auth::user();
            $departments = Department::all();

            return inertia('approval/MemoPembayaranApproval', [
                'departments' => $departments,
                'userRole' => $user->role->name ?? '',
            ]);
        }

        /**
         * Get Memo Pembayarans for approval
         */
        public function getMemoPembayarans(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query = MemoPembayaran::query()->with([
                'department',
                'purchaseOrders.perihal',
                'purchaseOrders.supplier',
                'purchaseOrder.perihal',
                'purchaseOrder.supplier',
                'supplier',
                'bank',
                'creator.role',
                'verifier',
                'validator',
                'approver',
                'rejecter'
            ])->whereNotIn('status', ['Draft', 'Canceled']);

            // 🔹 Filter status sesuai workflow
            $this->applyRoleStatusFilter($query, 'memo_pembayaran', $userRole);

            // 🔹 Apply filters tambahan
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
                $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
            }

            if ($request->filled('metode_pembayaran')) {
                $query->where('metode_pembayaran', $request->metode_pembayaran);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $searchColumns = $request->get('search_columns', '');

                if ($searchColumns) {
                    // Dynamic search based on selected columns
                    $columns = explode(',', $searchColumns);
                    $query->where(function ($q) use ($search, $columns) {
                        foreach ($columns as $column) {
                            switch ($column) {
                                case 'no_mb':
                                    $q->orWhere('no_mb', 'like', "%{$search}%");
                                    break;
                                case 'no_po':
                                    $q->orWhereHas('purchaseOrders', fn($subQ) => $subQ->where('no_po', 'like', "%{$search}%"));
                                    break;
                                case 'supplier':
                                    $q->orWhereHas('supplier', fn($subQ) => $subQ->where('nama_supplier', 'like', "%{$search}%"));
                                    break;
                                case 'tanggal':
                                    $q->orWhere('tanggal', 'like', "%{$search}%");
                                    break;
                                case 'status':
                                    $q->orWhere('status', 'like', "%{$search}%");
                                    break;
                                case 'perihal':
                                    $q->orWhere('keterangan', 'like', "%{$search}%");
                                    break;
                                case 'department':
                                    $q->orWhereHas('department', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                                    break;
                                case 'metode_pembayaran':
                                    $q->orWhere('metode_pembayaran', 'like', "%{$search}%");
                                    break;
                                case 'grand_total':
                                case 'total':
                                    $q->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%{$search}%"]);
                                    break;
                                case 'nama_rekening':
                                    $q->orWhere('nama_rekening', 'like', "%{$search}%");
                                    break;
                                case 'no_rekening':
                                    $q->orWhere('no_rekening', 'like', "%{$search}%");
                                    break;
                                case 'no_kartu_kredit':
                                    $q->orWhere('no_kartu_kredit', 'like', "%{$search}%");
                                    break;
                                case 'no_giro':
                                    $q->orWhere('no_giro', 'like', "%{$search}%");
                                    break;
                                case 'tanggal_giro':
                                    $q->orWhere('tanggal_giro', 'like', "%{$search}%");
                                    break;
                                case 'tanggal_cair':
                                    $q->orWhere('tanggal_cair', 'like', "%{$search}%");
                                    break;
                                case 'keterangan':
                                    $q->orWhere('keterangan', 'like', "%{$search}%");
                                    break;
                                case 'diskon':
                                    $q->orWhereRaw('CAST(diskon AS CHAR) LIKE ?', ["%{$search}%"]);
                                    break;
                                case 'ppn':
                                    $q->orWhere('ppn', 'like', "%{$search}%");
                                    break;
                                case 'ppn_nominal':
                                    $q->orWhereRaw('CAST(ppn_nominal AS CHAR) LIKE ?', ["%{$search}%"]);
                                    break;
                                case 'pph_nominal':
                                    $q->orWhereRaw('CAST(pph_nominal AS CHAR) LIKE ?', ["%{$search}%"]);
                                    break;
                                case 'created_by':
                                    $q->orWhereHas('creator', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                                    break;
                                case 'created_at':
                                    $q->orWhere('created_at', 'like', "%{$search}%");
                                    break;
                            }
                        }
                    });
                } else {
                    // Default search across all common fields
                    $query->where(function ($q) use ($search) {
                        $q->where('no_mb', 'like', "%{$search}%")
                            ->orWhere('keterangan', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('tanggal', 'like', "%{$search}%")
                            ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%{$search}%"])
                            ->orWhereHas('department', fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('purchaseOrders', fn($q) => $q->where('no_po', 'like', "%{$search}%"))
                            ->orWhereHas('purchaseOrders.supplier', fn($q) => $q->where('nama_supplier', 'like', "%{$search}%"))
                            ->orWhereHas('supplier', fn($q) => $q->where('nama_supplier', 'like', "%{$search}%"));
                    });
                }
            }

            $perPage = (int) $request->get('per_page', 15);
            $currentPage = (int) $request->get('page', 1);

            try {
                $counts = [
                    'pending'  => MemoPembayaran::whereIn('status', ['In Progress', 'Verified', 'Validated'])->count(),
                    'approved' => MemoPembayaran::where('status', 'Approved')->count(),
                    'rejected' => MemoPembayaran::where('status', 'Rejected')->count(),
                ];

                // 1) Kumpulkan ID yang actionable
                $actionableIds = [];
                (clone $query)
                    ->with(['department', 'creator.role'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->chunk(500, function ($items) use ($user, &$actionableIds) {
                        foreach ($items as $memo) {
                            $action = $this->inferActionForMemo($memo->status, $memo);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memo, $action)) {
                                $actionableIds[] = $memo->id;
                            }
                        }
                    });

                $actionableTotal = count($actionableIds);
                $lastPage = (int) max(1, (int) ceil($actionableTotal / max(1, $perPage)));
                $currentPage = min(max(1, $currentPage), $lastPage);

                // 2) Ambil ID untuk halaman saat ini
                $offset = ($currentPage - 1) * $perPage;
                $pageIds = array_slice($actionableIds, $offset, $perPage);

                $items = collect();
                if (!empty($pageIds)) {
                    $items = MemoPembayaran::with([
                        'department',
                        'purchaseOrders.perihal',
                        'purchaseOrders.supplier',
                        'purchaseOrder.perihal',
                        'purchaseOrder.supplier',
                        'supplier',
                        'bank',
                        'creator.role',
                        'verifier',
                        'validator',
                        'approver',
                        'rejecter'
                    ])
                        ->whereIn('id', $pageIds)
                        ->get()
                        ->sortBy(function ($m) use ($pageIds) {
                            return array_search($m->id, $pageIds);
                        })
                        ->values();
                }

                $from = $actionableTotal > 0 ? ($offset + 1) : 0;
                $to = $offset + $items->count();

                // Build Laravel-like pagination links (Previous, pages, Next)
                $prevUrl = $currentPage > 1
                    ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage])
                    : null;
                $nextUrl = $currentPage < $lastPage
                    ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage])
                    : null;
                $links = [];
                $links[] = ['url' => $prevUrl, 'label' => '&laquo; Previous', 'active' => false];
                for ($i = 1; $i <= $lastPage; $i++) {
                    $links[] = [
                        'url' => request()->fullUrlWithQuery(['page' => $i, 'per_page' => $perPage]),
                        'label' => (string) $i,
                        'active' => $i === $currentPage,
                    ];
                }
                $links[] = ['url' => $nextUrl, 'label' => 'Next &raquo;', 'active' => false];

                return response()->json([
                    'data' => $items,
                    'pagination' => [
                        'current_page' => $currentPage,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $actionableTotal,
                        'from' => $from,
                        'to' => $to,
                        'links' => $links,
                        'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                        'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                    ],
                    'counts' => $counts,
                ]);
            } catch (\Exception $e) {
                Log::error('Error getMemoPembayarans: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all(),
                ]);
                return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
            }
        }

        /**
         * Approve a Memo Pembayaran
         */
        public function approveMemoPembayaran(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $memoPembayaran = MemoPembayaran::findOrFail($id);

            // Check if user can approve this memo using workflow service
            if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'approve')) {
                return response()->json(['error' => 'Unauthorized to approve this memo'], 403);
            }

            try {
                DB::beginTransaction();

                $memoPembayaran->update([
                    'status' => 'Approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => $request->input('notes', '')
                ]);

                // Log approval activity
                $this->logApprovalActivity($user, $memoPembayaran, 'approved');

                DB::commit();

                return response()->json([
                    'message' => 'Memo Pembayaran approved successfully',
                    'memo_pembayaran' => $memoPembayaran->fresh(['approver'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to approve Memo Pembayaran'], 500);
            }
        }

        /**
         * Verify a Memo Pembayaran
         */
        public function verifyMemoPembayaran(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $memoPembayaran = MemoPembayaran::findOrFail($id);

            // Check if user can verify this memo using workflow service
            if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'verify')) {
                return response()->json(['error' => 'Unauthorized to verify this memo'], 403);
            }

            try {
                DB::beginTransaction();

                $memoPembayaran->update([
                    'status' => 'Verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'approval_notes' => $request->input('notes', '')
                ]);

                // Log verification activity
                $this->logApprovalActivity($user, $memoPembayaran, 'verified');

                DB::commit();

                return response()->json([
                    'message' => 'Memo Pembayaran verified successfully',
                    'memo_pembayaran' => $memoPembayaran->fresh(['verifier'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to verify Memo Pembayaran'], 500);
            }
        }

        /**
         * Validate a Memo Pembayaran
         */
        public function validateMemoPembayaran(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $memoPembayaran = MemoPembayaran::findOrFail($id);

            // Check if user can validate this memo using workflow service
            if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'validate')) {
                return response()->json(['error' => 'Unauthorized to validate this memo'], 403);
            }

            try {
                DB::beginTransaction();

                $memoPembayaran->update([
                    'status' => 'Validated',
                    'validated_by' => $user->id,
                    'validated_at' => now(),
                    'approval_notes' => $request->input('notes', '')
                ]);

                // Log validation activity
                $this->logApprovalActivity($user, $memoPembayaran, 'validated');

                DB::commit();

                return response()->json([
                    'message' => 'Memo Pembayaran validated successfully',
                    'memo_pembayaran' => $memoPembayaran->fresh(['validator'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to validate Memo Pembayaran'], 500);
            }
        }

        /**
         * Reject a Memo Pembayaran
         */
        public function rejectMemoPembayaran(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $memoPembayaran = MemoPembayaran::findOrFail($id);

            // Check if user can reject this memo using workflow service
            if (!$this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memoPembayaran, 'reject')) {
                return response()->json(['error' => 'Unauthorized to reject this memo'], 403);
            }

            try {
                DB::beginTransaction();

                $memoPembayaran->update([
                    'status' => 'Rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $request->input('reason', '')
                ]);

                // Log rejection activity
                $this->logApprovalActivity($user, $memoPembayaran, 'rejected');

                DB::commit();

                return response()->json([
                    'message' => 'Memo Pembayaran rejected successfully',
                    'memo_pembayaran' => $memoPembayaran->fresh(['rejecter'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to reject Memo Pembayaran'], 500);
            }
        }

        /**
         * Get approval progress for a Memo Pembayaran (API)
         */
        public function getMemoPembayaranProgress($id): JsonResponse
        {
            $memoPembayaran = MemoPembayaran::findOrFail($id);
            $progress = $this->approvalWorkflowService->getApprovalProgressForMemoPembayaran($memoPembayaran);

            return response()->json([
                'progress' => $progress,
                'current_status' => $memoPembayaran->status,
            ]);
        }

        /**
         * Bulk approve Memo Pembayarans
         */
        public function bulkApproveMemoPembayarans(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $memoIds = $request->input('memo_ids', []);
            if (empty($memoIds)) {
                return response()->json(['error' => 'No Memo Pembayaran selected'], 400);
            }

            $memos = MemoPembayaran::whereIn('id', $memoIds)->get();

            $approvedCount = 0;
            $errors = [];

            try {
                DB::beginTransaction();

                foreach ($memos as $memo) {
                    $canApprove = $this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memo, 'approve');

                    if ($canApprove && in_array($memo->status, ['Validated', 'Verified'])) {
                        // Approve when:
                        // - Status Validated (normal flow)
                        // - Status Verified for flows where Direksi can directly approve after Verified
                        $memo->update([
                            'status' => 'Approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);

                        $this->logApprovalActivity($user, $memo, 'approved');
                        $approvedCount++;
                    } else {
                        $errors[] = "Cannot approve Memo #{$memo->no_mb} at status {$memo->status}";
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => "Successfully approved {$approvedCount} Memo Pembayaran",
                    'approved_count' => $approvedCount,
                    'errors' => $errors,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk approve Memo Pembayaran'], 500);
            }
        }

        /**
         * Bulk reject Memo Pembayarans
         */
        public function bulkRejectMemoPembayarans(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $memoIds = $request->input('memo_ids', []);
            $reason = $request->input('reason', '');

            if (empty($memoIds)) {
                return response()->json(['error' => 'No Memo Pembayaran selected'], 400);
            }

            $memos = MemoPembayaran::whereIn('id', $memoIds)->get();

            $rejectedCount = 0;
            $errors = [];

            try {
                DB::beginTransaction();

                foreach ($memos as $memo) {
                    $canReject = $this->approvalWorkflowService->canUserApproveMemoPembayaran($user, $memo, 'reject');

                    if ($canReject && in_array($memo->status, ['In Progress', 'Verified', 'Validated'])) {
                        $memo->update([
                            'status' => 'Rejected',
                            'rejected_by' => $user->id,
                            'rejected_at' => now(),
                            'rejection_reason' => $reason,
                        ]);

                        $this->logApprovalActivity($user, $memo, 'rejected');
                        $rejectedCount++;
                    } else {
                        $errors[] = "Cannot reject Memo #{$memo->no_mb} at status {$memo->status}";
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => "Successfully rejected {$rejectedCount} Memo Pembayaran",
                    'rejected_count' => $rejectedCount,
                    'errors' => $errors,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk reject Memo Pembayaran'], 500);
            }
        }

        /**
         * Display Memo Pembayaran detail within Approval module
         */
        public function memoPembayaranDetail(MemoPembayaran $memoPembayaran)
        {
            $memo = $memoPembayaran->load([
                'department',
                // Load many-to-many relationship (if used)
                'purchaseOrders.perihal',
                'purchaseOrders.supplier' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'purchaseOrders.department',
                'purchaseOrders.items',
                // Include bank account info for many-to-many POs
                'purchaseOrders.bankSupplierAccount.bank',
                // Load single relationship (primary method used)
                'purchaseOrder' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'purchaseOrder.pph',
                'purchaseOrder.termin',
                'purchaseOrder.perihal',
                'purchaseOrder.items',
                'purchaseOrder.supplier' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'purchaseOrder.department',
                // Include bank account info for primary PO
                'purchaseOrder.bankSupplierAccount.bank',
                'supplier' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'bank',
                // Memo-level bank supplier account
                'bankSupplierAccount.bank',
                'creator.role',
                'verifier',
                'validator',
                'approver',
                'rejecter'
            ]);


            return inertia('approval/MemoPembayaranApprovalDetail', [
                'memoPembayaran' => $memo,
            ]);
        }

        private function applyRoleStatusFilter($query, string $documentType, string $userRole): void
        {
            $statuses = $this->getStatusesForRole($documentType, $userRole);

            if ($statuses === []) {
                Log::info("Role {$userRole} → no statuses (denied)");
                $query->whereRaw('1 = 0');
                return;
            }

            if ($statuses === null) {
                Log::info("Role {$userRole} → bypass (admin)");
                return;
            }

            if (strtolower($userRole) === 'direksi' && $documentType === 'purchase_order') {
                Log::info("Role Direksi filter applied", ['statuses' => $statuses]);

                $query->where(function ($q) use ($statuses) {
                    $q->whereIn('status', $statuses)
                        ->orWhere(function ($sub) {
                            $sub->where('status', 'Verified')
                                ->whereHas('department', fn($d) => $d->whereIn('name', ['Zi&Glo', 'Human Greatness']));
                        });
                });
                return;
            }

            if (strtolower($userRole) === 'kadiv' && $documentType === 'purchase_order') {
                Log::info("Role Kadiv filter applied", ['statuses' => $statuses]);

                $query->where(function ($q) use ($statuses) {
                    $q->whereIn('status', $statuses) // biasanya 'Verified'
                        ->orWhere(function ($sub) {
                            $sub->where('status', 'In Progress')
                                ->whereHas('creator.role', fn($r) => $r->where('name', 'Staff Digital Marketing'));
                        });
                });
                return;
            }

            Log::info("Role {$userRole} → filter statuses", ['statuses' => $statuses]);
            $query->whereIn('status', $statuses);
        }

    // ==================== PAYMENT VOUCHER APPROVAL METHODS ====================

        /**
         * Display Payment Voucher approval page
         */
        public function paymentVouchers()
        {
            $user = Auth::user();
            $departments = Department::all();

            return inertia('approval/PaymentVoucherApproval', [
                'departments' => $departments,
                'userRole' => $user->role->name ?? '',
            ]);
        }

        /**
         * Get Payment Vouchers for approval
         */
        public function getPaymentVouchers(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query = PaymentVoucher::query()->with([
                'department',
                'supplier',
                'perihal',
                'creator.role',
                'verifier',
                'approver',
                'rejecter',
                // Nested relations for columns
                'purchaseOrder' => function ($q) {
                    $q->with(['department', 'perihal', 'supplier', 'pph', 'termin', 'creditCard.bank', 'bankSupplierAccount.bank']);
                },
                'memoPembayaran' => function ($q) {
                    $q->with(['department', 'supplier', 'bankSupplierAccount.bank']);
                },
            ])->whereNotIn('status', ['Draft', 'Canceled']);

            // Filter status sesuai workflow
            $this->applyRoleStatusFilter($query, 'payment_voucher', $userRole);

            // Apply filters tambahan
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by tipe_pv (e.g., Reguler, Anggaran, Lainnya, Pajak, Manual)
            if ($request->filled('tipe_pv')) {
                $query->where('tipe_pv', $request->get('tipe_pv'));
            }

            if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
                $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
            }

            if ($request->filled('metode_bayar')) {
                $method = $request->metode_bayar;
                // UI may send 'Kredit' as alias for 'Kartu Kredit'
                if ($method === 'Kredit') {
                    $method = 'Kartu Kredit';
                }
                $query->where(function ($q) use ($method) {
                    $q->where('metode_bayar', $method)
                        ->orWhereHas('purchaseOrder', fn($sub) => $sub->where('metode_pembayaran', $method))
                        ->orWhereHas('memoPembayaran', fn($sub) => $sub->where('metode_pembayaran', $method));
                });
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $searchColumns = $request->get('search_columns', '');

                if ($searchColumns) {
                    // Dynamic search based on selected columns
                    $columns = explode(',', $searchColumns);
                    $query->where(function ($q) use ($search, $columns) {
                        foreach ($columns as $column) {
                            switch ($column) {
                                case 'no_pv':
                                    $q->orWhere('no_pv', 'like', "%{$search}%");
                                    break;
                                case 'no_po':
                                    $q->orWhereHas('purchaseOrder', fn($subQ) => $subQ->where('no_po', 'like', "%{$search}%"));
                                    break;
                                case 'supplier':
                                    $q->orWhereHas('supplier', fn($subQ) => $subQ->where('nama_supplier', 'like', "%{$search}%"));
                                    break;
                                case 'tanggal':
                                    $q->orWhere('tanggal', 'like', "%{$search}%");
                                    break;
                                case 'status':
                                    $q->orWhere('status', 'like', "%{$search}%");
                                    break;
                                case 'perihal':
                                    $q->orWhereHas('perihal', fn($subQ) => $subQ->where('nama', 'like', "%{$search}%"));
                                    break;
                                case 'department':
                                    $q->orWhereHas('department', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                                    break;
                                case 'metode_bayar':
                                    $q->orWhere('metode_bayar', 'like', "%{$search}%");
                                    break;
                                case 'metode_pembayaran':
                                    // alias support: search pv.metode_bayar and related PO/Memo metode_pembayaran
                                    $q->orWhere('metode_bayar', 'like', "%{$search}%")
                                        ->orWhereHas('purchaseOrder', fn($subQ) => $subQ->where('metode_pembayaran', 'like', "%{$search}%"))
                                        ->orWhereHas('memoPembayaran', fn($subQ) => $subQ->where('metode_pembayaran', 'like', "%{$search}%"));
                                    break;
                                case 'nominal':
                                    $q->orWhereRaw('CAST(nominal AS CHAR) LIKE ?', ["%{$search}%"]);
                                    break;
                                case 'keterangan':
                                    $q->orWhere('keterangan', 'like', "%{$search}%");
                                    break;
                                case 'created_by':
                                    $q->orWhereHas('creator', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                                    break;
                                case 'created_at':
                                    $q->orWhere('created_at', 'like', "%{$search}%");
                                    break;
                            }
                        }
                    });
                } else {
                    // Default search across all common fields
                    $query->where(function ($q) use ($search) {
                        $q->where('no_pv', 'like', "%{$search}%")
                            ->orWhere('keterangan', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('tanggal', 'like', "%{$search}%")
                            ->orWhereRaw('CAST(nominal AS CHAR) LIKE ?', ["%{$search}%"])
                            ->orWhereHas('department', fn($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('purchaseOrder', fn($q) => $q->where('no_po', 'like', "%{$search}%"))
                            ->orWhereHas('supplier', fn($q) => $q->where('nama_supplier', 'like', "%{$search}%"));
                    });
                }
            }

            $perPage = (int) $request->get('per_page', 15);
            $currentPage = (int) $request->get('page', 1);

            try {
                $counts = [
                    'pending'  => PaymentVoucher::whereIn('status', ['In Progress', 'Verified'])->count(),
                    'approved' => PaymentVoucher::where('status', 'Approved')->count(),
                    'rejected' => PaymentVoucher::where('status', 'Rejected')->count(),
                ];

                // 1) Kumpulkan ID yang actionable (berdasarkan workflow permission)
                $actionableIds = [];
                (clone $query)
                    ->with(['department', 'creator.role'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->chunk(500, function ($items) use ($user, &$actionableIds) {
                        foreach ($items as $pv) {
                            $action = $this->inferActionForPv($pv->status, $pv);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $pv, $action)) {
                                $actionableIds[] = $pv->id;
                            }
                        }
                    });

                $actionableTotal = count($actionableIds);
                $lastPage = (int) max(1, (int) ceil($actionableTotal / max(1, $perPage)));
                $currentPage = min(max(1, $currentPage), $lastPage);

                // 2) Ambil ID untuk halaman saat ini
                $offset = ($currentPage - 1) * $perPage;
                $pageIds = array_slice($actionableIds, $offset, $perPage);

                $items = collect();
                if (!empty($pageIds)) {
                    // 3) Ambil data PV untuk ID tersebut dan normalisasi field seperti sebelumnya
                    $items = PaymentVoucher::with([
                        'department',
                        'supplier',
                        'perihal',
                        'creator.role',
                        'verifier',
                        'approver',
                        'rejecter',
                        'purchaseOrder' => function ($q) {
                            $q->with(['department', 'perihal', 'supplier', 'pph', 'termin', 'creditCard.bank', 'bankSupplierAccount.bank']);
                        },
                        'memoPembayaran' => function ($q) {
                            $q->with(['department', 'supplier', 'bankSupplierAccount.bank']);
                        },
                    ])
                        ->whereIn('id', $pageIds)
                        ->get()
                        ->sortBy(function ($m) use ($pageIds) {
                            return array_search($m->id, $pageIds);
                        })
                        ->map(function ($pv) {
                            $metodePembayaran = $pv->metode_bayar
                                ?? $pv->purchaseOrder?->metode_pembayaran
                                ?? $pv->memoPembayaran?->metode_pembayaran;

                            $supplierName = $pv->supplier?->nama_supplier
                                ?? $pv->purchaseOrder?->supplier?->nama_supplier
                                ?? $pv->memoPembayaran?->supplier?->nama_supplier;

                            $departmentName = $pv->department?->name
                                ?? $pv->purchaseOrder?->department?->name
                                ?? $pv->memoPembayaran?->department?->name;

                            $perihalName = $pv->perihal?->nama
                                ?? $pv->purchaseOrder?->perihal?->nama;

                            $namaRekening = $pv->nama_rekening
                                ?? $pv->purchaseOrder?->bankSupplierAccount?->nama_rekening
                                ?? $pv->memoPembayaran?->bankSupplierAccount?->nama_rekening
                                ?? $pv->manual_nama_pemilik_rekening;

                            $noRekening = $pv->no_rekening
                                ?? $pv->purchaseOrder?->bankSupplierAccount?->no_rekening
                                ?? $pv->memoPembayaran?->bankSupplierAccount?->no_rekening
                                ?? $pv->manual_no_rekening;

                            $noKartuKredit = $pv->no_kartu_kredit
                                ?? $pv->purchaseOrder?->creditCard?->no_kartu_kredit;

                            $noGiro = $pv->no_giro ?? $pv->purchaseOrder?->no_giro;
                            $tanggalGiro = $pv->tanggal_giro ?? $pv->purchaseOrder?->tanggal_giro;
                            $tanggalCair = $pv->tanggal_cair ?? $pv->purchaseOrder?->tanggal_cair;

                            $total = $pv->total ?? $pv->purchaseOrder?->total;
                            $diskon = $pv->diskon ?? $pv->purchaseOrder?->diskon;
                            $ppnFlag = $pv->ppn ?? $pv->purchaseOrder?->ppn;
                            $ppnNominal = $pv->ppn_nominal ?? $pv->purchaseOrder?->ppn_nominal;
                            $pphNominal = $pv->pph_nominal ?? $pv->purchaseOrder?->pph_nominal;
                            $grandTotal = $pv->grand_total ?? $pv->purchaseOrder?->grand_total;

                            return [
                                'id' => $pv->id,
                                'no_pv' => $pv->no_pv,
                                'tipe_pv' => $pv->tipe_pv,
                                // expose nominal from PV for Pajak/Manual display in frontend
                                'nominal' => $pv->nominal,
                                // expose memo cicilan for tipe Lainnya display in tables
                                'memo_cicilan' => $pv->memoPembayaran?->cicilan,
                                'no_po' => $pv->purchaseOrder?->no_po,
                                // unified reference number for table display
                                'reference_number' => (strtolower($pv->tipe_pv ?? '') === 'lainnya')
                                    ? ($pv->memoPembayaran?->no_mb)
                                    : ($pv->purchaseOrder?->no_po),
                                'tanggal' => $pv->tanggal,
                                'status' => $pv->status,
                                'supplier' => ['nama_supplier' => $supplierName],
                                'supplier_name' => $supplierName,
                                'department' => ['name' => $departmentName],
                                'department_name' => $departmentName,
                                'perihal' => ['nama' => $perihalName],
                                'metode_pembayaran' => $metodePembayaran,
                                'nama_rekening' => $namaRekening,
                                'no_rekening' => $noRekening,
                                'no_kartu_kredit' => $noKartuKredit,
                                'no_giro' => $noGiro,
                                'tanggal_giro' => $tanggalGiro,
                                'tanggal_cair' => $tanggalCair,
                                'keterangan' => $pv->keterangan ?? $pv->note,
                                'total' => $total,
                                'diskon' => $diskon,
                                'ppn' => $ppnFlag,
                                'ppn_nominal' => $ppnNominal,
                                'pph_nominal' => $pphNominal,
                                'grand_total' => $grandTotal,
                                'creator' => $pv->creator ? ['name' => $pv->creator->name] : null,
                                'created_at' => optional($pv->created_at)->toDateString(),
                                'purchase_order' => $pv->purchaseOrder,
                            ];
                        });
                }

                $from = $actionableTotal > 0 ? ($offset + 1) : 0;
                $to = $offset + $items->count();

                // Build Laravel-like pagination links (Previous, pages, Next)
                $prevUrl = $currentPage > 1
                    ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage])
                    : null;
                $nextUrl = $currentPage < $lastPage
                    ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage])
                    : null;
                $links = [];
                $links[] = ['url' => $prevUrl, 'label' => '&laquo; Previous', 'active' => false];
                for ($i = 1; $i <= $lastPage; $i++) {
                    $links[] = [
                        'url' => request()->fullUrlWithQuery(['page' => $i, 'per_page' => $perPage]),
                        'label' => (string) $i,
                        'active' => $i === $currentPage,
                    ];
                }
                $links[] = ['url' => $nextUrl, 'label' => 'Next &raquo;', 'active' => false];

                return response()->json([
                    'data' => $items->values(),
                    'pagination' => [
                        'current_page' => $currentPage,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $actionableTotal,
                        'from' => $from,
                        'to' => $to,
                        'links' => $links,
                        'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                        'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                    ],
                    'counts' => $counts,
                ]);
            } catch (\Exception $e) {
                Log::error('Error getPaymentVouchers: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all(),
                ]);
                return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
            }
        }

        /**
         * Get Payment Voucher count for dashboard
         */
        public function getPaymentVoucherCount(): JsonResponse
        {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['count' => 0]);
                }

                $userRole = $user->role->name ?? '';

                if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                    return response()->json(['count' => 0]);
                }

                // Base status filter by role
                $statuses = $this->getStatusesForRole('payment_voucher', $userRole);
                if ($statuses === []) {
                    return response()->json(['count' => 0]);
                }

                $query = PaymentVoucher::query()
                    ->whereNotIn('status', ['Draft', 'Canceled']);

                if (is_array($statuses)) {
                    $query->whereIn('status', $statuses);
                }

                // Actionable-only count using workflow permission checks
                $actionable = 0;
                $query->with(['department', 'creator.role'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->chunk(500, function ($items) use ($user, &$actionable) {
                        foreach ($items as $pv) {
                            $action = $this->inferActionForPv($pv->status, $pv);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $pv, $action)) {
                                $actionable++;
                            }
                        }
                    });

                return response()->json(['count' => $actionable]);
            } catch (\Exception $e) {
                return response()->json(['count' => 0, 'error' => $e->getMessage()]);
            }
        }

        /**
         * Verify a Payment Voucher
         */
        public function verifyPaymentVoucher(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $paymentVoucher = PaymentVoucher::findOrFail($id);

            // Check if user can verify this PV using workflow service
            if (!$this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $paymentVoucher, 'verify')) {
                return response()->json(['error' => 'Unauthorized to verify this payment voucher'], 403);
            }

            try {
                DB::beginTransaction();

                $paymentVoucher->update([
                    'status' => 'Verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'verification_notes' => $request->input('notes', '')
                ]);

                // Log verification activity
                $this->logApprovalActivity($user, $paymentVoucher, 'verified');

                DB::commit();

                return response()->json([
                    'message' => 'Payment Voucher verified successfully',
                    'payment_voucher' => $paymentVoucher->fresh(['verifier'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error verifyPaymentVoucher', [
                    'payment_voucher_id' => $id,
                    'user_id' => $user->id ?? null,
                    'message' => $e->getMessage(),
                ]);
                return response()->json(['error' => 'Failed to verify Payment Voucher'], 500);
            }
        }

        /**
         * Approve a Payment Voucher
         */
        public function approvePaymentVoucher(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $paymentVoucher = PaymentVoucher::findOrFail($id);

            // Check if user can approve this PV using workflow service
            if (!$this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $paymentVoucher, 'approve')) {
                return response()->json(['error' => 'Unauthorized to approve this payment voucher'], 403);
            }

            try {
                DB::beginTransaction();

                $paymentVoucher->update([
                    'status' => 'Approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => $request->input('notes', '')
                ]);

                // Log approval activity
                $this->logApprovalActivity($user, $paymentVoucher, 'approved');

                DB::commit();

                return response()->json([
                    'message' => 'Payment Voucher approved successfully',
                    'payment_voucher' => $paymentVoucher->fresh(['approver'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to approve Payment Voucher'], 500);
            }
        }

        /**
         * Reject a Payment Voucher
         */
        public function rejectPaymentVoucher(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $paymentVoucher = PaymentVoucher::findOrFail($id);

            // Check if user can reject this PV using workflow service
            if (!$this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $paymentVoucher, 'reject')) {
                return response()->json(['error' => 'Unauthorized to reject this payment voucher'], 403);
            }

            try {
                DB::beginTransaction();

                $paymentVoucher->update([
                    'status' => 'Rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $request->input('reason', '')
                ]);

                // Log rejection activity
                $this->logApprovalActivity($user, $paymentVoucher, 'rejected');

                DB::commit();

                return response()->json([
                    'message' => 'Payment Voucher rejected successfully',
                    'payment_voucher' => $paymentVoucher->fresh(['rejecter'])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to reject Payment Voucher'], 500);
            }
        }

        /**
         * Bulk approve Payment Vouchers
         */
        public function bulkApprovePaymentVouchers(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $pvIds = $request->input('pv_ids', []);
            if (empty($pvIds)) {
                return response()->json(['error' => 'No Payment Vouchers selected'], 400);
            }

            $paymentVouchers = PaymentVoucher::whereIn('id', $pvIds)->get();

            $approvedCount = 0;
            $errors = [];

            try {
                DB::beginTransaction();

                foreach ($paymentVouchers as $pv) {
                    $canApprove = $this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $pv, 'approve');

                    $isApprovedState = false;
                    if ($pv->status === 'Verified') {
                        // Default flow: Direksi approve after Verified
                        $isApprovedState = true;
                    } elseif ($pv->status === 'Validated' && $pv->tipe_pv === 'Pajak') {
                        // Pajak flow: Direksi approve after Validated
                        $isApprovedState = true;
                    }

                    if ($canApprove && $isApprovedState) {
                        $pv->update([
                            'status' => 'Approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);

                        $this->logApprovalActivity($user, $pv, 'approved');
                        $approvedCount++;
                    } else {
                        $errors[] = "Cannot approve PV #{$pv->no_pv} at status {$pv->status}";
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => "Successfully approved {$approvedCount} Payment Vouchers",
                    'approved_count' => $approvedCount,
                    'errors' => $errors,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk approve Payment Vouchers'], 500);
            }
        }

        /**
         * Bulk reject Payment Vouchers
         */
        public function bulkRejectPaymentVouchers(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'payment_voucher')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $pvIds = $request->input('pv_ids', []);
            $reason = $request->input('reason', '');

            if (empty($pvIds)) {
                return response()->json(['error' => 'No Payment Vouchers selected'], 400);
            }

            $paymentVouchers = PaymentVoucher::whereIn('id', $pvIds)->get();

            $rejectedCount = 0;
            $errors = [];

            try {
                DB::beginTransaction();

                foreach ($paymentVouchers as $pv) {
                    $canReject = $this->approvalWorkflowService->canUserApprovePaymentVoucher($user, $pv, 'reject');

                    $isRejectableState = in_array($pv->status, ['In Progress', 'Verified'], true);
                    // Allow Validated stage for Pajak when role is Direksi/Admin (aligned with workflow service)
                    if ($pv->tipe_pv === 'Pajak' && $pv->status === 'Validated' && in_array($userRole, ['Direksi', 'Admin'], true)) {
                        $isRejectableState = true;
                    }

                    if ($canReject && $isRejectableState) {
                        $pv->update([
                            'status' => 'Rejected',
                            'rejected_by' => $user->id,
                            'rejected_at' => now(),
                            'rejection_reason' => $reason,
                        ]);

                        $this->logApprovalActivity($user, $pv, 'rejected');
                        $rejectedCount++;
                    } else {
                        $errors[] = "Cannot reject PV #{$pv->no_pv} at status {$pv->status}";
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => "Successfully rejected {$rejectedCount} Payment Vouchers",
                    'rejected_count' => $rejectedCount,
                    'errors' => $errors,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk reject Payment Vouchers'], 500);
            }
        }

        /**
         * Get approval progress for a Payment Voucher (API)
         */
        public function getPaymentVoucherProgress($id): JsonResponse
        {
            $paymentVoucher = PaymentVoucher::findOrFail($id);
            $progress = $this->approvalWorkflowService->getApprovalProgressForPaymentVoucher($paymentVoucher);

            return response()->json([
                'progress' => $progress,
                'current_status' => $paymentVoucher->status,
            ]);
        }

        /**
         * Display Payment Voucher detail within Approval module
         */
        public function paymentVoucherDetail(PaymentVoucher $paymentVoucher)
        {
            $pv = $paymentVoucher->load([
                'department',
                'supplier' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'perihal',
                // Ensure PV-level bank account is available for display
                'bankSupplierAccount.bank',
                // Ensure PV's own credit card relation is available
                'creditCard.bank',
                'purchaseOrder' => function ($q) {
                    $q->withoutGlobalScopes()->with([
                        'department',
                        'perihal',
                        'supplier',
                        'pph',
                        'termin',
                        'creditCard.bank',
                        'bankSupplierAccount.bank'
                    ]);
                },
                'memoPembayaran' => function ($q) {
                    $q->withoutGlobalScopes()->with([
                        'department',
                        'supplier',
                        'bankSupplierAccount.bank'
                    ]);
                },
                'creator.role',
                'verifier',
                'approver',
                'rejecter',
                'canceller',
                'documents'
            ]);

            return inertia('approval/PaymentVoucherApprovalDetail', [
                'paymentVoucher' => $pv,
            ]);
        }

        /**
         * Display Payment Voucher logs within Approval module
         */
        public function paymentVoucherLog(PaymentVoucher $paymentVoucher, Request $request)
        {
            $pv = $paymentVoucher;

            $logsQuery = PaymentVoucherLog::with(['user.department', 'user.role'])
                ->where('payment_voucher_id', $pv->id);

            // Filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $logsQuery->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                        ->orWhere('action', 'like', "%$search%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%$search%");
                        });
                });
            }
            if ($request->filled('action')) {
                $logsQuery->where('action', $request->input('action'));
            }
            if ($request->filled('role')) {
                $roleId = $request->input('role');
                $logsQuery->whereHas('user.role', function ($q) use ($roleId) {
                    $q->where('id', $roleId);
                });
            }
            if ($request->filled('department')) {
                $departmentId = $request->input('department');
                $logsQuery->whereHas('user.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            }
            if ($request->filled('date')) {
                $logsQuery->whereDate('created_at', $request->input('date'));
            }

            $perPage = (int) $request->input('per_page', 10);
            $logs = $logsQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

            $roleOptions = Role::select('id', 'name')->orderBy('name')->get();
            $departmentOptions = DepartmentService::getOptionsForFilter();
            $actionOptions = PaymentVoucherLog::where('payment_voucher_id', $pv->id)
                ->select('action')
                ->distinct()
                ->pluck('action');

            $filters = $request->only(['search', 'action', 'role', 'department', 'date', 'per_page']);

            if ($request->wantsJson()) {
                return response()->json([
                    'paymentVoucher' => $pv,
                    'logs' => $logs,
                    'filters' => $filters,
                    'roleOptions' => $roleOptions,
                    'departmentOptions' => $departmentOptions,
                    'actionOptions' => $actionOptions,
                ]);
            }

            return inertia('approval/PaymentVoucherApprovalLog', [
                'paymentVoucher' => $pv,
                'logs' => $logs,
                'filters' => $filters,
                'roleOptions' => $roleOptions,
                'departmentOptions' => $departmentOptions,
                'actionOptions' => $actionOptions,
            ]);
        }

    // ==================== BPB APPROVAL METHODS ====================

        /**
         * Display BPB approval page
         */
        public function bpbs()
        {
            $user = Auth::user();
            $departments = Department::all();

            return inertia('approval/BpbApproval', [
                'departments' => $departments,
                'userRole' => $user->role->name ?? '',
            ]);
        }

        /**
         * Get BPBs for approval
         */
        public function getBpbs(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'bpb')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query = Bpb::query()->with([
                'department',
                'supplier',
                'purchaseOrder',
                'purchaseOrder.perihal',
                'paymentVoucher',
                'creator.role',
                'approver',
                'rejecter',
            ])->whereNotIn('status', ['Draft', 'Canceled']);

            // Filter status sesuai workflow mapping
            $this->applyRoleStatusFilter($query, 'bpb', $userRole);

            // Filters
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('tanggal_start') && $request->filled('tanggal_end')) {
                $query->whereBetween('tanggal', [$request->tanggal_start, $request->tanggal_end]);
            } else {
                if ($request->filled('tanggal_start')) {
                    $query->whereDate('tanggal', '>=', $request->input('tanggal_start'));
                }
                if ($request->filled('tanggal_end')) {
                    $query->whereDate('tanggal', '<=', $request->input('tanggal_end'));
                }
            }
            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }
            if ($request->filled('no_bpb')) {
                $query->where('no_bpb', 'like', '%' . trim($request->input('no_bpb')) . '%');
            }
            if ($request->filled('no_po')) {
                $no = trim($request->input('no_po'));
                $query->whereHas('purchaseOrder', function ($q) use ($no) {
                    $q->where('no_po', 'like', "%$no%");
                });
            }
            if ($request->filled('no_pv')) {
                $no = trim($request->input('no_pv'));
                $query->whereHas('paymentVoucher', function ($q) use ($no) {
                    $q->where('no_pv', 'like', "%$no%");
                });
            }
            if ($request->filled('po_perihal')) {
                $text = trim($request->input('po_perihal'));
                $query->whereHas('purchaseOrder.perihal', function ($q) use ($text) {
                    $q->where('nama', 'like', "%$text%");
                });
            }
            if ($request->filled('search')) {
                $search = trim((string) $request->get('search'));
                $searchColumns = $request->filled('search_columns')
                    ? array_filter(array_map('trim', explode(',', (string) $request->get('search_columns'))))
                    : [];

                if (!empty($searchColumns)) {
                    $query->where(function ($q) use ($search, $searchColumns) {
                        foreach ($searchColumns as $column) {
                            switch ($column) {
                                case 'no_bpb':
                                    $q->orWhere('no_bpb', 'like', "%$search%");
                                    break;
                                case 'no_po':
                                    $q->orWhereHas('purchaseOrder', function ($po) use ($search) {
                                        $po->where('no_po', 'like', "%$search%");
                                    });
                                    break;
                                case 'no_pv':
                                    $q->orWhereHas('paymentVoucher', function ($pv) use ($search) {
                                        $pv->where('no_pv', 'like', "%$search%");
                                    });
                                    break;
                                case 'tanggal':
                                    $q->orWhere('tanggal', 'like', "%$search%");
                                    break;
                                case 'status':
                                    $q->orWhere('status', 'like', "%$search%");
                                    break;
                                case 'supplier':
                                    $q->orWhereHas('supplier', function ($s) use ($search) {
                                        $s->where('nama_supplier', 'like', "%$search%");
                                    });
                                    break;
                                case 'department':
                                    $q->orWhereHas('department', function ($d) use ($search) {
                                        $d->where('name', 'like', "%$search%");
                                    });
                                    break;
                                case 'perihal':
                                    $q->orWhereHas('purchaseOrder.perihal', function ($p) use ($search) {
                                        $p->where('nama', 'like', "%$search%");
                                    });
                                    break;
                                case 'subtotal':
                                case 'diskon':
                                case 'dpp':
                                case 'ppn':
                                case 'pph':
                                case 'grand_total':
                                    $q->orWhereRaw('CAST(' . $column . ' AS CHAR) LIKE ?', ["%$search%"]);
                                    break;
                                case 'keterangan':
                                    $q->orWhere('keterangan', 'like', "%$search%");
                                    break;
                            }
                        }
                    });
                } else {
                    $query->where(function ($q) use ($search) {
                        $q->where('no_bpb', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhere('tanggal', 'like', "%{$search}%")
                            ->orWhere('keterangan', 'like', "%{$search}%")
                            ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%$search%"]);
                    })
                        ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn($sq) => $sq->where('nama_supplier', 'like', "%{$search}%"))
                        ->orWhereHas('purchaseOrder', fn($pq) => $pq->where('no_po', 'like', "%{$search}%"))
                        ->orWhereHas('purchaseOrder.perihal', fn($pp) => $pp->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('paymentVoucher', fn($pv) => $pv->where('no_pv', 'like', "%{$search}%"));
                }
            }

            $perPage = (int) $request->get('per_page', 15);
            $currentPage = (int) $request->get('page', 1);

            // 1) Kumpulkan ID yang actionable (berdasarkan workflow permission)
            $actionableIds = [];
            (clone $query)
                ->with(['department', 'creator.role'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->chunk(500, function ($items) use ($user, &$actionableIds) {
                    foreach ($items as $bpb) {
                        $action = $this->inferActionForBpb($bpb->status, $bpb);
                        if (!$action) continue;
                        if ($this->approvalWorkflowService->canUserApproveBpb($user, $bpb, $action)) {
                            $actionableIds[] = $bpb->id;
                        }
                    }
                });

            $actionableTotal = count($actionableIds);
            $lastPage = (int) max(1, (int) ceil($actionableTotal / max(1, $perPage)));
            $currentPage = min(max(1, $currentPage), $lastPage);

            // 2) Ambil ID untuk halaman saat ini
            $offset = ($currentPage - 1) * $perPage;
            $pageIds = array_slice($actionableIds, $offset, $perPage);

            $items = collect();
            if (!empty($pageIds)) {
                $items = Bpb::with([
                    'department',
                    'supplier',
                    'purchaseOrder',
                    'purchaseOrder.perihal',
                    'paymentVoucher',
                    'creator.role',
                    'approver',
                    'rejecter',
                ])
                    ->whereIn('id', $pageIds)
                    ->get()
                    ->sortBy(function ($m) use ($pageIds) {
                        return array_search($m->id, $pageIds);
                    })
                    ->values();

                // Attach Approved PaymentVoucher by matching purchase_order_id when direct relation is missing
                $poIds = $items->pluck('purchase_order_id')->filter()->unique()->values();
                if ($poIds->isNotEmpty()) {
                    $pvByPo = PaymentVoucher::query()
                        ->whereIn('purchase_order_id', $poIds)
                        ->where('status', 'Approved')
                        ->orderByDesc('id')
                        ->get(['id', 'no_pv', 'purchase_order_id'])
                        ->keyBy('purchase_order_id');

                    $items->each(function ($bpb) use ($pvByPo) {
                        if ((!$bpb->relationLoaded('paymentVoucher')) || $bpb->paymentVoucher === null) {
                            $poId = (int) ($bpb->purchase_order_id ?? 0);
                            if ($poId && $pvByPo->has($poId)) {
                                $bpb->setRelation('paymentVoucher', $pvByPo->get($poId));
                            }
                        }
                    });
                }
            }

            $from = $actionableTotal > 0 ? ($offset + 1) : 0;
            $to = $offset + $items->count();

            // Build Laravel-like pagination links (Previous, pages, Next)
            $prevUrl = $currentPage > 1
                ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage])
                : null;
            $nextUrl = $currentPage < $lastPage
                ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage])
                : null;
            $links = [];
            $links[] = ['url' => $prevUrl, 'label' => '&laquo; Previous', 'active' => false];
            for ($i = 1; $i <= $lastPage; $i++) {
                $links[] = [
                    'url' => request()->fullUrlWithQuery(['page' => $i, 'per_page' => $perPage]),
                    'label' => (string) $i,
                    'active' => $i === $currentPage,
                ];
            }
            $links[] = ['url' => $nextUrl, 'label' => 'Next &raquo;', 'active' => false];

            $counts = [
                'pending'  => Bpb::where('status', 'In Progress')->count(),
                'approved' => Bpb::where('status', 'Approved')->count(),
                'rejected' => Bpb::where('status', 'Rejected')->count(),
            ];

            return response()->json([
                'data' => $items,
                'pagination' => [
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $actionableTotal,
                    'from' => $from,
                    'to' => $to,
                    'links' => $links,
                    'prev_page_url' => $currentPage > 1 ? request()->fullUrlWithQuery(['page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                    'next_page_url' => $currentPage < $lastPage ? request()->fullUrlWithQuery(['page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                ],
                'counts' => $counts,
            ]);
        }

        /**
         * Get BPB count for dashboard
         */
        public function getBpbCount(): JsonResponse
        {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['count' => 0]);
                }

                $userRole = $user->role->name ?? '';
                if (!$this->canAccessDocumentType($userRole, 'bpb')) {
                    return response()->json(['count' => 0]);
                }

                $statuses = $this->getStatusesForRole('bpb', $userRole);
                if ($statuses === []) {
                    return response()->json(['count' => 0]);
                }

                $query = Bpb::query();
                if (is_array($statuses)) {
                    $query->whereIn('status', $statuses);
                }

                // Actionable-only count using workflow permission checks
                $actionable = 0;
                $query->with(['creator.role'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->chunk(500, function ($items) use ($user, &$actionable) {
                        foreach ($items as $bpb) {
                            $action = $this->inferActionForBpb($bpb->status, $bpb);
                            if (!$action) continue;
                            if ($this->approvalWorkflowService->canUserApproveBpb($user, $bpb, $action)) {
                                $actionable++;
                            }
                        }
                    });

                return response()->json(['count' => $actionable]);
            } catch (\Exception $e) {
                return response()->json(['count' => 0, 'error' => $e->getMessage()]);
            }
        }

        /**
         * Approve a BPB
         */
        public function approveBpb(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'bpb')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $bpb = Bpb::findOrFail($id);

            if (!$this->approvalWorkflowService->canUserApproveBpb($user, $bpb, 'approve')) {
                return response()->json(['error' => 'Unauthorized to approve this BPB'], 403);
            }

            try {
                DB::beginTransaction();

                $bpb->update([
                    'status' => 'Approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                $this->logApprovalActivity($user, $bpb, 'approved');

                DB::commit();
                return response()->json(['message' => 'BPB approved successfully', 'bpb' => $bpb->fresh(['approver'])]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to approve BPB'], 500);
            }
        }

        /**
         * Reject a BPB
         */
        public function rejectBpb(Request $request, $id): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';

            if (!$this->canAccessDocumentType($userRole, 'bpb')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $bpb = Bpb::findOrFail($id);

            if (!$this->approvalWorkflowService->canUserApproveBpb($user, $bpb, 'reject')) {
                return response()->json(['error' => 'Unauthorized to reject this BPB'], 403);
            }

            try {
                DB::beginTransaction();

                $bpb->update([
                    'status' => 'Rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                ]);

                $this->logApprovalActivity($user, $bpb, 'rejected');

                DB::commit();
                return response()->json(['message' => 'BPB rejected successfully', 'bpb' => $bpb->fresh(['rejecter'])]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to reject BPB'], 500);
            }
        }

        /**
         * Bulk approve BPBs
         */
        public function bulkApproveBpbs(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';
            if (!$this->canAccessDocumentType($userRole, 'bpb')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $ids = $request->input('bpb_ids', []);
            if (empty($ids)) {
                return response()->json(['error' => 'No BPBs selected'], 400);
            }

            $bpbs = Bpb::whereIn('id', $ids)->get();
            $approved = 0;
            $errors = [];

            try {
                DB::beginTransaction();
                foreach ($bpbs as $bpb) {
                    $canApprove = $this->approvalWorkflowService->canUserApproveBpb($user, $bpb, 'approve');
                    if ($canApprove && $bpb->status === 'In Progress') {
                        $bpb->update([
                            'status' => 'Approved',
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);
                        $this->logApprovalActivity($user, $bpb, 'approved');
                        $approved++;
                    } else {
                        $errors[] = "Cannot approve BPB #{$bpb->no_bpb} at status {$bpb->status}";
                    }
                }
                DB::commit();
                return response()->json(['message' => "Successfully approved {$approved} BPBs", 'approved_count' => $approved, 'errors' => $errors]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk approve BPBs'], 500);
            }
        }

        /**
         * Bulk reject BPBs
         */
        public function bulkRejectBpbs(Request $request): JsonResponse
        {
            $user = Auth::user();
            $userRole = $user->role->name ?? '';
            if (!$this->canAccessDocumentType($userRole, 'bpb')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $ids = $request->input('bpb_ids', []);
            $reason = $request->input('reason', '');
            if (empty($ids)) {
                return response()->json(['error' => 'No BPBs selected'], 400);
            }

            $bpbs = Bpb::whereIn('id', $ids)->get();
            $rejected = 0;
            $errors = [];

            try {
                DB::beginTransaction();
                foreach ($bpbs as $bpb) {
                    $canReject = $this->approvalWorkflowService->canUserApproveBpb($user, $bpb, 'reject');
                    if ($canReject && $bpb->status === 'In Progress') {
                        $bpb->update([
                            'status' => 'Rejected',
                            'rejected_by' => $user->id,
                            'rejected_at' => now(),
                        ]);
                        $this->logApprovalActivity($user, $bpb, 'rejected');
                        $rejected++;
                    } else {
                        $errors[] = "Cannot reject BPB #{$bpb->no_bpb} at status {$bpb->status}";
                    }
                }
                DB::commit();
                return response()->json(['message' => "Successfully rejected {$rejected} BPBs", 'rejected_count' => $rejected, 'errors' => $errors]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Failed to bulk reject BPBs'], 500);
            }
        }

        /**
         * Get approval progress for a BPB (API)
         */
        public function getBpbProgress($id): JsonResponse
        {
            $bpb = Bpb::findOrFail($id);
            $progress = $this->approvalWorkflowService->getApprovalProgressForBpb($bpb);
            return response()->json([
                'progress' => $progress,
                'current_status' => $bpb->status,
            ]);
        }

        /**
         * Display BPB detail within Approval module
         */
        public function bpbDetail(Bpb $bpb)
        {
            $bp = $bpb->load([
                'items',
                'department',
                'supplier' => function ($q) {
                    $q->withoutGlobalScopes();
                },
                'purchaseOrder' => function ($q) {
                    $q->withoutGlobalScopes()->with(['perihal', 'items']);
                },
                'paymentVoucher',
                'creator.role',
                'approver',
                'rejecter',
            ]);

            // Fallback: attach Approved PV by purchase_order_id when direct relation is missing
            if (!$bp->paymentVoucher && $bp->purchase_order_id) {
                $pv = PaymentVoucher::query()
                    ->where('purchase_order_id', $bp->purchase_order_id)
                    ->where('status', 'Approved')
                    ->orderByDesc('id')
                    ->first(['id', 'no_pv', 'purchase_order_id']);
                if ($pv) {
                    $bp->setRelation('paymentVoucher', $pv);
                }
            }

            return inertia('approval/BpbApprovalDetail', [
                'bpb' => $bp,
            ]);
        }

        /**
         * Display BPB logs within Approval module
         */
        public function bpbLog(Bpb $bpb, Request $request)
        {
            $bp = $bpb;

            $logsQuery = BpbLog::with(['user.department', 'user.role'])
                ->where('bpb_id', $bp->id);

            // Filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $logsQuery->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                        ->orWhere('action', 'like', "%$search%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%$search%");
                        });
                });
            }
            if ($request->filled('action')) {
                $logsQuery->where('action', $request->input('action'));
            }
            if ($request->filled('role')) {
                $roleId = $request->input('role');
                $logsQuery->whereHas('user.role', function ($q) use ($roleId) {
                    $q->where('id', $roleId);
                });
            }
            if ($request->filled('department')) {
                $departmentId = $request->input('department');
                $logsQuery->whereHas('user.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            }
            if ($request->filled('date')) {
                $logsQuery->whereDate('created_at', $request->input('date'));
            }

            $perPage = (int) $request->input('per_page', 10);
            $logs = $logsQuery->orderByDesc('created_at')->paginate($perPage)->withQueryString();

            $roleOptions = Role::select('id', 'name')->orderBy('name')->get();
            $departmentOptions = DepartmentService::getOptionsForFilter();
            $actionOptions = BpbLog::where('bpb_id', $bp->id)
                ->select('action')
                ->distinct()
                ->pluck('action');

            $filters = $request->only(['search', 'action', 'role', 'department', 'date', 'per_page']);

            if ($request->wantsJson()) {
                return response()->json([
                    'bpb' => $bp,
                    'logs' => $logs,
                    'filters' => $filters,
                    'roleOptions' => $roleOptions,
                    'departmentOptions' => $departmentOptions,
                    'actionOptions' => $actionOptions,
                ]);
            }

            return inertia('approval/BpbApprovalLog', [
                'bpb' => $bp,
                'logs' => $logs,
                'filters' => $filters,
                'roleOptions' => $roleOptions,
                'departmentOptions' => $departmentOptions,
                'actionOptions' => $actionOptions,
            ]);
        }
    }

    // ======== Helper methods to infer next action for actionable-only filtering ========

    trait ApprovalActionInference
    {
        private function inferActionForPo(string $status, _Po $po): ?string
        {
            // Department overrides: Zi&Glo / Human Greatness may allow direct approve at Verified for Direksi
            $dept = $po->department?->name;
            $creatorRole = $po->creator?->role?->name;

            if ($status === 'In Progress') {
                // Staff Digital Marketing flow: Kadiv validates first
                if ($creatorRole === 'Staff Digital Marketing') return 'validate';
                // Default first step for other creators is verify
                return 'verify';
            }
            if ($status === 'Verified') {
                if (in_array($dept, ['Zi&Glo', 'Human Greatness'], true)) return 'approve';
                return 'validate';
            }
            if ($status === 'Validated') return 'approve';
            return null;
        }

        private function inferActionForMemo(string $status, _Memo $memo): ?string
        {
            if ($status === 'In Progress') return 'verify';
            if ($status === 'Verified') return 'approve';
            if ($status === 'Validated') return 'approve';
            return null;
        }

        private function inferActionForPv(string $status, _Pv $pv): ?string
        {
            $tipe = $pv->tipe_pv ?? null;

            // Pajak: Kabag verify -> Kadiv validate -> Direksi approve
            if ($tipe === 'Pajak') {
                if ($status === 'In Progress') return 'verify';
                if ($status === 'Verified') return 'validate';
                if ($status === 'Validated') return 'approve';
                return null;
            }

            // Default: Kabag verify -> Direksi approve
            if ($status === 'In Progress') return 'verify';
            if ($status === 'Verified') return 'approve';
            return null;
        }

        private function inferActionForBpb(string $status, _Bpb $bpb): ?string
        {
            if ($status === 'In Progress') return 'approve';
            return null;
        }
    }
}
