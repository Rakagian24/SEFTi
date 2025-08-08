<?php

namespace App\Observers;

use App\Models\Department;

class DepartmentObserver
{
    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        $this->clearDepartmentCaches();
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        $this->clearDepartmentCaches();
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        $this->clearDepartmentCaches();
    }

    /**
     * Handle the Department "restored" event.
     */
    public function restored(Department $department): void
    {
        $this->clearDepartmentCaches();
    }

    /**
     * Handle the Department "force deleted" event.
     */
    public function forceDeleted(Department $department): void
    {
        $this->clearDepartmentCaches();
    }

    /**
     * Clear all department-related caches
     */
    private function clearDepartmentCaches(): void
    {
        // Clear all department-related cache keys
        cache()->forget('departments_all_list');
        cache()->forget('departments_active_accounts');
        cache()->forget('departments_active_bank_masuk');
        cache()->forget('departments_active_users');
        cache()->forget('departments_all');
    }
}
