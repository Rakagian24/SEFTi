<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DepartmentService
{
    /**
     * Get department options for dropdowns based on user permissions
     *
     * @param bool $includeAllOption Whether to include "All" option for users with multiple departments
     * @return array
     */
        public static function getOptionsForUser(bool $includeAllOption = false): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $userDepartments = $user->departments;

        // If user has no departments, return empty array
        if ($userDepartments->isEmpty()) {
            return [];
        }

        // Check if user has 'All' department - they can access everything
        if ($user->departments->contains(function ($dept) {
            return $dept->name === 'All';
        })) {
            // User dengan 'All' bisa akses semua department
            $allDepartments = Department::select('id', 'name')
                ->orderBy('name')
                ->get();

            $options = [];

            // // Add "All" option if requested
            // if ($includeAllOption) {
            //     $options[] = [
            //         'id' => 'all',
            //         'name' => 'Semua Departemen',
            //         'disabled' => false
            //     ];
            // }

            // Add all available departments
            foreach ($allDepartments as $dept) {
                $options[] = [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'disabled' => false
                ];
            }

            return $options;
        }

        // If user has only one department, return only that department
        if ($userDepartments->count() === 1) {
            $dept = $userDepartments->first();
            return [
                [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'disabled' => false
                ]
            ];
        }

        // If user has multiple departments (but not 'All')
        $options = [];

        // Add "All" option if requested and user has multiple departments
        // if ($includeAllOption) {
        //     $options[] = [
        //         'id' => 'all',
        //         'name' => 'Semua Departemen Saya',
        //         'disabled' => false
        //     ];
        // }

        // Add user's departments
        foreach ($userDepartments as $dept) {
            $options[] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'disabled' => false
            ];
        }

        return $options;
    }

    /**
     * Get department options for forms (create/edit)
     * Forms should only show user's departments, no "All" option
     *
     * @return array
     */
    public static function getOptionsForForm(): array
    {
        return self::getOptionsForUser(false);
    }

    /**
     * Get department options for filters
     * Filters can include "All" option for users with multiple departments
     *
     * @return array
     */
    public static function getOptionsForFilter(): array
    {
        return self::getOptionsForUser(true);
    }

    /**
     * Check if user should see department dropdown
     *
     * @return bool
     */
    public static function shouldShowDropdown(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user->departments->count() > 1;
    }

    /**
     * Get default department for user
     *
     * @return int|null
     */
    public static function getDefaultDepartment(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();
        $firstDept = $user->departments->first();

        return $firstDept ? $firstDept->id : null;
    }

    /**
     * Check if user has access to specific department
     *
     * @param int $departmentId
     * @return bool
     */
    public static function userHasAccess(int $departmentId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // User with 'All' department can access everything
        if ($user->departments->contains('name', 'All')) {
            return true;
        }

        return $user->departments->contains('id', $departmentId);
    }
}
