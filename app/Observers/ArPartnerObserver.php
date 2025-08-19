<?php

namespace App\Observers;

use App\Models\ArPartner;
use App\Models\ArPartnerLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArPartnerObserver
{
    /**
     * Handle the ArPartner "created" event.
     */
    public function created(ArPartner $arPartner): void
    {
        $this->logActivity($arPartner, 'created', 'Customer dibuat');
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "updated" event.
     */
    public function updated(ArPartner $arPartner): void
    {
        $this->logActivity($arPartner, 'updated', 'Customer diupdate');
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "deleted" event.
     */
    public function deleted(ArPartner $arPartner): void
    {
        $this->logActivity($arPartner, 'deleted', 'Customer dihapus');
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "restored" event.
     */
    public function restored(ArPartner $arPartner): void
    {
        $this->logActivity($arPartner, 'restored', 'Customer dipulihkan');
        $this->clearArPartnerCaches();
    }

    /**
     * Handle the ArPartner "force deleted" event.
     */
    public function forceDeleted(ArPartner $arPartner): void
    {
        $this->logActivity($arPartner, 'force_deleted', 'Customer dihapus permanen');
        $this->clearArPartnerCaches();
    }

    /**
     * Log activity to ar_partner_logs table
     */
    private function logActivity(ArPartner $arPartner, string $action, string $description): void
    {
        try {
            ArPartnerLog::create([
                'ar_partner_id' => $arPartner->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't throw it to avoid breaking the main operation
            Log::error('Failed to create ArPartner log: ' . $e->getMessage(), [
                'ar_partner_id' => $arPartner->id,
                'action' => $action,
                'user_id' => Auth::id(),
            ]);
        }
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
