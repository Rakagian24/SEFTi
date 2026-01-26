<?php

namespace App\Services;

use App\Models\User;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class PurchaseOrderWhatsappNotifier
{
    protected ApprovalWorkflowService $workflowService;

    public function __construct(ApprovalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function notifyNextApproverOnStatusChange(PurchaseOrder $purchaseOrder, string $oldStatus, string $newStatus): void
    {
        // Approval-stage notifications will use a different template; skip for now
        if (!in_array($newStatus, ['Verified', 'Validated'], true)) {
            return;
        }

        $nextStep = $this->workflowService->getNextApprovalStep($purchaseOrder);
        if (!$nextStep) {
            return;
        }

        $requiredRole = $this->workflowService->getRequiredRoleForStep($purchaseOrder, $nextStep);
        if (!$requiredRole) {
            Log::warning('WA PO Create - no requiredRole resolved for step', [
                'po_id' => $purchaseOrder->id,
                'next_step' => $nextStep,
            ]);
            return;
        }

        $departmentId = $purchaseOrder->department_id;

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

        if ($targets->isEmpty()) {
            Log::warning('WA PO Create - no target users found for first approver', [
                'po_id' => $purchaseOrder->id,
                'required_role' => $requiredRole,
                'department_id' => $departmentId,
            ]);
            return;
        }

        $creator = $purchaseOrder->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';
        $stageLabel = $this->mapStepToStageLabel($nextStep);

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,                          // Notifikasi dari sistem {{1}}
                '2' => $documentTypeLabel,                   // Terdapat pengajuan {{2}}
                '3' => (string) $purchaseOrder->no_po,       // dengan nomor {{3}}
                '4' => $creatorName,                         // yang dibuat oleh {{4}}
                '5' => $creatorRole,                         // - {{5}}
                '6' => $stageLabel,                          // tahap {{6}}
            ];

            $this->sendTemplateMessage($phone, $variables);
        }
    }

    public function notifyFirstApproverOnCreated(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status === 'Draft') {
            return;
        }

        Log::info('WA PO Create - notifyFirstApproverOnCreated called', [
            'po_id' => $purchaseOrder->id,
            'status' => $purchaseOrder->status,
            'department_id' => $purchaseOrder->department_id,
        ]);

        $nextStep = $this->workflowService->getNextApprovalStep($purchaseOrder);
        if (!$nextStep) {
            Log::warning('WA PO Create - no nextStep resolved from workflow', [
                'po_id' => $purchaseOrder->id,
            ]);
            return;
        }

        $requiredRole = $this->workflowService->getRequiredRoleForStep($purchaseOrder, $nextStep);
        if (!$requiredRole) {
            return;
        }

        $departmentId = $purchaseOrder->department_id;

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

        if ($targets->isEmpty()) {
            return;
        }

        $creator = $purchaseOrder->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Purchase Order';
        $stageLabel = $this->mapStepToStageLabel($nextStep);

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

            $this->sendTemplateMessageForCreate($phone, $variables);
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

    protected function sendTemplateMessage(string $phone, array $variables): void
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
            Log::info('WA PO Create - sending WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PO Create - failed to send WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendTemplateMessageForCreate(string $phone, array $variables): void
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
            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
        }
    }
}

