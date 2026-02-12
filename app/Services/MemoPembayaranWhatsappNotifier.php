<?php

namespace App\Services;

use App\Models\MemoPembayaran;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class MemoPembayaranWhatsappNotifier
{
    protected ApprovalEmailNotifier $emailNotifier;

    public function __construct(ApprovalEmailNotifier $emailNotifier)
    {
        $this->emailNotifier = $emailNotifier;
    }
    public function notifyFirstApproverOnCreated(MemoPembayaran $memo): void
    {
        if ($memo->status === 'Draft' || !$memo->creator) {
            return;
        }

        if ($memo->status === 'Approved') {
            $this->notifyCreatorOnApproved($memo, $memo->creator);
            return;
        }

        [$nextStep, $nextRole] = $this->resolveNextStepAndRole($memo->status, $memo);
        if (!$nextStep || !$nextRole) {
            return;
        }

        $stageLabel = $this->mapStepToStageLabel($nextStep);
        $this->notifyApproversForRole($memo, $nextRole, $stageLabel);
    }

    public function notifyNextApproverOnStatusChange(MemoPembayaran $memo, string $oldStatus, string $newStatus): void
    {
        if (!in_array($newStatus, ['Verified', 'Validated'], true)) {
            return;
        }

        [$nextStep, $nextRole] = $this->resolveNextStepAndRole($newStatus, $memo);
        if (!$nextStep || !$nextRole) {
            return;
        }

        $stageLabel = $this->mapStepToStageLabel($nextStep);
        $this->notifyApproversForRole($memo, $nextRole, $stageLabel);
    }

    public function notifyCreatorOnApproved(MemoPembayaran $memo, User $actor): void
    {
        $creator = $memo->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $memo->no_mb,
            ];

            $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Memo Pembayaran detail page)
        $detailUrl = route('memo-pembayaran.show', $memo);
        $this->emailNotifier->notifyCreatorApproved($creator, $documentTypeLabel, (string) $memo->no_mb, $detailUrl);
    }

    public function notifyCreatorOnRejected(MemoPembayaran $memo, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $memo->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus);

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $memo->no_mb,
                '4' => $stageLabel,
                '5' => $actor->name ?? '-',
                '6' => $actor->role->name ?? '-',
                '7' => $reason,
            ];

            $this->sendRejectedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Memo Pembayaran detail page)
        $detailUrl = route('memo-pembayaran.show', $memo);
        $this->emailNotifier->notifyCreatorRejected(
            $creator,
            $documentTypeLabel,
            (string) $memo->no_mb,
            $stageLabel,
            $actor->name ?? null,
            $actor->role->name ?? null,
            $reason,
            $detailUrl
        );
    }

    protected function resolveNextStepAndRole(string $currentStatus, MemoPembayaran $memo): array
    {
        /** @var \App\Services\ApprovalWorkflowService $workflow */
        $workflow = app(\App\Services\ApprovalWorkflowService::class);
        $progress = $workflow->getApprovalProgressForMemoPembayaran($memo);

        if (empty($progress)) {
            return [null, null];
        }

        foreach ($progress as $stepInfo) {
            if (($stepInfo['status'] ?? null) === 'current') {
                return [$stepInfo['step'] ?? null, $stepInfo['role'] ?? null];
            }
        }

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

    protected function notifyApproversForRole(MemoPembayaran $memo, string $requiredRole, string $stageLabel): void
    {
        Log::info('WA Memo - notifyApproversForRole called', [
            'memo_id' => $memo->id,
            'memo_status' => $memo->status,
            'creator_role' => $memo->creator?->role?->name,
            'creator_dept' => $memo->creator?->department?->name,
            'memo_dept_id' => $memo->department_id,
            'required_role' => $requiredRole,
            'stage_label' => $stageLabel
        ]);

        $targets = $this->resolveApproverTargets($memo, $requiredRole);

        if ($targets->isEmpty()) {
            Log::info('WA Memo - No targets found', [
                'required_role' => $requiredRole,
                'memo_dept_id' => $memo->department_id,
                'creator_role' => $memo->creator?->role?->name,
            ]);
            return;
        }

        Log::info('WA Memo - Targets found', [
            'required_role' => $requiredRole,
            'memo_dept_id' => $memo->department_id,
            'targets_count' => $targets->count(),
            'target_names' => $targets->pluck('name')->all(),
        ]);

        $creator = $memo->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $memo->no_mb,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            // WhatsApp if phone exists
            if ($phone !== '') {
                $this->sendStage1TemplateMessage($phone, $variables);
            }

            // Email to approver
            $detailUrl = route('approval.memo-pembayarans.detail', $memo);
            $this->emailNotifier->notifyApprover(
                $user,
                $documentTypeLabel,
                (string) $memo->no_mb,
                $stageLabel,
                $creatorName,
                $creatorRole,
                $detailUrl
            );
        }
    }

    /**
     * Resolve approver targets for a given Memo Pembayaran and workflow role.
     * Encapsulates all routing rules so it can be reused for single and bulk flows.
     */
    protected function resolveApproverTargets(MemoPembayaran $memo, string $requiredRole)
    {
        $departmentId = $memo->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $memo->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = \App\Models\Department::where('name', 'Finance')->value('id');
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        // Special case: Kabag should receive notifications only for Memos created by Staff Akunting & Finance
        if ($requiredRole === 'Kabag' && $creatorRole !== 'Staff Akunting & Finance') {
            return collect();
        }

        if ($requiredRole === 'Kabag') {
            return User::query()
                ->whereHas('role', function ($q) use ($requiredRole) {
                    $q->where('name', $requiredRole);
                })
                ->whereNotNull('phone')
                ->get();
        }

        // For other roles, use department filtering
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
     * Bulk summary notification to next approvers for a collection of Memo Pembayaran.
     * Intended for bulk verify/validate/approve actions from list views.
     *
     * @param iterable<MemoPembayaran> $memos
     */
    public function notifyBulkNextApproverSummary(iterable $memos): void
    {
        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';

        $counters = [];

        foreach ($memos as $memo) {
            if (!$memo instanceof MemoPembayaran) {
                continue;
            }

            if ($memo->status === 'Draft') {
                continue;
            }

            [$nextStep, $nextRole] = $this->resolveNextStepAndRole($memo->status, $memo);
            if (!$nextStep || !$nextRole) {
                continue;
            }

            $targets = $this->resolveApproverTargets($memo, $nextRole);
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
            $listUrl = route('approval.memo-pembayarans');
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
            Log::info('WA Memo - sending stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Memo - failed to send stage1 WhatsApp message', [
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
            Log::info('WA Memo - sending bulk stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Memo - failed to send bulk stage1 WhatsApp message', [
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
            Log::info('WA Memo - sending rejected WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Memo - failed to send rejected WhatsApp message', [
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
            Log::info('WA Memo - sending approved WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA Memo - failed to send approved WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
