<?php

namespace App\Services;

use App\Models\Bpb;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class BpbWhatsappNotifier
{
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
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $bpb->no_bpb,
        ];

        $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
    }

    public function notifyCreatorOnRejected(Bpb $bpb, User $actor, string $reason): void
    {
        $creator = $bpb->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';
        $stageLabel = 'Approval';

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
        $departmentId = $bpb->department_id;

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

        $creator = $bpb->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'BPB';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $bpb->no_bpb,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            $this->sendStage1TemplateMessage($phone, $variables);
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
