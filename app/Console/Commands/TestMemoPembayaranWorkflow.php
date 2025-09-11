<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MemoPembayaran;
use App\Models\User;
use App\Services\ApprovalWorkflowService;

class TestMemoPembayaranWorkflow extends Command
{
    protected $signature = 'approval:test-memo {--limit=10 : Max memos per scenario} {--user= : Test as specific user id}';
    protected $description = 'Dry-run test of Memo Pembayaran approval workflows without altering data.';

    public function handle(ApprovalWorkflowService $workflow)
    {
        $limit = (int) $this->option('limit') ?: 10;
        $testUser = null;
        if ($this->option('user')) {
            $testUser = User::find($this->option('user'));
            if (!$testUser) {
                $this->error('User not found for provided --user option');
                return 1;
            }
        }

        $this->info('Starting Memo Pembayaran workflow dry-run tests...');

        $scenarios = [
            'Staff Toko (non-Zi&Glo)' => function () use ($limit) {
                return MemoPembayaran::with(['creator.role', 'department'])
                    ->whereHas('creator.role', function ($q) { $q->where('name', 'Staff Toko'); })
                    ->whereHas('department', function ($q) { $q->where('name', '!=', 'Zi&Glo'); })
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
            },
            'Staff Toko (Zi&Glo)' => function () use ($limit) {
                return MemoPembayaran::with(['creator.role', 'department'])
                    ->whereHas('creator.role', function ($q) { $q->where('name', 'Staff Toko'); })
                    ->whereHas('department', function ($q) { $q->where('name', 'Zi&Glo'); })
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
            },
            'Staff Akunting & Finance' => function () use ($limit) {
                return MemoPembayaran::with(['creator.role', 'department'])
                    ->whereHas('creator.role', function ($q) { $q->where('name', 'Staff Akunting & Finance'); })
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
            },
            'Staff Digital Marketing' => function () use ($limit) {
                return MemoPembayaran::with(['creator.role', 'department'])
                    ->whereHas('creator.role', function ($q) { $q->where('name', 'Staff Digital Marketing'); })
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
            },
            'Admin' => function () use ($limit) {
                return MemoPembayaran::with(['creator.role', 'department'])
                    ->orderByDesc('id')
                    ->take($limit)
                    ->get();
            },
        ];

        $overallFailures = 0;
        $overallChecked = 0;

        foreach ($scenarios as $label => $resolver) {
            $memos = $resolver();
            if ($memos->isEmpty()) {
                $this->warn("[{$label}] No memos found to test.");
                continue;
            }

            $this->line(str_repeat('-', 60));
            $this->info("Scenario: {$label} (" . $memos->count() . " memos)");

            foreach ($memos as $memo) {
                $overallChecked++;

                DB::beginTransaction();
                try {
                    // Simulate verify/validate/approve/reject based on service
                    $creator = $memo->creator;
                    $department = $memo->department;

                    $rolesToUsers = $this->pickUsersForRoles(['Admin', 'Kepala Toko', 'Kadiv', 'Kabag']);

                    $actors = [
                        'Admin' => $rolesToUsers['Admin'] ?? null,
                        'Kepala Toko' => $rolesToUsers['Kepala Toko'] ?? null,
                        'Kadiv' => $rolesToUsers['Kadiv'] ?? null,
                        'Kabag' => $rolesToUsers['Kabag'] ?? null,
                    ];

                    if ($testUser) {
                        // Override all actions by single user when provided
                        foreach ($actors as $k => $v) { $actors[$k] = $testUser; }
                    }

                    $canVerify = $workflow->canUserApproveMemoPembayaran($actors['Kepala Toko'] ?? $actors['Kadiv'] ?? $actors['Kabag'] ?? $actors['Admin'], $memo, 'verify');
                    $canValidate = $workflow->canUserApproveMemoPembayaran($actors['Kadiv'] ?? $actors['Kabag'] ?? $actors['Admin'], $memo, 'validate');
                    $canApprove = $workflow->canUserApproveMemoPembayaran($actors['Kadiv'] ?? $actors['Kabag'] ?? $actors['Admin'], $memo, 'approve');

                    // Try transitions according to current status
                    $this->line("- Memo #{$memo->no_mb} status={$memo->status} dept=" . ($department->name ?? '-') . " creatorRole=" . ($creator?->role?->name ?? '-'));

                    if ($memo->status === 'In Progress' && $canVerify) {
                        $this->line('  > verify: OK (permitted)');
                    } elseif ($memo->status === 'In Progress' && !$canVerify) {
                        $this->warn('  > verify: BLOCKED (as expected or missing role)');
                    }

                    if (in_array($memo->status, ['In Progress', 'Verified', 'Validated'], true)) {
                        $canReject = $workflow->canUserApproveMemoPembayaran($actors['Kadiv'] ?? $actors['Kepala Toko'] ?? $actors['Kabag'] ?? $actors['Admin'], $memo, 'reject');
                        $this->line('  > reject: ' . ($canReject ? 'OK (permitted)' : 'BLOCKED'));
                    }

                    if ($memo->status === 'Verified' && $canValidate) {
                        $this->line('  > validate: OK (permitted)');
                    } elseif ($memo->status === 'Verified') {
                        $this->warn('  > validate: BLOCKED');
                    }

                    if (in_array($memo->status, ['In Progress', 'Verified', 'Validated'], true) && $canApprove) {
                        $this->line('  > approve: OK (permitted)');
                    } elseif (in_array($memo->status, ['In Progress', 'Verified', 'Validated'], true)) {
                        $this->warn('  > approve: BLOCKED');
                    }

                    // Do not persist changes
                    DB::rollBack();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $overallFailures++;
                    $this->error('  ! Error: ' . $e->getMessage());
                }
            }
        }

        $this->line(str_repeat('=', 60));
        $this->info("Checked memos: {$overallChecked}");
        if ($overallFailures > 0) {
            $this->warn("Failures: {$overallFailures}");
        } else {
            $this->info('No runtime errors during dry-run.');
        }

        return $overallFailures > 0 ? 1 : 0;
    }

    private function pickUsersForRoles(array $roleNames): array
    {
        $result = [];
        foreach ($roleNames as $roleName) {
            $user = User::whereHas('role', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            })->first();
            if ($user) {
                $result[$roleName] = $user;
            }
        }
        return $result;
    }
}

