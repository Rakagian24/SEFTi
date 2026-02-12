<?php

namespace App\Services;

use App\Models\PoAnggaran;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class PoAnggaranWhatsappNotifier
{
    protected ApprovalWorkflowService $workflowService;
    protected ApprovalEmailNotifier $emailNotifier;

    public function __construct(ApprovalWorkflowService $workflowService, ApprovalEmailNotifier $emailNotifier)
    {
        $this->workflowService = $workflowService;
        $this->emailNotifier = $emailNotifier;
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
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $poAnggaran->no_po_anggaran,
            ];

            $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal PO Anggaran detail page)
        $detailUrl = route('po-anggaran.show', $poAnggaran);
        $this->emailNotifier->notifyCreatorApproved($creator, $documentTypeLabel, (string) $poAnggaran->no_po_anggaran, $detailUrl);
    }

    public function notifyCreatorOnRejected(PoAnggaran $poAnggaran, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $poAnggaran->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus);

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
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

        // Email (creator should see normal PO Anggaran detail page)
        $detailUrl = route('po-anggaran.show', $poAnggaran);
        $this->emailNotifier->notifyCreatorRejected(
            $creator,
            $documentTypeLabel,
            (string) $poAnggaran->no_po_anggaran,
            $stageLabel,
            $actor->name ?? null,
            $actor->role->name ?? null,
            $reason,
            $detailUrl
        );
    }

    protected function notifyApproversForStep(PoAnggaran $poAnggaran, string $requiredRole, string $step): void
    {
        Log::info('WA PO Anggaran - notifyApproversForStep called', [
            'po_anggaran_id' => $poAnggaran->id,
            'po_anggaran_status' => $poAnggaran->status,
            'creator_role' => $poAnggaran->creator?->role?->name,
            'creator_dept' => $poAnggaran->creator?->department?->name,
            'po_anggaran_dept_id' => $poAnggaran->department_id,
            'required_role' => $requiredRole,
            'step' => $step
        ]);

        $departmentId = $poAnggaran->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $poAnggaran->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = \App\Models\Department::where('name', 'Finance')->value('id');
            Log::info('WA PO Anggaran - Routing to Finance department', [
                'creator_role' => $creatorRole,
                'required_role' => $requiredRole,
                'finance_dept_id' => $financeDeptId,
                'original_dept_id' => $departmentId
            ]);
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        // Special case: Kabag should receive notifications only for PO Anggarans created by Staff Akunting & Finance
        if ($requiredRole === 'Kabag' && $poAnggaran->creator?->role?->name !== 'Staff Akunting & Finance') {
            Log::info('WA PO Anggaran - Skipping Kabag notification - not created by Staff Akunting & Finance', [
                'creator_role' => $poAnggaran->creator?->role?->name
            ]);
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
            Log::info('WA PO Anggaran - No targets found', [
                'required_role' => $requiredRole,
                'department_id' => $departmentId,
                'creator_role' => $creatorRole
            ]);
            return;
        }

        Log::info('WA PO Anggaran - Targets found', [
            'required_role' => $requiredRole,
            'department_id' => $departmentId,
            'targets_count' => $targets->count(),
            'target_names' => $targets->pluck('name')->all()
        ]);

        $creator = $poAnggaran->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';
        $stageLabel = $this->mapStepToStageLabel($step);

        foreach ($targets as $user) {
            $phone = (string) $user->phone;

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $poAnggaran->no_po_anggaran,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            // WhatsApp if phone exists
            if ($phone !== '') {
                $this->sendStage1TemplateMessage($phone, $variables);
            }

            // Email to approver
            $detailUrl = route('approval.po-anggaran.detail', $poAnggaran);
            $this->emailNotifier->notifyApprover(
                $user,
                $documentTypeLabel,
                (string) $poAnggaran->no_po_anggaran,
                $stageLabel,
                $creatorName,
                $creatorRole,
                $detailUrl
            );
        }
    }

    protected function resolveApproverTargets(PoAnggaran $poAnggaran, string $requiredRole)
    {
        $departmentId = $poAnggaran->department_id;

        return User::query()
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
    }

    /**
     * Bulk summary notification to next approvers for a collection of PO Anggaran.
     *
     * @param iterable<PoAnggaran> $poAnggarans
     */
    public function notifyBulkNextApproverSummary(iterable $poAnggarans): void
    {
        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'PO Anggaran';

        $counters = [];

        foreach ($poAnggarans as $poAnggaran) {
            if (!$poAnggaran instanceof PoAnggaran) {
                continue;
            }

            if ($poAnggaran->status === 'Draft') {
                continue;
            }

            $progress = $this->workflowService->getApprovalProgressForPoAnggaran($poAnggaran);
            if (empty($progress)) {
                continue;
            }

            $currentStep = collect($progress)->firstWhere('status', 'current');
            if (!$currentStep) {
                continue;
            }

            $requiredRole = $currentStep['role'] ?? null;
            if (!$requiredRole) {
                continue;
            }

            $targets = $this->resolveApproverTargets($poAnggaran, (string) $requiredRole);
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
            $listUrl = route('approval.po-anggaran');
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
            Log::info('WA POA - sending bulk stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA POA - failed to send bulk stage1 WhatsApp message', [
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
