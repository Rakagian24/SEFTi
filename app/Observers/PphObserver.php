<?php

namespace App\Observers;

use App\Models\Pph;

class PphObserver
{
    /**
     * Handle the Pph "created" event.
     */
    public function created(Pph $pph): void
    {
        $this->clearPphCaches();
    }

    /**
     * Handle the Pph "updated" event.
     */
    public function updated(Pph $pph): void
    {
        $this->clearPphCaches();
    }

    /**
     * Handle the Pph "deleted" event.
     */
    public function deleted(Pph $pph): void
    {
        $this->clearPphCaches();
    }

    /**
     * Handle the Pph "restored" event.
     */
    public function restored(Pph $pph): void
    {
        $this->clearPphCaches();
    }

    /**
     * Handle the Pph "force deleted" event.
     */
    public function forceDeleted(Pph $pph): void
    {
        $this->clearPphCaches();
    }

    /**
     * Clear all pph-related caches
     */
    private function clearPphCaches(): void
    {
        // Clear pph-related cache keys
        cache()->forget('pphs_active');
        cache()->forget('pphs_all');
    }
}
