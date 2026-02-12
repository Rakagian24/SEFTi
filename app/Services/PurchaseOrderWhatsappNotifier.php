<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class PurchaseOrderWhatsappNotifier
{
    protected ApprovalWorkflowService $workflowService;
    protected ApprovalEmailNotifier $emailNotifier;

    public function __construct(ApprovalWorkflowService $workflowService, ApprovalEmailNotifier $emailNotifier)
    {
        $this->workflowService = $workflowService;
        $this->emailNotifier = $emailNotifier;
    }

    public function notifyFirstApproverOnCreated(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status === 'Draft') {
            return;
        }

        $nextStep = $this->workflowService->getNextApprovalStep($purchaseOrder);
        if (!$nextStep) {
            return;
        }

        $requiredRole = $this->workflowService->getRequiredRoleForStep($purchaseOrder, $nextStep);
        if (!$requiredRole) {
            return;
        }

        $this->notifyApproversForStep($purchaseOrder, $requiredRole, $nextStep);
    }

    public function notifyNextApproverOnStatusChange(PurchaseOrder $purchaseOrder, string $oldStatus, string $newStatus): void
    {
        if (!in_array($newStatus, ['Verified', 'Validated'], true)) {
            return;
        }

        $nextStep = $this->workflowService->getNextApprovalStep($purchaseOrder);
        if (!$nextStep) {
            return;
        }

        $requiredRole = $this->workflowService->getRequiredRoleForStep($purchaseOrder, $nextStep);
        if (!$requiredRole) {
            return;
        }

        $this->notifyApproversForStep($purchaseOrder, $requiredRole, $nextStep);
    }

    public function notifyCreatorOnApproved(PurchaseOrder $purchaseOrder, User $actor): void
    {
        Log::info('WA PO - notifyCreatorOnApproved called', [
            'po_id' => $purchaseOrder->id ?? null,
            'po_status' => $purchaseOrder->status ?? null,
            'creator_id' => $purchaseOrder->created_by ?? null,
            'actor_id' => $actor->id ?? null,
        ]);

        $creator = $purchaseOrder->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $purchaseOrder->no_po,
            ];

            $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Purchase Order detail page)
        $detailUrl = route('purchase-orders.show', $purchaseOrder);
        $this->emailNotifier->notifyCreatorApproved($creator, $documentTypeLabel, (string) $purchaseOrder->no_po, $detailUrl);
    }

    public function notifyCreatorOnRejected(PurchaseOrder $purchaseOrder, User $actor, string $reason, string $oldStatus): void
    {
        Log::info('WA PO - notifyCreatorOnRejected called', [
            'po_id' => $purchaseOrder->id ?? null,
            'old_status' => $oldStatus,
            'new_status' => $purchaseOrder->status ?? null,
            'creator_id' => $purchaseOrder->created_by ?? null,
            'actor_id' => $actor->id ?? null,
        ]);

        $creator = $purchaseOrder->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';
        $stageLabel = $this->inferStageLabelForRejection($purchaseOrder, $oldStatus);

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $purchaseOrder->no_po,
                '4' => $stageLabel,
                '5' => $actor->name ?? '-',
                '6' => $actor->role->name ?? '-',
                '7' => $reason,
            ];

            $this->sendRejectedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Purchase Order detail page)
        $detailUrl = route('purchase-orders.show', $purchaseOrder);
        $this->emailNotifier->notifyCreatorRejected(
            $creator,
            $documentTypeLabel,
            (string) $purchaseOrder->no_po,
            $stageLabel,
            $actor->name ?? null,
            $actor->role->name ?? null,
            $reason,
            $detailUrl
        );
    }

    protected function notifyApproversForStep(PurchaseOrder $purchaseOrder, string $requiredRole, string $step): void
    {
        Log::info('WA PO - notifyApproversForStep called', [
            'po_id' => $purchaseOrder->id,
            'po_status' => $purchaseOrder->status,
            'creator_role' => $purchaseOrder->creator?->role?->name,
            'creator_dept' => $purchaseOrder->creator?->department?->name,
            'po_dept_id' => $purchaseOrder->department_id,
            'required_role' => $requiredRole,
            'step' => $step
        ]);

        $departmentId = $purchaseOrder->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $purchaseOrder->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = Department::where('name', 'Finance')->value('id');
            Log::info('WA PO - Routing to Finance department', [
                'creator_role' => $creatorRole,
                'required_role' => $requiredRole,
                'finance_dept_id' => $financeDeptId,
                'original_dept_id' => $departmentId
            ]);
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        // Special case: Kabag should receive notifications only for POs created by Staff Akunting & Finance
        if ($requiredRole === 'Kabag' && $purchaseOrder->creator?->role?->name !== 'Staff Akunting & Finance') {
            return;
        }

        if ($requiredRole === 'Kabag') {
            $targets = User::query()
                ->whereHas('role', function ($q) use ($requiredRole) {
                    $q->where('name', $requiredRole);
                })
                ->whereNotNull('phone')
                ->get();
        } else {
            // For other roles, use department filtering
            $targets = User::query()
                ->whereHas('role', function ($q) use ($requiredRole) {
                    $q->where('name', $requiredRole);
                })
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->where(function ($inner) use ($departmentId) {
                        $inner->whereHas('department', function ($d) use ($departmentId) {
                            $d->where('departments.id', $departmentId);
                        })
                        ->orWhereHas('departments', function ($d) use ($departmentId) {
                            $d->where('departments.id', $departmentId);
                        });
                    });
                })
                ->whereNotNull('phone')
                ->get();
        }

        if ($targets->isEmpty()) {
            Log::info('WA PO - No targets found', [
                'required_role' => $requiredRole,
                'department_id' => $departmentId,
                'creator_role' => $creatorRole
            ]);
            return;
        }

        Log::info('WA PO - Targets found', [
            'required_role' => $requiredRole,
            'department_id' => $departmentId,
            'targets_count' => $targets->count(),
            'target_names' => $targets->pluck('name')->all()
        ]);

        $creator = $purchaseOrder->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';
        $stageLabel = $this->mapStepToStageLabel($step);

        foreach ($targets as $user) {
            $phone = (string) $user->phone;

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $purchaseOrder->no_po,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            // WhatsApp if phone exists
            if ($phone !== '') {
                $this->sendStage1TemplateMessage($phone, $variables);
            }

            // Email to approver
            $detailUrl = route('approval.purchase-orders.detail', $purchaseOrder);
            $this->emailNotifier->notifyApprover(
                $user,
                $documentTypeLabel,
                (string) $purchaseOrder->no_po,
                $stageLabel,
                $creatorName,
                $creatorRole,
                $detailUrl
            );
        }
    }

    /**
     * Resolve approver targets for a given purchase order and workflow role,
     * applying special routing rules (e.g. Staff Akunting & Finance  Kadiv/Direksi Finance).
     */
    protected function resolveApproverTargets(PurchaseOrder $purchaseOrder, string $requiredRole)
    {
        $departmentId = $purchaseOrder->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $purchaseOrder->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = Department::where('name', 'Finance')->value('id');
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        return User::query()
            ->whereHas('role', function ($q) use ($requiredRole) {
                $q->where('name', $requiredRole);
            })
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where(function ($inner) use ($departmentId) {
                    $inner->whereHas('department', function ($d) use ($departmentId) {
                        $d->where('departments.id', $departmentId);
                    })
                    ->orWhereHas('departments', function ($d) use ($departmentId) {
                        $d->where('departments.id', $departmentId);
                    });
                });
            })
            ->whereNotNull('phone')
            ->get();
    }

    /**
     * Bulk summary notification to next approvers for a collection of POs.
     * This is intended for bulk verify/validate/approve actions from list views
     * to avoid sending 1 WhatsApp per document.
     *
     * @param iterable<PurchaseOrder> $purchaseOrders
     */
    public function notifyBulkNextApproverSummary(iterable $purchaseOrders): void
    {
        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';

        // Map of approver user_id => ['user' => User, 'count' => int]
        $counters = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            if (!$purchaseOrder instanceof PurchaseOrder) {
                continue;
            }

            if ($purchaseOrder->status === 'Draft') {
                continue;
            }

            $nextStep = $this->workflowService->getNextApprovalStep($purchaseOrder);
            if (!$nextStep) {
                continue;
            }

            $requiredRole = $this->workflowService->getRequiredRoleForStep($purchaseOrder, $nextStep);
            if (!$requiredRole) {
                continue;
            }

            $targets = $this->resolveApproverTargets($purchaseOrder, $requiredRole);
            foreach ($targets as $user) {
                $id = (int) $user->id;
                if (!isset($counters[$id])) {
                    $counters[$id] = ['user' => $user, 'count' => 0];
                }
                $counters[$id]['count']++;
            }
        }

        foreach ($counters as $entry) {
            $user = $entry['user'];
            $count = (int) ($entry['count'] ?? 0);
            $phone = (string) ($user->phone ?? '');
            if ($count <= 0) {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => (string) $count,
                '3' => $documentTypeLabel,
            ];

            // WhatsApp bulk summary if phone exists
            if ($phone !== '') {
                $this->sendBulkStage1TemplateMessage($phone, $variables);
            }

            // Email bulk summary to next approver
            $listUrl = route('approval.purchase-orders');
            $this->emailNotifier->notifyBulkSummary(
                $user,
                $documentTypeLabel,
                $count,
                'menunggu tindakan Anda',
                $listUrl
            );
        }
    }

    protected function mapStepToStageLabel(string $step): string
    {
        if ($step === 'verified') {
            return 'Verifikasi';
        }
        if ($step === 'validated') {
            return 'Validasi';
        }
        if ($step === 'approved') {
            return 'Approval';
        }

        return ucfirst($step);
    }

    protected function inferStageLabelForRejection(PurchaseOrder $purchaseOrder, string $oldStatus): string
    {
        // Approximate rejection stage based solely on previous status.
        // This avoids calling private workflow methods while keeping
        // messages understandable for users.

        if ($oldStatus === 'In Progress') {
            return $this->mapStepToStageLabel('verified');
        }

        if ($oldStatus === 'Verified') {
            // Could be either validate or approve depending on workflow;
            // use "Validasi" as an intermediate stage label.
            return $this->mapStepToStageLabel('validated');
        }

        if ($oldStatus === 'Validated') {
            return $this->mapStepToStageLabel('approved');
        }

        return 'Approval';
    }

    protected function sendStage1TemplateMessage(string $phone, array $variables): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.whatsapp_from');
        $templateSid = (string) env('TWILIO_TEMPLATE_STAGE1_SID');

        if ($sid === '' || $token === '' || $from === '' || $templateSid === '') {
            return;
        }

        $client = new TwilioClient($sid, $token);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return;
        }

        $to = 'whatsapp:+' . $digits;
        $fromWhatsapp = str_starts_with($from, 'whatsapp:') ? $from : ('whatsapp:' . $from);

        $payload = [
            'from' => $fromWhatsapp,
            'contentSid' => $templateSid,
            'contentVariables' => json_encode($variables, JSON_THROW_ON_ERROR),
        ];

        try {
            Log::info('WA PO - sending stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PO - failed to send stage1 WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send bulk summary notification (for multiple documents) using bulk template.
     * Variables:
     *  1 => system name (e.g. SEFTi)
     *  2 => document count
     *  3 => document type label (e.g. Purchase Order)
     */
    protected function sendBulkStage1TemplateMessage(string $phone, array $variables): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.whatsapp_from');
        $templateSid = (string) env('TWILIO_TEMPLATE_BULK_SID');

        if ($sid === '' || $token === '' || $from === '' || $templateSid === '') {
            return;
        }

        $client = new TwilioClient($sid, $token);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return;
        }

        $to = 'whatsapp:+' . $digits;
        $fromWhatsapp = str_starts_with($from, 'whatsapp:') ? $from : ('whatsapp:' . $from);

        $payload = [
            'from' => $fromWhatsapp,
            'contentSid' => $templateSid,
            'contentVariables' => json_encode($variables, JSON_THROW_ON_ERROR),
        ];

        try {
            Log::info('WA PO - sending bulk stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PO - failed to send bulk stage1 WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendRejectedTemplateMessage(string $phone, array $variables): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.whatsapp_from');
        $templateSid = (string) env('TWILIO_TEMPLATE_REJECTED_SID');

        Log::info('WA PO - rejected template config check', [
            'sid_empty' => $sid === '',
            'token_empty' => $token === '',
            'from_empty' => $from === '',
            'templateSid_empty' => $templateSid === '',
        ]);

        if ($sid === '' || $token === '' || $from === '' || $templateSid === '') {
            return;
        }

        $client = new TwilioClient($sid, $token);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return;
        }

        $to = 'whatsapp:+' . $digits;
        $fromWhatsapp = str_starts_with($from, 'whatsapp:') ? $from : ('whatsapp:' . $from);

        $payload = [
            'from' => $fromWhatsapp,
            'contentSid' => $templateSid,
            'contentVariables' => json_encode($variables, JSON_THROW_ON_ERROR),
        ];

        try {
            Log::info('WA PO - sending rejected WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PO - failed to send rejected WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendApprovedTemplateMessage(string $phone, array $variables): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.whatsapp_from');
        $templateSid = (string) env('TWILIO_TEMPLATE_APPROVED_SID');

        Log::info('WA PO - approved template config check', [
            'sid_empty' => $sid === '',
            'token_empty' => $token === '',
            'from_empty' => $from === '',
            'templateSid_empty' => $templateSid === '',
        ]);

        if ($sid === '' || $token === '' || $from === '' || $templateSid === '') {
            return;
        }

        $client = new TwilioClient($sid, $token);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return;
        }

        $to = 'whatsapp:+' . $digits;
        $fromWhatsapp = str_starts_with($from, 'whatsapp:') ? $from : ('whatsapp:' . $from);

        $payload = [
            'from' => $fromWhatsapp,
            'contentSid' => $templateSid,
            'contentVariables' => json_encode($variables, JSON_THROW_ON_ERROR),
        ];

        try {
            Log::info('WA PO - sending approved WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PO - failed to send approved WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
