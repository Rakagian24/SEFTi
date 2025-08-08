<?php

namespace App\Observers;

use App\Models\Role;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->clearRoleCaches();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        $this->clearRoleCaches();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->clearRoleCaches();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        $this->clearRoleCaches();
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        $this->clearRoleCaches();
    }

    /**
     * Clear all role-related caches
     */
    private function clearRoleCaches(): void
    {
        // Clear all role-related cache keys
        cache()->forget('roles_active');
        cache()->forget('roles_all_list');
    }
}
