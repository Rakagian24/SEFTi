<?php

namespace App\Observers;

use App\Models\Bank;

class BankObserver
{
    /**
     * Handle the Bank "created" event.
     */
    public function created(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    /**
     * Handle the Bank "updated" event.
     */
    public function updated(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    /**
     * Handle the Bank "deleted" event.
     */
    public function deleted(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    /**
     * Handle the Bank "restored" event.
     */
    public function restored(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    /**
     * Handle the Bank "force deleted" event.
     */
    public function forceDeleted(Bank $bank): void
    {
        $this->clearBankCaches();
    }

    /**
     * Clear all bank-related caches
     */
    private function clearBankCaches(): void
    {
        // Clear all bank-related cache keys
        cache()->forget('banks_active_accounts');
        cache()->forget('banks_all');
        cache()->forget('banks_active');

        // Also clear bank account caches since they depend on banks
        cache()->forget('bank_accounts_active');
        cache()->forget('departments_active_bank_masuk');
    }
}
