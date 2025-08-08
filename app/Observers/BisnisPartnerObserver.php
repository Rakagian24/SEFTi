<?php

namespace App\Observers;

use App\Models\BisnisPartner;

class BisnisPartnerObserver
{
    /**
     * Handle the BisnisPartner "created" event.
     */
    public function created(BisnisPartner $bisnisPartner): void
    {
        $this->clearBisnisPartnerCaches();
    }

    /**
     * Handle the BisnisPartner "updated" event.
     */
    public function updated(BisnisPartner $bisnisPartner): void
    {
        $this->clearBisnisPartnerCaches();
    }

    /**
     * Handle the BisnisPartner "deleted" event.
     */
    public function deleted(BisnisPartner $bisnisPartner): void
    {
        $this->clearBisnisPartnerCaches();
    }

    /**
     * Handle the BisnisPartner "restored" event.
     */
    public function restored(BisnisPartner $bisnisPartner): void
    {
        $this->clearBisnisPartnerCaches();
    }

    /**
     * Handle the BisnisPartner "force deleted" event.
     */
    public function forceDeleted(BisnisPartner $bisnisPartner): void
    {
        $this->clearBisnisPartnerCaches();
    }

    /**
     * Clear all bisnis partner-related caches
     */
    private function clearBisnisPartnerCaches(): void
    {
        // Clear bisnis partner-related cache keys
        cache()->forget('bisnis_partners_active');
        cache()->forget('bisnis_partners_all');
    }
}
