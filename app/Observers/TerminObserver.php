<?php

namespace App\Observers;

use App\Models\Termin;

class TerminObserver
{
    /**
     * Handle the Termin "created" event.
     */
    public function created(Termin $termin): void
    {
        $this->clearTerminCaches();
    }

    /**
     * Handle the Termin "updated" event.
     */
    public function updated(Termin $termin): void
    {
        $this->clearTerminCaches();
    }

    /**
     * Handle the Termin "deleted" event.
     */
    public function deleted(Termin $termin): void
    {
        $this->clearTerminCaches();
    }

    /**
     * Handle the Termin "restored" event.
     */
    public function restored(Termin $termin): void
    {
        $this->clearTerminCaches();
    }

    /**
     * Handle the Termin "force deleted" event.
     */
    public function forceDeleted(Termin $termin): void
    {
        $this->clearTerminCaches();
    }

    /**
     * Clear all termin-related caches
     */
    private function clearTerminCaches(): void
    {
        // Clear termin-related cache keys
        cache()->forget('termins_active');
        cache()->forget('termins_all');
    }
}
