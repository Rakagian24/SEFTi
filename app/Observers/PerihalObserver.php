<?php

namespace App\Observers;

use App\Models\Perihal;

class PerihalObserver
{
    /**
     * Handle the Perihal "created" event.
     */
    public function created(Perihal $perihal): void
    {
        $this->clearPerihalCaches();
    }

    /**
     * Handle the Perihal "updated" event.
     */
    public function updated(Perihal $perihal): void
    {
        $this->clearPerihalCaches();
    }

    /**
     * Handle the Perihal "deleted" event.
     */
    public function deleted(Perihal $perihal): void
    {
        $this->clearPerihalCaches();
    }

    /**
     * Handle the Perihal "restored" event.
     */
    public function restored(Perihal $perihal): void
    {
        $this->clearPerihalCaches();
    }

    /**
     * Handle the Perihal "force deleted" event.
     */
    public function forceDeleted(Perihal $perihal): void
    {
        $this->clearPerihalCaches();
    }

    /**
     * Clear all perihal-related caches
     */
    private function clearPerihalCaches(): void
    {
        // Clear perihal-related cache keys
        cache()->forget('perihals_active');
        cache()->forget('perihals_all');
    }
}
