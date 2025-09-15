<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Department;
use App\Models\PurchaseOrderLog;
use App\Models\MemoPembayaranLog;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\MemoPembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\DepartmentService;
use App\Services\ApprovalWorkflowService;

class ApprovalController extends Controller
{
    protected $approvalWorkflowService;

    public function __construct(ApprovalWorkflowService $approvalWorkflowService)
    {
        $this->approvalWorkflowService = $approvalWorkflowService;
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
    public function getPurchaseOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $userRole = $user->role->name ?? '';

            // Check if user can access purchase orders
            if (!$this->canAccessDocumentType($userRole, 'purchase_order')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Use DepartmentScope automatically (do NOT bypass) so 'All' access works and multi-department users are respected
            $query = PurchaseOrder::with(['department', 'supplier', 'perihal', 'creator.role'])
                ->whereNotIn('status', ['Draft', 'Canceled']);

            // Note: DepartmentScope already filters by user's departments automatically

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
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
                $search = $request->get('search');

                // Ambil kolom yang dipilih user untuk pencarian
                $selectedColumnsRaw = $request->get('search_columns', '');
                $selectedKeys = array_filter(array_map('trim', explode(',', (string) $selectedColumnsRaw)));

                // Peta dari key kolom tabel ke kolom database / relasi
                $columnMap = [
                    'no_po' => ['type' => 'column', 'name' => 'no_po'],
                    'no_invoice' => ['type' => 'column', 'name' => 'no_invoice'],
                    'tipe_po' => ['type' => 'column', 'name' => 'tipe_po'],
                    'tanggal' => ['type' => 'column', 'name' => 'tanggal'],
                    'department' => ['type' => 'relation', 'relation' => 'department', 'field' => 'name'],
                    'perihal' => ['type' => 'relation', 'relation' => 'perihal', 'field' => 'nama'],
                    'supplier' => ['type' => 'relation', 'relation' => 'supplier', 'field' => 'nama_supplier'],
                    'metode_pembayaran' => ['type' => 'column', 'name' => 'metode_pembayaran'],
                    'total' => ['type' => 'column', 'name' => 'total'],
                    'diskon' => ['type' => 'column', 'name' => 'diskon'],
                    'ppn' => ['type' => 'column', 'name' => 'ppn_nominal'],
                    'pph' => ['type' => 'column', 'name' => 'pph_nominal'],
                    'grand_total' => ['type' => 'column', 'name' => 'grand_total'],
                    'status' => ['type' => 'column', 'name' => 'status'],
                    'created_by' => ['type' => 'relation', 'relation' => 'creator', 'field' => 'name'],
                    'created_at' => ['type' => 'column', 'name' => 'created_at'],
                ];

                // Jika tidak ada kolom terpilih dari frontend, fallback ke kolom umum
                if (empty($selectedKeys)) {
                    $selectedKeys = ['no_po', 'supplier', 'perihal'];
                }

                $query->where(function ($q) use ($search, $selectedKeys, $columnMap) {
                    foreach ($selectedKeys as $key) {
                        if (!array_key_exists($key, $columnMap)) {
                            continue;
                        }

                        $config = $columnMap[$key];
                        if ($config['type'] === 'column') {
                            $q->orWhere('purchase_orders.' . $config['name'], 'like', "%{$search}%");
                        } elseif ($config['type'] === 'relation') {
                            $relation = $config['relation'];
                            $field = $config['field'];
                            $q->orWhereHas($relation, function ($sq) use ($field, $search) {
                                $sq->where($field, 'like', "%{$search}%");
                            });
                        }
                    }
                });
            }

            // Get counts for different statuses
            $counts = $this->getPurchaseOrderCounts($user, $userRole);

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $purchaseOrders = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'data' => $purchaseOrders->items(),
                'pagination' => [
                    'current_page' => $purchaseOrders->currentPage(),
                    'last_page' => $purchaseOrders->lastPage(),
                    'per_page' => $purchaseOrders->perPage(),
                    'total' => $purchaseOrders->total(),
                    'from' => $purchaseOrders->firstItem(),
                    'to' => $purchaseOrders->lastItem(),
                    // Tambahkan struktur links agar konsisten dengan modul lain
                    'links' => $purchaseOrders->toArray()['links'] ?? [],
                    'prev_page_url' => $purchaseOrders->previousPageUrl(),
                    'next_page_url' => $purchaseOrders->nextPageUrl(),
                ],
                'counts' => $counts
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

            $statuses = $this->getStatusesForRole('purchase_order', $userRole);

            $query = PurchaseOrder::query();

            if ($statuses === []) {
                // Tidak ada akses status
                return response()->json(['count' => 0]);
            } elseif (is_array($statuses)) {
                // Role biasa → filter status
                $query->whereIn('status', $statuses);
            }
            // Kalau null → Admin → semua status (no filter)

            $count = $query->count();

            return response()->json(['count' => $count]);

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

            $query = MemoPembayaran::query();

            if ($statuses === []) {
                return response()->json(['count' => 0]);
            } elseif (is_array($statuses)) {
                $query->whereIn('status', $statuses);
            }
            // Kalau null → Admin → semua status (no filter)

            $count = $query->count();

            return response()->json(['count' => $count]);

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

                if ($canApprove && in_array($po->status, ['Validated', 'Verified'])) {
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
                return in_array($documentType, ['purchase_order', 'anggaran', 'memo_pembayaran']);

            case 'Kabag':
                return in_array($documentType, ['purchase_order', 'payment_voucher', 'anggaran', 'bpb', 'realisasi', 'memo_pembayaran']);

            case 'Staff Akunting & Finance':
                return in_array($documentType, ['realisasi']);

            case 'Kadiv':
                return in_array($documentType, ['purchase_order', 'payment_voucher', 'anggaran', 'memo_pembayaran']);

            case 'Direksi':
                return in_array($documentType, ['purchase_order', 'anggaran', 'payment_voucher', 'realisasi']);

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
            // Use DepartmentScope automatically (do NOT bypass) so 'All' access works and multi-department users are respected
            $query = PurchaseOrder::query();

            // Note: DepartmentScope already filters by user's departments automatically

            $inProgress = (clone $query)->where('status', 'In Progress')->count();
            $verified = (clone $query)->where('status', 'Verified')->count();
            $validated = (clone $query)->where('status', 'Validated')->count();
            $approved = (clone $query)->where('status', 'Approved')->count();
            $rejected = (clone $query)->where('status', 'Rejected')->count();

            return [
                'in_progress' => $inProgress,
                'verified' => $verified,
                'validated' => $validated,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $inProgress + $verified + $validated // Total pending approvals
            ];

        } catch (\Exception $e) {
            return [
                'in_progress' => 0,
                'verified' => 0,
                'validated' => 0,
                'approved' => 0,
                'rejected' => 0,
                'pending' => 0
            ];
        }
    }

