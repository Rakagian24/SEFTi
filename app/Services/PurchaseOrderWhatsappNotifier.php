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

    public function __construct(ApprovalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
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
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $purchaseOrder->no_po,
        ];

        $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
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
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';
        $stageLabel = $this->inferStageLabelForRejection($purchaseOrder, $oldStatus);

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

    protected function notifyApproversForStep(PurchaseOrder $purchaseOrder, string $requiredRole, string $step): void
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
            return;
        }

        $creator = $purchaseOrder->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';
        $stageLabel = $this->mapStepToStageLabel($step);

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $purchaseOrder->no_po,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            $this->sendStage1TemplateMessage($phone, $variables);
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
