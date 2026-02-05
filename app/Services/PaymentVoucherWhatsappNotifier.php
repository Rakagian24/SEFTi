<?php

namespace App\Services;

use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class PaymentVoucherWhatsappNotifier
{
    public function notifyFirstApproverOnCreated(PaymentVoucher $pv): void
    {
        if ($pv->status === 'Draft' || !$pv->creator) {
            return;
        }

        // If already Approved (auto-approval flows), notify creator instead of approver.
        if ($pv->status === 'Approved') {
            $this->notifyCreatorOnApproved($pv, $pv->creator);
            return;
        }

        [$nextRole, $stage] = $this->resolveNextApproverRoleAndStage($pv->status, $pv);
        if (!$nextRole || !$stage) {
            return;
        }

        $this->notifyApproversForRole($pv, $nextRole, $stage);
    }

    public function notifyNextApproverOnStatusChange(PaymentVoucher $pv, string $oldStatus, string $newStatus): void
    {
        if (!in_array($newStatus, ['Verified', 'Validated'], true)) {
            return;
        }

        [$nextRole, $stage] = $this->resolveNextApproverRoleAndStage($newStatus, $pv);
        if (!$nextRole || !$stage) {
            return;
        }

        $this->notifyApproversForRole($pv, $nextRole, $stage);
    }

    public function notifyCreatorOnApproved(PaymentVoucher $pv, User $actor): void
    {
        $creator = $pv->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $pv->no_pv,
        ];

        $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
    }

    public function notifyCreatorOnRejected(PaymentVoucher $pv, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $pv->creator;
        if (!$creator || !$creator->phone) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus, $pv->tipe_pv);

        $variables = [
            '1' => $systemName,
            '2' => $documentTypeLabel,
            '3' => (string) $pv->no_pv,
            '4' => $stageLabel,
            '5' => $actor->name ?? '-',
            '6' => $actor->role->name ?? '-',
            '7' => $reason,
        ];

        $this->sendRejectedTemplateMessage((string) $creator->phone, $variables);
    }

    protected function resolveNextApproverRoleAndStage(string $currentStatus, PaymentVoucher $pv): array
    {
        $tipe = $pv->tipe_pv;

        // Pajak: Staff AF -> Kabag(verify) -> Kadiv Finance(validate) -> Direksi Finance(approve)
        if ($tipe === 'Pajak') {
            if ($currentStatus === 'In Progress') {
                return ['Kabag', 'Verifikasi'];
            }
            if ($currentStatus === 'Verified') {
                return ['Kadiv', 'Validasi'];
            }
            if ($currentStatus === 'Validated') {
                return ['Direksi', 'Approval'];
            }
        } else {
            // Non-Pajak: Staff AF -> Kabag(verify) -> Direksi Finance(approve)
            if ($currentStatus === 'In Progress') {
                return ['Kabag', 'Verifikasi'];
            }
            if ($currentStatus === 'Verified') {
                return ['Direksi', 'Approval'];
            }
        }

        return [null, null];
    }

    protected function notifyApproversForRole(PaymentVoucher $pv, string $requiredRole, string $stageLabel): void
    {
        $departmentId = $pv->department_id;

        $targets = User::query()
            ->whereHas('role', function ($q) use ($requiredRole) {
                $q->where('name', $requiredRole);
            })
            ->when($requiredRole === 'Direksi', function ($q) {
                // Limit to Direksi Finance to align with business rules
                $q->whereHas('departments', function ($d) {
                    $d->where('name', 'Finance');
                });
            })
            ->when($departmentId, function ($q) use ($departmentId, $requiredRole) {
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
            ->whereNotNull('phone')
            ->get();

        if ($targets->isEmpty()) {
            return;
        }

        $creator = $pv->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;
            if ($phone === '') {
                continue;
            }

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $pv->no_pv,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            $this->sendStage1TemplateMessage($phone, $variables);
        }
    }

    protected function inferStageLabelForRejection(string $oldStatus, ?string $tipePv): string
    {
        if ($oldStatus === 'In Progress') {
            return 'Verifikasi';
        }

        if ($oldStatus === 'Verified') {
            // For Pajak, this could be before Validasi; otherwise before Approval
            return $tipePv === 'Pajak' ? 'Validasi' : 'Approval';
        }

        if ($oldStatus === 'Validated') {
            return 'Approval';
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
            Log::info('WA PV - sending stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PV - failed to send stage1 WhatsApp message', [
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
            Log::info('WA PV - sending rejected WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PV - failed to send rejected WhatsApp message', [
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
            Log::info('WA PV - sending approved WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PV - failed to send approved WhatsApp message', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