    private function getWorkflowMapping(): array
    {
        return [
            'purchase_order' => [
                'Admin' => null,
                'Staff Toko' => 'In Progress',
                'Kepala Toko' => 'In Progress',
                'Kadiv' => 'Verified',
                'Kabag' => 'In Progress',
                'Direksi' => 'Validated',
            ],
            'memo_pembayaran' => [
                'Admin' => null,
                'Staff Toko' => 'In Progress',
                'Kepala Toko' => 'In Progress',
                'Kadiv' => 'In Progress', // approve langsung
                'Kabag' => 'In Progress', // approve langsung
                'Direksi' => null, // direksi tidak approve memo
            ],
        ];
    }

    private function getStatusesForRole(string $docType, string $role): array|null
    {
        $mapping = $this->getWorkflowMapping();

        // cek apakah docType ada di mapping
        if (!isset($mapping[$docType])) {
            return [];
        }

        // bikin key role di mapping jadi lowercase semua
        $roleMapping = array_change_key_case($mapping[$docType], CASE_LOWER);

        $roleLower = strtolower($role);

        if (!isset($roleMapping[$roleLower])) {
            return [];
        }

        $statuses = $roleMapping[$roleLower];

        // null = admin / role yang bisa lihat semua
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
        }
    }

    private function getActionDescription(string $action, $document, User $user): string
    {
        $userName = $user->name ?? 'Unknown User';
        $documentType = $document instanceof \App\Models\PurchaseOrder ? 'Purchase Order' : 'Memo Pembayaran';
        $documentNumber = $document->no_po ?? $document->no_memo ?? 'N/A';

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
            'supplier' => function ($q) { $q->withoutGlobalScopes(); },
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
            'rejecter'
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

        $query = MemoPembayaran::query()
            ->with(['department', 'purchaseOrders.perihal', 'purchaseOrders.supplier', 'supplier', 'bank', 'creator.role', 'verifier', 'validator', 'approver', 'rejecter']);

        // No status filter - show all statuses for all roles

        // Apply additional filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('no_mb', 'like', "%{$search}%")
                  ->orWhere('detail_keperluan', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('tanggal', 'like', "%{$search}%")
                  ->orWhereRaw('CAST(grand_total AS CHAR) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('department', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('purchaseOrders', function ($q) use ($search) {
                      $q->where('no_po', 'like', "%{$search}%");
                  });
            });
        }

        // Optional: honor search_columns to narrow search scope similar to index page
        if ($request->filled('search') && $request->filled('search_columns')) {
            $columns = array_filter(explode(',', $request->search_columns));
            $search = $request->search;
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $col) {
                    switch ($col) {
                        case 'no_mb':
                            $q->orWhere('no_mb', 'like', "%{$search}%");
                            break;
                        case 'detail_keperluan':
                            $q->orWhere('detail_keperluan', 'like', "%{$search}%");
                            break;
                        case 'keterangan':
                            $q->orWhere('keterangan', 'like', "%{$search}%");
                            break;
                        case 'status':
                            $q->orWhere('status', 'like', "%{$search}%");
                            break;
                        case 'tanggal':
                            $q->orWhere('tanggal', 'like', "%{$search}%");
                            break;
                        case 'grand_total':
                        case 'total':
                        case 'diskon':
                        case 'ppn_nominal':
                        case 'pph_nominal':
                            $q->orWhereRaw('CAST(' . $col . ' AS CHAR) LIKE ?', ["%{$search}%"]);
                            break;
                        case 'department':
                            $q->orWhereHas('department', function ($q2) use ($search) {
                                $q2->where('name', 'like', "%{$search}%");
                            });
                            break;
                        case 'no_po':
                            $q->orWhereHas('purchaseOrders', function ($q2) use ($search) {
                                $q2->where('no_po', 'like', "%{$search}%");
                            });
                            break;
                        case 'supplier':
                            $q->orWhereHas('purchaseOrders.supplier', function ($q2) use ($search) {
                                $q2->where('nama_supplier', 'like', "%{$search}%");
                            });
                            break;
                        default:
                            break;
                    }
                }
            });
        }

        $perPage = $request->get('per_page', 15);
        $memoPembayarans = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get counts for different statuses
        $counts = [
            'pending' => MemoPembayaran::whereIn('status', ['In Progress', 'Verified', 'Validated'])->count(),
            'approved' => MemoPembayaran::where('status', 'Approved')->count(),
            'rejected' => MemoPembayaran::where('status', 'Rejected')->count(),
        ];

        return response()->json([
            'data' => $memoPembayarans->items(),
            'pagination' => [
                'current_page' => $memoPembayarans->currentPage(),
                'last_page' => $memoPembayarans->lastPage(),
                'per_page' => $memoPembayarans->perPage(),
                'total' => $memoPembayarans->total(),
                'from' => $memoPembayarans->firstItem(),
                'to' => $memoPembayarans->lastItem(),
                'links' => $memoPembayarans->toArray()['links'] ?? [],
                'prev_page_url' => $memoPembayarans->previousPageUrl(),
                'next_page_url' => $memoPembayarans->nextPageUrl(),
            ],
            'counts' => $counts,
        ]);
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
        $user = Auth::user();
        $userRole = $user->role->name ?? '';

        if (!$this->canAccessDocumentType($userRole, 'memo_pembayaran')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
            'purchaseOrders.perihal',
            'supplier',
            'bank',
            'pph',
            'creator',
            'verifier',
            'validator',
            'approver',
            'rejecter'
        ]);

        return inertia('approval/MemoPembayaranApprovalDetail', [
            'memoPembayaran' => $memo,
        ]);
    }
}
