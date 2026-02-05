<?php

namespace App\Services;

use App\Models\MemoPembayaran;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class MemoPembayaranWhatsappNotifier
{
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
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $memo->no_mb,
        ];

        $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
    }

    public function notifyCreatorOnRejected(MemoPembayaran $memo, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $memo->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus);

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
        $departmentId = $memo->department_id;

        $targets = User::query()
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

        if ($targets->isEmpty()) {
            return;
        }

        $creator = $memo->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Memo Pembayaran';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $memo->no_mb,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            $this->sendStage1TemplateMessage($phone, $variables);
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
