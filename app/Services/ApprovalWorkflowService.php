<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\MemoPembayaran;
use App\Models\PaymentVoucher;

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

        // Zi&Glo & Human Greatness override regardless of creator
        if ($departmentName === 'Zi&Glo' || $departmentName === 'Human Greatness') {
            return [
                'steps' => ['verified', 'approved'],
                'roles' => [$creatorRole, 'Kepala Toko', 'Direksi']
            ];
        }

        switch ($creatorRole) {
            case 'Admin':
                return [
                    'steps' => ['verified', 'validated', 'approved'],
                    'roles' => [$creatorRole, 'Kepala Toko', 'Kadiv', 'Direksi']
                ];
            case 'Kepala Toko':
                // Treat PO created by Kepala Toko the same as Staff Toko flow
                // so that after auto-Verified, Kadiv can Validate, then Direksi Approve
                return [
                    'steps' => ['verified', 'validated', 'approved'],
                    'roles' => [$creatorRole, 'Kepala Toko', 'Kadiv', 'Direksi']
                ];
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
        // BUT prevent user who already performed an action from rejecting
        if ($action === 'reject') {
            // Check if user has already performed any action
            if ($this->hasUserPerformedAction($user, $purchaseOrder)) {
                return false;
            }

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

    // ==================== MEMO PEMBAYARAN WORKFLOW ====================

    /**
     * Derive workflow steps and roles for a specific Memo Pembayaran
     * Rules (creator-role based with Zi&Glo override):
     * - If creator is Staff Toko (any department): Kepala Toko -> Kadiv (verify, approve)
     * - If creator is Staff Toko in Zi&Glo department: Kadiv (approve only)
     * - If creator is Staff Akunting & Finance: Kabag (approve only)
     * - If creator is Staff Digital Marketing: Kadiv (approve only)
     */
    private function getWorkflowForMemoPembayaran(MemoPembayaran $memoPembayaran): ?array
    {
        $creatorRole = $memoPembayaran->creator?->role?->name ?? null;
        $departmentName = $memoPembayaran->department?->name ?? '';

        if (!$creatorRole) {
            return null;
        }

        switch ($creatorRole) {
            case 'Admin':
                return [
                    'steps' => ['verified', 'approved'],
                    'roles' => [$creatorRole, 'Kepala Toko', 'Kadiv']
                ];
            case 'Kepala Toko':
                // Memo created by Kepala Toko: skip verify UI-wise but keep step for status mapping
                // Flow: (Verified) -> Approved by Kadiv
                return [
                    'steps' => ['verified', 'approved'],
                    'roles' => [$creatorRole, 'Kepala Toko', 'Kadiv']
                ];
            case 'Staff Toko':
                // Special case: Staff Toko in Zi&Glo or Human Greatness department goes directly to Kadiv
                if ($departmentName === 'Zi&Glo' || $departmentName === 'Human Greatness') {
                    return [
                        'steps' => ['approved'],
                        'roles' => [$creatorRole, 'Kadiv']
                    ];
                }
                // Regular Staff Toko workflow: Kepala Toko -> Kadiv
                return [
                    'steps' => ['verified', 'approved'],
                    'roles' => [$creatorRole, 'Kepala Toko', 'Kadiv']
                ];
            case 'Staff Akunting & Finance':
                return [
                    'steps' => ['approved'],
                    'roles' => [$creatorRole, 'Kabag']
                ];
            case 'Staff Digital Marketing':
                return [
                    'steps' => ['approved'],
                    'roles' => [$creatorRole, 'Kadiv']
                ];
        }

        return null; // Unknown creator role – no workflow
    }

    /**
     * Check if user can perform approval action on memo pembayaran
     */
    public function canUserApproveMemoPembayaran(User $user, MemoPembayaran $memoPembayaran, string $action): bool
    {
        // Admin can do everything
        if ($user->role->name === 'Admin') {
            return true;
        }

        $workflow = $this->getWorkflowForMemoPembayaran($memoPembayaran);

        if (!$workflow) {
            return false;
        }

        $userRole = $user->role->name;
        $currentStatus = $memoPembayaran->status;

        $steps = $workflow['steps'];

        // Quick reject rule: allow reject at any active stage for any role present in workflow
        // BUT prevent user who already performed an action from rejecting
        if ($action === 'reject') {
            // Check if user has already performed any action
            if ($this->hasUserPerformedActionForMemo($user, $memoPembayaran)) {
                return false;
            }

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

        // Special case: Kepala Toko memo yang langsung Verified bisa di-approve
        $creatorRole = $memoPembayaran->creator?->role?->name;
        if ($actionStep === 'approved' && $creatorRole === 'Kepala Toko' && $currentStatus === 'Verified') {
            $requiredPrevStatus = 'Verified';
        }

        if ($currentStatus !== $requiredPrevStatus) {
            return false;
        }

        // Finally, check role requirement
        $requiredRole = $stepToRole[$actionStep] ?? null;
        return $requiredRole !== null && in_array($userRole, [$requiredRole, 'Admin'], true);
    }

    /**
     * Get approval progress for a memo pembayaran
     */
    public function getApprovalProgressForMemoPembayaran(MemoPembayaran $memoPembayaran): array
    {
        $workflow = $this->getWorkflowForMemoPembayaran($memoPembayaran);

        if (!$workflow) {
            return [];
        }

        $progress = [];
        $currentStatus = $memoPembayaran->status;

        $steps = $workflow['steps'];
        $currentIndex = null;
        $statusIndexMap = ['In Progress' => -1, 'Verified' => array_search('verified', $steps, true), 'Validated' => array_search('validated', $steps, true), 'Approved' => array_search('approved', $steps, true)];
        if ($memoPembayaran->status === 'In Progress') {
            $currentIndex = 0;
        } elseif ($memoPembayaran->status === 'Verified' && $statusIndexMap['Verified'] !== false) {
            $currentIndex = ($statusIndexMap['Verified'] ?? 0) + 1;
        } elseif ($memoPembayaran->status === 'Validated' && $statusIndexMap['Validated'] !== false) {
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
                $status = in_array($memoPembayaran->status, ['Approved'], true) ? 'completed' : 'current';
            }

            $progress[] = [
                'step' => $step,
                'role' => $role,
                'status' => $status,
                'completed_at' => $this->getStepCompletedAtForMemoPembayaran($memoPembayaran, $step),
                'completed_by' => $this->getStepCompletedByForMemoPembayaran($memoPembayaran, $step)
            ];
        }

        return $progress;
    }

    /**
     * Get completion timestamp for a step in memo pembayaran
     */
    private function getStepCompletedAtForMemoPembayaran(MemoPembayaran $memoPembayaran, string $step): ?string
    {
        switch ($step) {
            case 'verified':
                return $memoPembayaran->verified_at?->toDateTimeString();
            case 'validated':
                return $memoPembayaran->validated_at?->toDateTimeString();
            case 'approved':
                return $memoPembayaran->approved_at?->toDateTimeString();
        }

        return null;
    }

    /**
     * Get user who completed a step in memo pembayaran
     */
    private function getStepCompletedByForMemoPembayaran(MemoPembayaran $memoPembayaran, string $step): ?array
    {
        switch ($step) {
            case 'verified':
                return $memoPembayaran->verifier ? [
                    'id' => $memoPembayaran->verifier->id,
                    'name' => $memoPembayaran->verifier->name
                ] : null;
            case 'validated':
                return $memoPembayaran->validator ? [
                    'id' => $memoPembayaran->validator->id,
                    'name' => $memoPembayaran->validator->name
                ] : null;
            case 'approved':
                return $memoPembayaran->approver ? [
                    'id' => $memoPembayaran->approver->id,
                    'name' => $memoPembayaran->approver->name
                ] : null;
        }

        return null;
    }

    /**
     * Check if user has already performed any action on purchase order
     */
    private function hasUserPerformedAction(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // Check if user is the verifier
        if ($purchaseOrder->verifier_id === $user->id) {
            return true;
        }

        // Check if user is the validator
        if ($purchaseOrder->validator_id === $user->id) {
            return true;
        }

        // Check if user is the approver
        if ($purchaseOrder->approver_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has already performed any action on memo pembayaran
     */
    private function hasUserPerformedActionForMemo(User $user, MemoPembayaran $memoPembayaran): bool
    {
        // Check if user is the verifier
        if ($memoPembayaran->verifier_id === $user->id) {
            return true;
        }

        // Check if user is the validator
        if ($memoPembayaran->validator_id === $user->id) {
            return true;
        }

        // Check if user is the approver
        if ($memoPembayaran->approver_id === $user->id) {
            return true;
        }

        return false;
    }

    // ==================== PAYMENT VOUCHER WORKFLOW ====================

    /**
     * Derive workflow steps and roles for a specific Payment Voucher
     * Rules: Creator -> Kabag -> Direksi (verified -> approved)
     * Simple 2-step workflow regardless of creator role
     */
    private function getWorkflowForPaymentVoucher(PaymentVoucher $paymentVoucher): ?array
    {
        $creatorRole = $paymentVoucher->creator?->role?->name ?? null;

        if (!$creatorRole) {
            return null;
        }

        // Payment Voucher workflows by tipe_pv
        $tipe = $paymentVoucher->tipe_pv;
        if ($tipe === 'Pajak') {
            // Creator -> Kabag (verify) -> Kadiv (validate) -> Direksi (approve)
            return [
                'steps' => ['verified', 'validated', 'approved'],
                'roles' => [$creatorRole, 'Kabag', 'Kadiv', 'Direksi']
            ];
        }
        if ($tipe === 'Manual') {
            // Creator -> Kabag (approve only, single-step)
            return [
                'steps' => ['approved'],
                'roles' => [$creatorRole, 'Kabag']
            ];
        }

        // Default: Creator -> Kabag (verify) -> Direksi (approve)
        return [
            'steps' => ['verified', 'approved'],
            'roles' => [$creatorRole, 'Kabag', 'Direksi']
        ];
    }

    /**
     * Check if user can perform approval action on payment voucher
     */
    public function canUserApprovePaymentVoucher(User $user, PaymentVoucher $paymentVoucher, string $action): bool
    {
        // Admin can do everything
        if ($user->role->name === 'Admin') {
            return true;
        }

        $workflow = $this->getWorkflowForPaymentVoucher($paymentVoucher);

        if (!$workflow) {
            return false;
        }

        $userRole = $user->role->name;
        $currentStatus = $paymentVoucher->status;

        $steps = $workflow['steps'];

        // Quick reject rule: allow reject at any active stage for any role present in workflow
        // BUT prevent user who already performed an action from rejecting
        if ($action === 'reject') {
            // Check if user has already performed any action
            if ($this->hasUserPerformedActionForPaymentVoucher($user, $paymentVoucher)) {
                return false;
            }

            return in_array($currentStatus, ['In Progress', 'Verified'], true)
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
    }

    /**
     * Get approval progress for a payment voucher
     */
    public function getApprovalProgressForPaymentVoucher(PaymentVoucher $paymentVoucher): array
    {
        $workflow = $this->getWorkflowForPaymentVoucher($paymentVoucher);

        if (!$workflow) {
            return [];
        }

        $progress = [];
        $currentStatus = $paymentVoucher->status;

        $steps = $workflow['steps'];
        $currentIndex = null;
        $statusIndexMap = ['In Progress' => -1, 'Verified' => array_search('verified', $steps, true), 'Approved' => array_search('approved', $steps, true)];
        if ($paymentVoucher->status === 'In Progress') {
            $currentIndex = 0;
        } elseif ($paymentVoucher->status === 'Verified' && $statusIndexMap['Verified'] !== false) {
            $currentIndex = ($statusIndexMap['Verified'] ?? 0) + 1;
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
                $status = in_array($paymentVoucher->status, ['Approved'], true) ? 'completed' : 'current';
            }

            $progress[] = [
                'step' => $step,
                'role' => $role,
                'status' => $status,
                'completed_at' => $this->getStepCompletedAtForPaymentVoucher($paymentVoucher, $step),
                'completed_by' => $this->getStepCompletedByForPaymentVoucher($paymentVoucher, $step)
            ];
        }

        return $progress;
    }

    /**
     * Get completion timestamp for a step in payment voucher
     */
    private function getStepCompletedAtForPaymentVoucher(PaymentVoucher $paymentVoucher, string $step): ?string
    {
        switch ($step) {
            case 'verified':
                return $paymentVoucher->verified_at?->toDateTimeString();
            case 'approved':
                return $paymentVoucher->approved_at?->toDateTimeString();
        }

        return null;
    }

    /**
     * Get user who completed a step in payment voucher
     */
    private function getStepCompletedByForPaymentVoucher(PaymentVoucher $paymentVoucher, string $step): ?array
    {
        switch ($step) {
            case 'verified':
                return $paymentVoucher->verifier ? [
                    'id' => $paymentVoucher->verifier->id,
                    'name' => $paymentVoucher->verifier->name
                ] : null;
            case 'approved':
                return $paymentVoucher->approver ? [
                    'id' => $paymentVoucher->approver->id,
                    'name' => $paymentVoucher->approver->name
                ] : null;
        }

        return null;
    }

    /**
     * Check if user has already performed any action on payment voucher
     */
    private function hasUserPerformedActionForPaymentVoucher(User $user, PaymentVoucher $paymentVoucher): bool
    {
        // Check if user is the verifier
        if ($paymentVoucher->verified_by === $user->id) {
            return true;
        }

        // Check if user is the approver
        if ($paymentVoucher->approved_by === $user->id) {
            return true;
        }

        return false;
    }
}
