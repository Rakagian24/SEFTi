<?php

namespace App\Observers;

use App\Models\Pengeluaran;

class PengeluaranObserver
{
    /**
     * Handle the Pengeluaran "created" event.
     */
    public function created(Pengeluaran $pengeluaran): void
    {
        $this->clearPengeluaranCaches();
    }

    /**
     * Handle the Pengeluaran "updated" event.
     */
    public function updated(Pengeluaran $pengeluaran): void
    {
        $this->clearPengeluaranCaches();
    }

    /**
     * Handle the Pengeluaran "deleted" event.
     */
    public function deleted(Pengeluaran $pengeluaran): void
    {
        $this->clearPengeluaranCaches();
    }

    /**
     * Handle the Pengeluaran "restored" event.
     */
    public function restored(Pengeluaran $pengeluaran): void
    {
        $this->clearPengeluaranCaches();
    }

    /**
     * Handle the Pengeluaran "force deleted" event.
     */
    public function forceDeleted(Pengeluaran $pengeluaran): void
    {
        $this->clearPengeluaranCaches();
    }

    /**
     * Clear all pengeluaran-related caches
     */
    private function clearPengeluaranCaches(): void
    {
        // Clear pengeluaran-related cache keys
        cache()->forget('pengeluarans_active');
        cache()->forget('pengeluarans_all');
    }
}
