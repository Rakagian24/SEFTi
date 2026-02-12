<?php

namespace App\Services;

use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class RealisasiWhatsappNotifier
{
    protected ApprovalEmailNotifier $emailNotifier;

    public function __construct(ApprovalEmailNotifier $emailNotifier)
    {
        $this->emailNotifier = $emailNotifier;
    }
    public function notifyFirstApproverOnCreated(Realisasi $realisasi): void
    {
        if ($realisasi->status === 'Draft' || !$realisasi->creator) {
            return;
        }

        if ($realisasi->status === 'Approved') {
            $this->notifyCreatorOnApproved($realisasi, $realisasi->creator);
            return;
        }

        [$nextStep, $nextRole] = $this->resolveNextStepAndRole($realisasi->status, $realisasi);
        if (!$nextStep || !$nextRole) {
            return;
        }

        $stageLabel = $this->mapStepToStageLabel($nextStep);
        $this->notifyApproversForRole($realisasi, $nextRole, $stageLabel);
    }

    public function notifyNextApproverOnStatusChange(Realisasi $realisasi, string $oldStatus, string $newStatus): void
    {
        if (!in_array($newStatus, ['Verified', 'Validated'], true)) {
            return;
        }

        [$nextStep, $nextRole] = $this->resolveNextStepAndRole($newStatus, $realisasi);
        if (!$nextStep || !$nextRole) {
            return;
        }

        $stageLabel = $this->mapStepToStageLabel($nextStep);
        $this->notifyApproversForRole($realisasi, $nextRole, $stageLabel);
    }

    public function notifyCreatorOnApproved(Realisasi $realisasi, User $actor): void
    {
        $creator = $realisasi->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $realisasi->no_realisasi,
            ];

            $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Realisasi detail page)
        $detailUrl = route('realisasi.show', $realisasi);
        $this->emailNotifier->notifyCreatorApproved($creator, $documentTypeLabel, (string) $realisasi->no_realisasi, $detailUrl);
    }

    public function notifyCreatorOnRejected(Realisasi $realisasi, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $realisasi->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus);

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $realisasi->no_realisasi,
                '4' => $stageLabel,
                '5' => $actor->name ?? '-',
                '6' => $actor->role->name ?? '-',
                '7' => $reason,
            ];

            $this->sendRejectedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Realisasi detail page)
        $detailUrl = route('realisasi.show', $realisasi);
        $this->emailNotifier->notifyCreatorRejected(
            $creator,
            $documentTypeLabel,
            (string) $realisasi->no_realisasi,
            $stageLabel,
            $actor->name ?? null,
            $actor->role->name ?? null,
            $reason,
            $detailUrl
        );
    }

    protected function resolveNextStepAndRole(string $currentStatus, Realisasi $realisasi): array
    {
        // Mirror ApprovalWorkflowService::getApprovalProgressForRealisasi
        /** @var \App\Services\ApprovalWorkflowService $workflow */
        $workflow = app(\App\Services\ApprovalWorkflowService::class);
        $progress = $workflow->getApprovalProgressForRealisasi($realisasi);

        if (empty($progress)) {
            return [null, null];
        }

        foreach ($progress as $index => $stepInfo) {
            if (($stepInfo['status'] ?? null) === 'current') {
                // current step is where we are; next approver is at this step
                return [$stepInfo['step'] ?? null, $stepInfo['role'] ?? null];
            }
        }

        // If all completed but not yet Approved, no next step
        return [null, null];
    }

    protected function mapStepToStageLabel(string $step): string
    {
        return match ($step) {
            'verified' => 'Verifikasi',
            'validated' => 'Validasi',
            'approved' => 'Approval',
            default => 'Approval',
        };
    }

    protected function notifyApproversForRole(Realisasi $realisasi, string $requiredRole, string $stageLabel): void
    {
        Log::info('WA Realisasi - notifyApproversForRole called', [
            'realisasi_id' => $realisasi->id,
            'realisasi_status' => $realisasi->status,
            'creator_role' => $realisasi->creator?->role?->name,
            'creator_dept' => $realisasi->creator?->department?->name,
            'realisasi_dept_id' => $realisasi->department_id,
            'required_role' => $requiredRole,
            'stage_label' => $stageLabel
        ]);

        $departmentId = $realisasi->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $realisasi->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = \App\Models\Department::where('name', 'Finance')->value('id');
            Log::info('WA Realisasi - Routing to Finance department', [
                'creator_role' => $creatorRole,
                'required_role' => $requiredRole,
                'finance_dept_id' => $financeDeptId,
                'original_dept_id' => $departmentId
            ]);
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        // Special case: Kabag should receive notifications only for Realisasi created by Staff Akunting & Finance
        if ($requiredRole === 'Kabag' && $realisasi->creator?->role?->name !== 'Staff Akunting & Finance') {
            Log::info('WA Realisasi - Skipping Kabag notification - not created by Staff Akunting & Finance', [
                'creator_role' => $realisasi->creator?->role?->name
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
            Log::info('WA Realisasi - No targets found', [
                'required_role' => $requiredRole,
                'department_id' => $departmentId,
                'creator_role' => $creatorRole
            ]);
            return;
        }

        Log::info('WA Realisasi - Targets found', [
            'required_role' => $requiredRole,
            'department_id' => $departmentId,
            'targets_count' => $targets->count(),
            'target_names' => $targets->pluck('name')->all()
        ]);

        $creator = $realisasi->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $realisasi->no_realisasi,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            // WhatsApp if phone exists
            if ($phone !== '') {
                $this->sendStage1TemplateMessage($phone, $variables);
            }

            // Email to approver
            $detailUrl = route('approval.realisasi.detail', $realisasi);
            $this->emailNotifier->notifyApprover(
                $user,
                $documentTypeLabel,
                (string) $realisasi->no_realisasi,
                $stageLabel,
                $creatorName,
                $creatorRole,
                $detailUrl
            );
        }
    }

    /**
     * Resolve approver targets for a given Realisasi and workflow role,
     * respecting Finance-only routing for Direksi.
     */
    protected function resolveApproverTargets(Realisasi $realisasi, string $requiredRole)
    {
        $departmentId = $realisasi->department_id;

        return User::query()
            ->whereHas('role', function ($q) use ($requiredRole) {
                $q->where('name', $requiredRole);
            })
            ->when($departmentId, function ($q) use ($departmentId, $requiredRole) {
                if (!in_array($requiredRole, ['Direksi'], true)) {
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
                $q->whereHas('departments', function ($d) {
                    $d->where('name', 'Finance');
                });
            })
            ->whereNotNull('phone')
            ->get();
    }

    /**
     * Bulk summary notification to next approvers for a collection of Realisasi.
     * Intended for bulk verify/validate/approve actions from list views.
     *
     * @param iterable<Realisasi> $realisasis
     */
    public function notifyBulkNextApproverSummary(iterable $realisasis): void
    {
        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';

        $counters = [];

        foreach ($realisasis as $realisasi) {
            if (!$realisasi instanceof Realisasi) {
                continue;
            }

            if ($realisasi->status === 'Draft') {
                continue;
            }

            [$nextStep, $nextRole] = $this->resolveNextStepAndRole($realisasi->status, $realisasi);
            if (!$nextStep || !$nextRole) {
                continue;
            }

            $targets = $this->resolveApproverTargets($realisasi, $nextRole);
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
            $listUrl = route('approval.realisasi');
            $this->emailNotifier->notifyBulkSummary(
                $user,
                $documentTypeLabel,
                $count,
                'menunggu tindakan Anda',
                $listUrl
            );
        }
    }

    protected function inferStageLabelForRejection(string $oldStatus): string
    {
        return match ($oldStatus) {
            'In Progress' => 'Verifikasi',
            'Verified' => 'Validasi',
            'Validated' => 'Approval',
            default => 'Approval',
        };
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
            Log::info('WA Realisasi - sending stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Realisasi - failed to send stage1 WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send bulk summary notification using TWILIO_TEMPLATE_BULK_SID.
     * Variables:
     *  1 => system name
     *  2 => document count
     *  3 => document type label
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
            Log::info('WA Realisasi - sending bulk stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Realisasi - failed to send bulk stage1 WhatsApp message', [
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
            Log::info('WA Realisasi - sending rejected WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Realisasi - failed to send rejected WhatsApp message', [
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
            Log::info('WA Realisasi - sending approved WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Realisasi - failed to send approved WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
