<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\PurchaseOrder;

class ApprovalWorkflowService
{
    /**
     * Derive workflow steps and roles for a specific Purchase Order
     * Rules (creator-role based with Zi&Glo override):
     * - If department is Zi&Glo: Kadiv -> Direksi (In Progress -> Validated -> Approved)
     * - If creator is Staff Toko: Kepala Toko -> Kadiv -> Direksi (verify, validate, approve)
     * - If creator is Staff Akunting & Finance: Kabag -> Direksi (verify, approve)
     * - If creator is Staff Digital Marketing: Kadiv -> Direksi (validate, approve)
     */
    private function getWorkflowForPurchaseOrder(PurchaseOrder $purchaseOrder): ?array
    {
        $creatorRole = $purchaseOrder->creator?->role?->name ?? null;
        $departmentName = $purchaseOrder->department?->name ?? '';

        if (!$creatorRole) {
            return null;
        }

        // Zi&Glo override regardless of creator
        if ($departmentName === 'Zi&Glo') {
            return [
                'steps' => ['validated', 'approved'],
                'roles' => [$creatorRole, 'Kadiv', 'Direksi']
            ];
        }

        switch ($creatorRole) {
            case 'Staff Toko':
                return [
                    'steps' => ['verified', 'validated', 'approved'],
                    'roles' => [$creatorRole, 'Kepala Toko', 'Kadiv', 'Direksi']
                ];
            case 'Staff Akunting & Finance':
                return [
                    'steps' => ['verified', 'approved'],
                    'roles' => [$creatorRole, 'Kabag', 'Direksi']
                ];
            case 'Staff Digital Marketing':
                return [
                    'steps' => ['validated', 'approved'],
                    'roles' => [$creatorRole, 'Kadiv', 'Direksi']
                ];
        }

        return null; // Unknown creator role – no workflow
    }

    /**
     * Get workflow configuration for a department
     */
    // Deprecated: department-based workflow (kept for backward-compatibility if needed)
    public function getWorkflowForDepartment(string $departmentName): ?array
    {
        return null;
    }

    /**
     * Get next approval step for a purchase order
     */
    public function getNextApprovalStep(PurchaseOrder $purchaseOrder): ?string
    {
        $workflow = $this->getWorkflowForPurchaseOrder($purchaseOrder);

        if (!$workflow) {
            return null;
        }

        $currentStatus = $purchaseOrder->status;

        // Map status to workflow steps
        // Determine current step based on available steps in the workflow
        $steps = $workflow['steps'];
        $currentStep = null;
        if ($currentStatus === 'In Progress') {
            $currentStep = $steps[0] ?? null;
        } elseif ($currentStatus === 'Verified' && in_array('verified', $steps, true)) {
            // If verified exists in steps, next should be validate or approve depending on workflow
            $verifiedIndex = array_search('verified', $steps, true);
            $currentStep = $steps[$verifiedIndex + 1] ?? null;
        } elseif ($currentStatus === 'Validated' && in_array('validated', $steps, true)) {
            $validatedIndex = array_search('validated', $steps, true);
            $currentStep = $steps[$validatedIndex + 1] ?? null;
        }

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
        $workflow = $this->getWorkflowForPurchaseOrder($purchaseOrder);

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

        $workflow = $this->getWorkflowForPurchaseOrder($purchaseOrder);

        if (!$workflow) {
            return false;
        }

        $userRole = $user->role->name;
        $currentStatus = $purchaseOrder->status;

        $steps = $workflow['steps'];

        // Quick reject rule: allow reject at any active stage for any role present in workflow
        if ($action === 'reject') {
            return in_array($currentStatus, ['In Progress', 'Verified', 'Validated'], true)
                && in_array($userRole, $workflow['roles'], true);
        }

        // Map step -> required role
        $stepToRole = [];
        foreach ($steps as $index => $step) {
            $stepToRole[$step] = $workflow['roles'][$index + 1] ?? null; // +1 to skip creator
        }

        // Determine which step the action corresponds to
        $actionStep = null;
        if ($action === 'verify' && in_array('verified', $steps, true)) {
            $actionStep = 'verified';
        } elseif ($action === 'validate' && in_array('validated', $steps, true)) {
            $actionStep = 'validated';
        } elseif ($action === 'approve' && in_array('approved', $steps, true)) {
            $actionStep = 'approved';
        }

        if (!$actionStep) {
            return false; // action not part of this workflow
        }

        // Validate current status prerequisite for the action
        $requiredPrevStatus = null;
        $stepStatusMap = [
            'verified' => 'Verified',
            'validated' => 'Validated',
            'approved' => 'Approved',
        ];

        $actionIndex = array_search($actionStep, $steps, true);
        if ($actionIndex === 0) {
            // First step requires In Progress
            $requiredPrevStatus = 'In Progress';
        } else {
            $prevStep = $steps[$actionIndex - 1];
            $requiredPrevStatus = $stepStatusMap[$prevStep] ?? null;
        }

        if (!$requiredPrevStatus) {
            return false;
        }

        if ($currentStatus !== $requiredPrevStatus) {
            return false;
        }

        // Finally, check role requirement
        $requiredRole = $stepToRole[$actionStep] ?? null;
        return $requiredRole !== null && in_array($userRole, [$requiredRole, 'Admin'], true);

        return false;
    }

    /**
     * Get approval progress for a purchase order
     */
    public function getApprovalProgress(PurchaseOrder $purchaseOrder): array
    {
        $workflow = $this->getWorkflowForPurchaseOrder($purchaseOrder);

        if (!$workflow) {
            return [];
        }

        $progress = [];
        $currentStatus = $purchaseOrder->status;

        $steps = $workflow['steps'];
        $currentIndex = null;
        $statusIndexMap = ['In Progress' => -1, 'Verified' => array_search('verified', $steps, true), 'Validated' => array_search('validated', $steps, true), 'Approved' => array_search('approved', $steps, true)];
        if ($purchaseOrder->status === 'In Progress') {
            $currentIndex = 0;
        } elseif ($purchaseOrder->status === 'Verified' && $statusIndexMap['Verified'] !== false) {
            $currentIndex = ($statusIndexMap['Verified'] ?? 0) + 1;
        } elseif ($purchaseOrder->status === 'Validated' && $statusIndexMap['Validated'] !== false) {
            $currentIndex = ($statusIndexMap['Validated'] ?? 0) + 1;
        } else {
            // Approved or unknown: mark all completed
            $currentIndex = count($steps);
        }

        foreach ($steps as $index => $step) {
            $role = $workflow['roles'][$index + 1] ?? null;

            if (!$role) continue;

            // Determine visual status for progress UI
            $status = 'pending';
            if ($index < $currentIndex) {
                $status = 'completed';
            } elseif ($index === $currentIndex) {
                $status = in_array($purchaseOrder->status, ['Approved'], true) ? 'completed' : 'current';
            }

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
        // Deprecated: status determination is handled inline in getApprovalProgress()
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
