<?php

namespace App\Services;

use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class PaymentVoucherWhatsappNotifier
{
    protected ApprovalEmailNotifier $emailNotifier;

    public function __construct(ApprovalEmailNotifier $emailNotifier)
    {
        $this->emailNotifier = $emailNotifier;
    }
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
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $pv->no_pv,
            ];

            $this->sendApprovedTemplateMessage((string) $creator->phone, $variables);
        }

        // Email (creator should see normal Payment Voucher detail page)
        $detailUrl = route('payment-voucher.show', $pv->id);
        $this->emailNotifier->notifyCreatorApproved($creator, $documentTypeLabel, (string) $pv->no_pv, $detailUrl);
    }

    public function notifyCreatorOnRejected(PaymentVoucher $pv, User $actor, string $reason, string $oldStatus): void
    {
        $creator = $pv->creator;
        if (!$creator) {
            return;
        }

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';
        $stageLabel = $this->inferStageLabelForRejection($oldStatus, $pv->tipe_pv);

        // WhatsApp (only if phone is available)
        if ($creator->phone) {
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

        // Email (creator should see normal Payment Voucher detail page)
        $detailUrl = route('payment-voucher.show', $pv->id);
        $this->emailNotifier->notifyCreatorRejected(
            $creator,
            $documentTypeLabel,
            (string) $pv->no_pv,
            $stageLabel,
            $actor->name ?? null,
            $actor->role->name ?? null,
            $reason,
            $detailUrl
        );
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
        Log::info('WA PV - notifyApproversForRole called', [
            'pv_id' => $pv->id,
            'pv_status' => $pv->status,
            'pv_type' => $pv->tipe_pv,
            'creator_role' => $pv->creator?->role?->name,
            'creator_dept' => $pv->creator?->department?->name,
            'pv_dept_id' => $pv->department_id,
            'required_role' => $requiredRole,
            'stage_label' => $stageLabel
        ]);

        $departmentId = $pv->department_id;

        // Special routing: for documents created by Staff Akunting & Finance,
        // approvals by Kadiv/Direksi should go to Finance management,
        // not arbitrary Kadiv/Direksi from other departments.
        $creatorRole = $pv->creator?->role?->name ?? null;
        if ($creatorRole === 'Staff Akunting & Finance' && in_array($requiredRole, ['Kadiv', 'Direksi'], true)) {
            $financeDeptId = \App\Models\Department::where('name', 'Finance')->value('id');
            Log::info('WA PV - Routing to Finance department', [
                'creator_role' => $creatorRole,
                'required_role' => $requiredRole,
                'finance_dept_id' => $financeDeptId,
                'original_dept_id' => $departmentId
            ]);
            if ($financeDeptId) {
                $departmentId = $financeDeptId;
            }
        }

        // Special case: Kabag should receive notifications only for PVs created by Staff Akunting & Finance
        if ($requiredRole === 'Kabag' && $pv->creator?->role?->name !== 'Staff Akunting & Finance') {
            Log::info('WA PV - Skipping Kabag notification - not created by Staff Akunting & Finance', [
                'creator_role' => $pv->creator?->role?->name
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
            Log::info('WA PV - No targets found', [
                'required_role' => $requiredRole,
                'department_id' => $departmentId,
                'creator_role' => $creatorRole
            ]);
            return;
        }

        Log::info('WA PV - Targets found', [
            'required_role' => $requiredRole,
            'department_id' => $departmentId,
            'targets_count' => $targets->count(),
            'target_names' => $targets->pluck('name')->all()
        ]);

        $creator = $pv->creator;
        $creatorName = $creator?->name ?? '-';
        $creatorRole = $creator?->role?->name ?? '-';

        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';

        foreach ($targets as $user) {
            $phone = (string) $user->phone;

            $variables = [
                '1' => $systemName,
                '2' => $documentTypeLabel,
                '3' => (string) $pv->no_pv,
                '4' => $creatorName,
                '5' => $creatorRole,
                '6' => $stageLabel,
            ];

            // WhatsApp if phone exists
            if ($phone !== '') {
                $this->sendStage1TemplateMessage($phone, $variables);
            }

            // Email to approver
            $detailUrl = route('approval.payment-vouchers.detail', $pv);
            $this->emailNotifier->notifyApprover(
                $user,
                $documentTypeLabel,
                (string) $pv->no_pv,
                $stageLabel,
                $creatorName,
                $creatorRole,
                $detailUrl
            );
        }
    }

    protected function resolveApproverTargets(PaymentVoucher $pv, string $requiredRole)
    {
        $departmentId = $pv->department_id;

        return User::query()
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
    }

    /**
     * Bulk summary notification to next approvers for a collection of Payment Vouchers.
     *
     * @param iterable<PaymentVoucher> $paymentVouchers
     */
    public function notifyBulkNextApproverSummary(iterable $paymentVouchers): void
    {
        $systemName = config('app.name', 'SEFTi');
        $documentTypeLabel = 'Payment Voucher';

        $counters = [];

        foreach ($paymentVouchers as $pv) {
            if (!$pv instanceof PaymentVoucher) {
                continue;
            }

            if ($pv->status === 'Draft') {
                continue;
            }

            [$nextRole, $stage] = $this->resolveNextApproverRoleAndStage($pv->status, $pv);
            if (!$nextRole || !$stage) {
                continue;
            }

            $targets = $this->resolveApproverTargets($pv, $nextRole);
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
            $listUrl = route('approval.payment-vouchers');
            $this->emailNotifier->notifyBulkSummary(
                $user,
                $documentTypeLabel,
                $count,
                'menunggu tindakan Anda',
                $listUrl
            );
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
            Log::info('WA PV - sending bulk stage1 WhatsApp message', [
                'to' => $to,
                'from' => $fromWhatsapp,
                'templateSid' => $templateSid,
                'variables' => $variables,
            ]);

            $client->messages->create($to, $payload);
        } catch (\Throwable $e) {
            Log::error('WA PV - failed to send bulk stage1 WhatsApp message', [
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
