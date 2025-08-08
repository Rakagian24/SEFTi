<?php

namespace App\Observers;

use App\Models\ArPartner;

class ArPartnerObserver
{
    /**
     * Handle the ArPartner "created" event.
     */
    public function created(ArPartner $arPartner): void
    {
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "updated" event.
     */
    public function updated(ArPartner $arPartner): void
    {
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "deleted" event.
     */
    public function deleted(ArPartner $arPartner): void
    {
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "restored" event.
     */
    public function restored(ArPartner $arPartner): void
    {
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "force deleted" event.
     */
    public function forceDeleted(ArPartner $arPartner): void
    {
        $this->clearArPartnerCaches();
    }

    /**
     * Clear all ar partner-related caches
     */
    private function clearArPartnerCaches(): void
    {
        // Clear ar partner-related cache keys
        cache()->forget('ar_partners_active');
        cache()->forget('ar_partners_all');
    }
}
