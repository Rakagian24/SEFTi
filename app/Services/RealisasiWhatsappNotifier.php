<?php

namespace App\Services;

use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class RealisasiWhatsappNotifier
{
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
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $realisasi->no_realisasi,
        ];

        $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
    }

    public function notifyCreatorOnRejected(Realisasi $realisasi, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $realisasi->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus);

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
        $departmentId = $realisasi->department_id;

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

        $creator = $realisasi->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Realisasi';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $realisasi->no_realisasi,
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
