<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\PurchaseOrder;

class ApprovalWorkflowService
{
    /**
     * Define approval workflows for each department
     */
    const WORKFLOWS = [
        // SGT departments: Staff Akunting & Finance -> Kabag -> Kadiv -> Direksi
        'SGT 1' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Akunting & Finance', 'Kabag', 'Kadiv', 'Direksi']
        ],
        'SGT 2' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Akunting & Finance', 'Kabag', 'Kadiv', 'Direksi']
        ],
        'SGT 3' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Akunting & Finance', 'Kabag', 'Kadiv', 'Direksi']
        ],

        // Nirwana Textile departments: Staff Toko -> Kepala Toko -> Kadiv -> Direksi
        'Nirwana Textile Hasanudin' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Kadiv', 'Direksi']
        ],
        'Nirwana Textile Bkr' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Kadiv', 'Direksi']
        ],
        'Nirwana Textile Yogyakarta HOS Cokro' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Kadiv', 'Direksi']
        ],
        'Nirwana Textile Bali' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Kadiv', 'Direksi']
        ],
        'Nirwana Textile Surabaya' => [
            'steps' => ['verified', 'validated', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Kadiv', 'Direksi']
        ],

        // Human Greatness & Zi&Glo: Staff Toko -> Kepala Toko -> Direksi
        'Human Greatness' => [
            'steps' => ['verified', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Direksi']
        ],
        'Zi&Glo' => [
            'steps' => ['verified', 'approved'],
            'roles' => ['Staff Toko', 'Kepala Toko', 'Direksi']
        ]
    ];

    /**
     * Get workflow configuration for a department
     */
    public function getWorkflowForDepartment(string $departmentName): ?array
    {
        return self::WORKFLOWS[$departmentName] ?? null;
    }

    /**
     * Get next approval step for a purchase order
     */
    public function getNextApprovalStep(PurchaseOrder $purchaseOrder): ?string
    {
        $workflow = $this->getWorkflowForDepartment($purchaseOrder->department->name);

        if (!$workflow) {
            return null;
        }

        $currentStatus = $purchaseOrder->status;

        // Map status to workflow steps
        $statusToStep = [
            'In Progress' => 'verified',
            'Verified' => 'validated',
            'Validated' => 'approved'
        ];

        $currentStep = $statusToStep[$currentStatus] ?? null;

        if (!$currentStep) {
            return null;
        }

        // Find next step in workflow
        $currentIndex = array_search($currentStep, $workflow['steps']);

        if ($currentIndex === false || $currentIndex >= count($workflow['steps']) - 1) {
            return null; // No next step
        }

        return $workflow['steps'][$currentIndex + 1];
    }

    /**
     * Get required role for next approval step
     */
    public function getRequiredRoleForStep(PurchaseOrder $purchaseOrder, string $step): ?string
    {
        $workflow = $this->getWorkflowForDepartment($purchaseOrder->department->name);

        if (!$workflow) {
            return null;
        }

        $stepIndex = array_search($step, $workflow['steps']);

        if ($stepIndex === false) {
            return null;
        }

        return $workflow['roles'][$stepIndex + 1] ?? null; // +1 because roles[0] is creator role
    }

    /**
     * Check if user can perform approval action on purchase order
     */
    public function canUserApprove(User $user, PurchaseOrder $purchaseOrder, string $action): bool
    {
        // Admin can do everything
        if ($user->role->name === 'Admin') {
            return true;
        }

        $workflow = $this->getWorkflowForDepartment($purchaseOrder->department->name);

        if (!$workflow) {
            return false;
        }

        $userRole = $user->role->name;
        $currentStatus = $purchaseOrder->status;

        // Check if user's role is in the workflow
        if (!in_array($userRole, $workflow['roles'])) {
            return false;
        }

        // Check if user can perform the specific action based on current status
        switch ($action) {
            case 'verify':
                // For SGT departments: Kabag can verify (after Staff Akunting creates)
                // For Nirwana Textile departments: Kepala Toko can verify (after Staff Toko creates)
                // For Human Greatness & Zi&Glo: Kepala Toko can verify (after Staff Toko creates)
                if ($currentStatus !== 'In Progress') {
                    return false;
                }

                if (in_array($purchaseOrder->department->name, ['SGT 1', 'SGT 2', 'SGT 3'])) {
                    return in_array($userRole, ['Kabag', 'Admin']);
                } else {
                    return in_array($userRole, ['Kepala Toko', 'Admin']);
                }

            case 'validate':
                // Only Kadiv can validate (after verification)
                if ($currentStatus !== 'Verified') {
                    return false;
                }

                return in_array($userRole, ['Kadiv', 'Admin']);

            case 'approve':
                // For Human Greatness & Zi&Glo: Direksi can approve after verified (skip validation)
                // For other departments: only Direksi can approve after validated
                if (in_array($purchaseOrder->department->name, ['Human Greatness', 'Zi&Glo'])) {
                    return $currentStatus === 'Verified' &&
                           in_array($userRole, ['Direksi', 'Admin']);
                } else {
                    return $currentStatus === 'Validated' &&
                           in_array($userRole, ['Direksi', 'Admin']);
                }

            case 'reject':
                // Can reject at any approval level
                return in_array($currentStatus, ['In Progress', 'Verified', 'Validated']) &&
                       in_array($userRole, $workflow['roles']);
        }

        return false;
    }

    /**
     * Get approval progress for a purchase order
     */
    public function getApprovalProgress(PurchaseOrder $purchaseOrder): array
    {
        $workflow = $this->getWorkflowForDepartment($purchaseOrder->department->name);

        if (!$workflow) {
            return [];
        }

        $progress = [];
        $currentStatus = $purchaseOrder->status;

        foreach ($workflow['steps'] as $index => $step) {
            $role = $workflow['roles'][$index + 1] ?? null;

            if (!$role) continue;

            $status = $this->getStepStatus($purchaseOrder, $step, $currentStatus);

            $progress[] = [
                'step' => $step,
                'role' => $role,
                'status' => $status,
                'completed_at' => $this->getStepCompletedAt($purchaseOrder, $step),
                'completed_by' => $this->getStepCompletedBy($purchaseOrder, $step)
            ];
        }

        return $progress;
    }

    /**
     * Get status of a specific step
     */
    private function getStepStatus(PurchaseOrder $purchaseOrder, string $step, string $currentStatus): string
    {
        $statusMap = [
            'verified' => 'Verified',
            'validated' => 'Validated',
            'approved' => 'Approved'
        ];

        $targetStatus = $statusMap[$step] ?? null;

        if (!$targetStatus) {
            return 'pending';
        }

        // Check if step is completed
        if ($currentStatus === $targetStatus ||
            ($currentStatus === 'Approved' && $step === 'approved') ||
            ($currentStatus === 'Validated' && in_array($step, ['verified', 'validated'])) ||
            ($currentStatus === 'Verified' && $step === 'verified')) {
            return 'completed';
        }

        // Check if step is current
        if (($currentStatus === 'In Progress' && $step === 'verified') ||
            ($currentStatus === 'Verified' && $step === 'validated') ||
            ($currentStatus === 'Validated' && $step === 'approved')) {
            return 'current';
        }

        return 'pending';
    }

    /**
     * Get completion timestamp for a step
     */
    private function getStepCompletedAt(PurchaseOrder $purchaseOrder, string $step): ?string
    {
        switch ($step) {
            case 'verified':
                return $purchaseOrder->verified_at?->toDateTimeString();
            case 'validated':
                return $purchaseOrder->validated_at?->toDateTimeString();
            case 'approved':
                return $purchaseOrder->approved_at?->toDateTimeString();
        }

        return null;
    }

    /**
     * Get user who completed a step
     */
    private function getStepCompletedBy(PurchaseOrder $purchaseOrder, string $step): ?array
    {
        switch ($step) {
            case 'verified':
                return $purchaseOrder->verifier ? [
                    'id' => $purchaseOrder->verifier->id,
                    'name' => $purchaseOrder->verifier->name
                ] : null;
            case 'validated':
                return $purchaseOrder->validator ? [
                    'id' => $purchaseOrder->validator->id,
                    'name' => $purchaseOrder->validator->name
                ] : null;
            case 'approved':
                return $purchaseOrder->approver ? [
                    'id' => $purchaseOrder->approver->id,
                    'name' => $purchaseOrder->approver->name
                ] : null;
        }

        return null;
    }

    /**
     * Get next status after performing an action
     */
    public function getNextStatusAfterAction(string $currentStatus, string $action): string
    {
        switch ($action) {
            case 'verify':
                return 'Verified';
            case 'validate':
                return 'Validated';
            case 'approve':
                return 'Approved';
            case 'reject':
                return 'Rejected';
        }

        return $currentStatus;
    }
}
