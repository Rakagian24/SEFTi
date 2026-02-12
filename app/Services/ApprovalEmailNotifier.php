<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use SendGrid;
use SendGrid\Mail\Mail as SendGridMail;
use App\Models\User;

class ApprovalEmailNotifier
{
    public function sendToUser(?User $user, string $subject, string $textContent, ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        if (!$user || !$user->email) {
            return;
        }

        $apiKey = (string) env('SENDGRID_API_KEY');
        $fromEmail = (string) env('SENDGRID_FROM_EMAIL');
        $fromName = (string) env('SENDGRID_FROM_NAME', config('app.name', 'SEFTi'));

        if ($apiKey === '' || $fromEmail === '') {
            return;
        }

        $email = new SendGridMail();
        $email->setFrom($fromEmail, $fromName);
        $email->setSubject($subject);
        $email->addTo($user->email, $user->name ?? '');

        // Plain text body
        $plain = $textContent;
        if ($actionUrl) {
            $plain .= "\n\n" . ($actionLabel ?: 'Buka di SEFTi') . ': ' . $actionUrl;
        }
        $email->addContent('text/plain', $plain);

        // Simple HTML body with a button-like link if URL is provided
        $html = nl2br(e($textContent));
        if ($actionUrl) {
            $label = $actionLabel ?: 'Buka di SEFTi';
            $html .= '<br><br>' .
                '<a href="' . e($actionUrl) . '" '
                . 'style="display:inline-block;padding:10px 18px;background-color:#2563eb;color:#ffffff;text-decoration:none;border-radius:4px;"
                target="_blank" rel="noopener noreferrer">'
                . e($label) . '</a>';
        }
        $email->addContent('text/html', $html);

        try {
            $sendgrid = new SendGrid($apiKey);
            $response = $sendgrid->send($email);

            Log::info('ApprovalEmailNotifier - email sent', [
                'to' => $user->email,
                'status_code' => $response->statusCode(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalEmailNotifier - failed to send email', [
                'to' => $user->email ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifyApprover(
        User $approver,
        string $documentType,
        string $documentNumber,
        string $stageLabel,
        ?string $creatorName,
        ?string $creatorRole,
        ?string $detailUrl = null
    ): void
    {
        $systemName = config('app.name', 'SEFTi');

        $subject = "[{$systemName}] Permintaan Approval {$documentType} {$documentNumber}";

        $lines = [
            "Anda memiliki permintaan approval {$documentType}.",
            "Nomor: {$documentNumber}",
            "Tahap: {$stageLabel}",
        ];

        if ($creatorName) {
            $lines[] = "Dibuat oleh: {$creatorName}" . ($creatorRole ? " ({$creatorRole})" : '');
        }

        $lines[] = '';
        $lines[] = 'Silakan masuk ke sistem SEFTi untuk melakukan tindakan.';

        $content = implode("\n", $lines);

        $this->sendToUser($approver, $subject, $content, $detailUrl, 'Lihat Detail Dokumen');
    }

    public function notifyCreatorApproved(User $creator, string $documentType, string $documentNumber, ?string $detailUrl = null): void
    {
        $systemName = config('app.name', 'SEFTi');

        $subject = "[{$systemName}] {$documentType} {$documentNumber} telah disetujui";

        $content = implode("\n", [
            "Dokumen {$documentType} Anda telah DISETUJUI.",
            "Nomor: {$documentNumber}",
            '',
            'Silakan masuk ke sistem SEFTi untuk melihat detail.',
        ]);

        $this->sendToUser($creator, $subject, $content, $detailUrl, 'Lihat Detail Dokumen');
    }

    public function notifyCreatorRejected(
        User $creator,
        string $documentType,
        string $documentNumber,
        string $stageLabel,
        ?string $actorName,
        ?string $actorRole,
        string $reason,
        ?string $detailUrl = null
    ): void
    {
        $systemName = config('app.name', 'SEFTi');

        $subject = "[{$systemName}] {$documentType} {$documentNumber} DITOLAK";

        $lines = [
            "Dokumen {$documentType} Anda telah DITOLAK.",
            "Nomor: {$documentNumber}",
            "Tahap: {$stageLabel}",
        ];

        if ($actorName) {
            $lines[] = "Oleh: {$actorName}" . ($actorRole ? " ({$actorRole})" : '');
        }

        if ($reason !== '') {
            $lines[] = "Alasan: {$reason}";
        }

        $lines[] = '';
        $lines[] = 'Silakan masuk ke sistem SEFTi untuk melihat detail.';

        $content = implode("\n", $lines);

        $this->sendToUser($creator, $subject, $content, $detailUrl, 'Lihat Detail Dokumen');
    }

    /**
     * Summary email for bulk operations: single email with count and optional list URL.
     */
    public function notifyBulkSummary(
        User $recipient,
        string $documentType,
        int $count,
        string $contextLabel,
        ?string $listUrl = null
    ): void {
        $systemName = config('app.name', 'SEFTi');

        $subject = "[{$systemName}] {$count} {$documentType} {$contextLabel}";

        $lines = [
            "Terdapat {$count} {$documentType} {$contextLabel}.",
            '',
            'Silakan masuk ke sistem SEFTi untuk meninjau atau menindak lanjuti dokumen-dokumen tersebut.',
        ];

        $content = implode("\n", $lines);

        $this->sendToUser($recipient, $subject, $content, $listUrl, 'Buka Daftar Dokumen');
    }
}
