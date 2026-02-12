<?php

namespace App\Services;

use App\Models\Bpb;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class BpbWhatsappNotifier
{
    protected ApprovalEmailNotifier $emailNotifier;

    public function __construct(ApprovalEmailNotifier $emailNotifier)
    {
        $this->emailNotifier = $emailNotifier;
    }
    public function notifyFirstApproverOnCreated(Bpb $bpb): void
    {
        if ($bpb->status === 'Draft' || !$bpb->creator) {
            return;
        }

        if ($bpb->status === 'Approved') {
            $this->notifyCreatorOnApproved($bpb, $bpb->creator);
            return;
        }

        $requiredRole = $this->resolveApproverRole($bpb);
        if (!$requiredRole) {
            return;
        }

        $this->notifyApproversForRole($bpb, $requiredRole, 'Approval');
    }

    public function notifyCreatorOnApproved(Bpb $bpb, User $actor): void
    {
        $creator = $bpb->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $bpb->no_bpb,
            ];

            $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal BPB detail page)
        $detailUrl = route('bpb.detail', $bpb);
        $this->emailNotifier->notifyCreatorApproved($creator, $documentTypeLabel, (string) $bpb->no_bpb, $detailUrl);
    }

    public function notifyCreatorOnRejected(Bpb $bpb, User $actor, string $reason): void
    {
        $creator = $bpb->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';
        $stageLabel = 'Approval';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $bpb->no_bpb,
                '4' => $stageLabel,
                '5' => $actor->name ?? '-',
                '6' => $actor->role->name ?? '-',
                '7' => $reason,
            ];

            $this->sendRejectedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal BPB detail page)
        $detailUrl = route('bpb.detail', $bpb);
        $this->emailNotifier->notifyCreatorRejected(
            $creator,
            $documentTypeLabel,
            (string) $bpb->no_bpb,
            $stageLabel,
            $actor->name ?? null,
            $actor->role->name ?? null,
            $reason,
            $detailUrl
        );
    }

    protected function resolveApproverRole(Bpb $bpb): ?string
    {
        $creatorRole = $bpb->creator?->role?->name ?? null;
        if (!$creatorRole) {
            return null;
        }

        // Mirror ApprovalWorkflowService::getWorkflowForBpb
        if ($creatorRole === 'Staff Toko') {
            return 'Kepala Toko';
        }

        if ($creatorRole === 'Staff Akunting & Finance') {
            return 'Kabag';
        }

        return null;
    }

    protected function notifyApproversForRole(Bpb $bpb, string $requiredRole, string $stageLabel): void
    {
        Log::info('WA BPB - notifyApproversForRole called', [
            'bpb_id' => $bpb->id,
            'bpb_status' => $bpb->status,
            'creator_role' => $bpb->creator?->role?->name,
            'creator_dept' => $bpb->creator?->department?->name,
            'bpb_dept_id' => $bpb->department_id,
            'required_role' => $requiredRole,
            'stage_label' => $stageLabel
        ]);

        $departmentId = $bpb->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $bpb->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = \App\Models\Department::where('name', 'Finance')->value('id');
            Log::info('WA BPB - Routing to Finance department', [
                'creator_role' => $creatorRole,
                'required_role' => $requiredRole,
                'finance_dept_id' => $financeDeptId,
                'original_dept_id' => $departmentId
            ]);
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        // Special case: Kabag should receive notifications only for BPBs created by Staff Akunting & Finance
        if ($requiredRole === 'Kabag' && $bpb->creator?->role?->name !== 'Staff Akunting & Finance') {
            Log::info('WA BPB - Skipping Kabag notification - not created by Staff Akunting & Finance', [
                'creator_role' => $bpb->creator?->role?->name
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
            Log::info('WA BPB - No targets found', [
                'required_role' => $requiredRole,
                'department_id' => $departmentId,
                'creator_role' => $creatorRole
            ]);
            return;
        }

        Log::info('WA BPB - Targets found', [
            'required_role' => $requiredRole,
            'department_id' => $departmentId,
            'targets_count' => $targets->count(),
            'target_names' => $targets->pluck('name')->all()
        ]);

        $creator = $bpb->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $bpb->no_bpb,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            // WhatsApp if phone exists
            if ($phone !== '') {
                $this->sendStage1TemplateMessage($phone, $variables);
            }

            // Email to approver
            $detailUrl = route('approval.bpbs.detail', $bpb);
            $this->emailNotifier->notifyApprover(
                $user,
                $documentTypeLabel,
                (string) $bpb->no_bpb,
                $stageLabel,
                $creatorName,
                $creatorRole,
                $detailUrl
            );
        }
    }

    protected function resolveApproverTargets(Bpb $bpb, string $requiredRole)
    {
        $departmentId = $bpb->department_id;

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
     * Bulk summary notification to BPB approvers for bulk approve actions.
     *
     * @param iterable<Bpb> $bpbs
     */
    public function notifyBulkNextApproverSummary(iterable $bpbs): void
    {
        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';

        $counters = [];

        foreach ($bpbs as $bpb) {
            if (!$bpb instanceof Bpb) {
                continue;
            }

            if ($bpb->status === 'Draft') {
                continue;
            }

            $requiredRole = $this->resolveApproverRole($bpb);
            if (!$requiredRole) {
                continue;
            }

            $targets = $this->resolveApproverTargets($bpb, $requiredRole);
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
            $listUrl = route('approval.bpbs');
            $this->emailNotifier->notifyBulkSummary(
                $user,
                $documentTypeLabel,
                $count,
                'menunggu tindakan Anda',
                $listUrl
            );
        }
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
            Log::info('WA BPB - sending stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA BPB - failed to send stage1 WhatsApp message', [
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
            Log::info('WA BPB - sending bulk stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA BPB - failed to send bulk stage1 WhatsApp message', [
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
            Log::info('WA BPB - sending rejected WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA BPB - failed to send rejected WhatsApp message', [
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
            Log::info('WA BPB - sending approved WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA BPB - failed to send approved WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
