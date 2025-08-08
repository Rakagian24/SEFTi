<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearBankCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-master {--type=all : Type of cache to clear (bank, department, role, supplier, bisnis_partner, ar_partner, user, pph, pengeluaran, perihal, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all master data caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        $this->info('Clearing master data caches...');

        switch ($type) {
            case 'bank':
                $this->clearBankCaches();
                $this->info('Bank caches cleared successfully!');
                break;
            case 'department':
                $this->clearDepartmentCaches();
                $this->info('Department caches cleared successfully!');
                break;
            case 'role':
                $this->clearRoleCaches();
                $this->info('Role caches cleared successfully!');
                break;
            case 'supplier':
                $this->clearSupplierCaches();
                $this->info('Supplier caches cleared successfully!');
                break;
            case 'bisnis_partner':
                $this->clearBisnisPartnerCaches();
                $this->info('Bisnis Partner caches cleared successfully!');
                break;
            case 'ar_partner':
                $this->clearArPartnerCaches();
                $this->info('AR Partner caches cleared successfully!');
                break;
            case 'user':
                $this->clearUserCaches();
                $this->info('User caches cleared successfully!');
                break;
            case 'pph':
                $this->clearPphCaches();
                $this->info('PPh caches cleared successfully!');
                break;
            case 'pengeluaran':
                $this->clearPengeluaranCaches();
                $this->info('Pengeluaran caches cleared successfully!');
                break;
            case 'perihal':
                $this->clearPerihalCaches();
                $this->info('Perihal caches cleared successfully!');
                break;
            case 'all':
            default:
                $this->clearAllCaches();
                $this->info('All master data caches cleared successfully!');
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Clear bank-related caches
     */
    private function clearBankCaches()
    {
        cache()->forget('banks_active_accounts');
        cache()->forget('banks_all');
        cache()->forget('banks_active');
        cache()->forget('bank_accounts_active');
    }

    /**
     * Clear department-related caches
     */
    private function clearDepartmentCaches()
    {
        cache()->forget('departments_all_list');
        cache()->forget('departments_active_accounts');
        cache()->forget('departments_active_bank_masuk');
        cache()->forget('departments_active_users');
        cache()->forget('departments_all');
    }

    /**
     * Clear role-related caches
     */
    private function clearRoleCaches()
    {
        cache()->forget('roles_active');
        cache()->forget('roles_all_list');
    }

    /**
     * Clear supplier-related caches
     */
    private function clearSupplierCaches()
    {
        cache()->forget('suppliers_active');
        cache()->forget('suppliers_all');
    }

    /**
     * Clear bisnis partner-related caches
     */
    private function clearBisnisPartnerCaches()
    {
        cache()->forget('bisnis_partners_active');
        cache()->forget('bisnis_partners_all');
    }

    /**
     * Clear ar partner-related caches
     */
    private function clearArPartnerCaches()
    {
        cache()->forget('ar_partners_active');
        cache()->forget('ar_partners_all');
    }

    /**
     * Clear user-related caches
     */
    private function clearUserCaches()
    {
        cache()->forget('users_active');
        cache()->forget('users_all');
    }

    /**
     * Clear pph-related caches
     */
    private function clearPphCaches()
    {
        cache()->forget('pphs_active');
        cache()->forget('pphs_all');
    }

    /**
     * Clear pengeluaran-related caches
     */
    private function clearPengeluaranCaches()
    {
        cache()->forget('pengeluarans_active');
        cache()->forget('pengeluarans_all');
    }

    /**
     * Clear perihal-related caches
     */
    private function clearPerihalCaches()
    {
        cache()->forget('perihals_active');
        cache()->forget('perihals_all');
    }

    /**
     * Clear all master data caches
     */
    private function clearAllCaches()
    {
        $this->clearBankCaches();
        $this->clearDepartmentCaches();
        $this->clearRoleCaches();
        $this->clearSupplierCaches();
        $this->clearBisnisPartnerCaches();
        $this->clearArPartnerCaches();
        $this->clearUserCaches();
        $this->clearPphCaches();
        $this->clearPengeluaranCaches();
        $this->clearPerihalCaches();
    }
}
