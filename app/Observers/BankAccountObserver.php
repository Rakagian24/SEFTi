<?php

namespace App\Observers;

use App\Models\BankAccount;

class BankAccountObserver
{
    /**
     * Handle the BankAccount "created" event.
     */
    public function created(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    /**
     * Handle the BankAccount "updated" event.
     */
    public function updated(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    /**
     * Handle the BankAccount "deleted" event.
     */
    public function deleted(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    /**
     * Handle the BankAccount "restored" event.
     */
    public function restored(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    /**
     * Handle the BankAccount "force deleted" event.
     */
    public function forceDeleted(BankAccount $bankAccount): void
    {
        $this->clearBankAccountCaches();
    }

    /**
     * Clear all bank account related caches
     */
    private function clearBankAccountCaches(): void
    {
        // Clear bank account caches
        cache()->forget('bank_accounts_active');
        cache()->forget('departments_active_bank_masuk');
    }
}
