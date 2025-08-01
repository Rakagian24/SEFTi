<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankMasuk;

class CheckTestData extends Command
{
    protected $signature = 'check:test-data';
    protected $description = 'Check if test data exists in the database';

    public function handle()
    {
        $this->info('Checking test data...');

        $testData = BankMasuk::where('no_bm', 'like', 'BM/Test%')->get();
        $this->info("Found {$testData->count()} test records");

        if ($testData->count() > 0) {
            $this->info('Test data details:');
            foreach ($testData as $item) {
                $this->info("  - {$item->no_bm} - {$item->tanggal} - {$item->nilai}");
            }
        } else {
            $this->warn('No test data found. Please run: php artisan db:seed --class=AutoMatchSeeder');
        }

        return 0;
    }
}
