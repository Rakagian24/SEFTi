<?php

namespace App\Services;

use App\Models\PoAnggaran;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class PoAnggaranWhatsappNotifier
{
    protected ApprovalWorkflowService $workflowService;

    public function __construct(ApprovalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function notifyFirstApproverOnCreated(PoAnggaran $poAnggaran): void
    {
        if ($poAnggaran->status === 'Draft') {
            return;
        }

        $progress = $this->workflowService->getApprovalProgressForPoAnggaran($poAnggaran);
        if (empty($progress)) {
            return;
        }

        $currentStep = collect($progress)->firstWhere('status', 'current');
        if (!$currentStep) {
            return;
        }

        $requiredRole = $currentStep['role'] ?? null;
        $step = $currentStep['step'] ?? null;
        if (!$requiredRole || !$step) {
            return;
        }

        $this->notifyApproversForStep($poAnggaran, $requiredRole, (string) $step);
    }

    public function notifyNextApproverOnStatusChange(PoAnggaran $poAnggaran, string $oldStatus, string $newStatus): void
    {
        if (!in_array($newStatus, ['Verified', 'Validated'], true)) {
            return;
        }

        $progress = $this->workflowService->getApprovalProgressForPoAnggaran($poAnggaran);
        if (empty($progress)) {
            return;
        }

        $currentStep = collect($progress)->firstWhere('status', 'current');
        if (!$currentStep) {
            return;
        }

        $requiredRole = $currentStep['role'] ?? null;
        $step = $currentStep['step'] ?? null;
        if (!$requiredRole || !$step) {
            return;
        }

        $this->notifyApproversForStep($poAnggaran, $requiredRole, (string) $step);
    }

    public function notifyCreatorOnApproved(PoAnggaran $poAnggaran, User $actor): void
    {
        $creator = $poAnggaran->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $poAnggaran->no_po_anggaran,
        ];

        $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
    }

    public function notifyCreatorOnRejected(PoAnggaran $poAnggaran, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $poAnggaran->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus);

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $poAnggaran->no_po_anggaran,
            '4' => $stageLabel,
            '5' => $actor->name ?? '-',
            '6' => $actor->role->name ?? '-',
            '7' => $reason,
        ];

        $this->sendRejectedTemplateMessage((string) $creator->phone, $variables);
    }

    protected function notifyApproversForStep(PoAnggaran $poAnggaran, string $requiredRole, string $step): void
    {
        $departmentId = $poAnggaran->department_id;

        $targets = User::query()
            ->whereHas('role', function ($q) use ($requiredRole) {
                $q->where('name', $requiredRole);
            })
            ->when($departmentId, function ($q) use ($departmentId, $requiredRole) {
                // For non-Direksi roles, limit by document's department
                if ($requiredRole !== 'Direksi') {
                    $q->where(function ($inner) use ($departmentId) {
                        $inner->whereHas('department', function ($d) use ($departmentId) {
                            $d->where('departments.id', $departmentId);
                        })
                        ->orWhereHas('departments', function ($d) use ($departmentId) {
                            $d->where('departments.id', $departmentId);
                        });
                    });
                }
            })
            ->when($requiredRole === 'Direksi', function ($q) {
                // Direksi approvals should go to Direksi Finance
                $q->whereHas('departments', function ($d) {
                    $d->where('name', 'Finance');
                });
            })
            ->whereNotNull('phone')
            ->get();

        if ($targets->isEmpty()) {
            return;
        }

        $creator = $poAnggaran->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';
        $stageLabel = $this->mapStepToStageLabel($step);

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $poAnggaran->no_po_anggaran,
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

    protected function inferStageLabelForRejection(string $oldStatus): string
    {
        if ($oldStatus === 'In Progress') {
            return $this->mapStepToStageLabel('verified');
        }

        if ($oldStatus === 'Verified') {
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
            Log::info('WA POA - sending stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA POA - failed to send stage1 WhatsApp message', [
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
            Log::info('WA POA - sending rejected WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA POA - failed to send rejected WhatsApp message', [
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
            Log::info('WA POA - sending approved WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA POA - failed to send approved WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
