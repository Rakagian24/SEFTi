<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SjNew;
use App\Models\BankMasuk;
use App\Http\Controllers\BankMatchingController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestSjNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sj-new {--start-date=} {--end-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SjNew model and bank matching functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->option('end-date') ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->info("Testing SjNew model with date range: {$startDate} to {$endDate}");

        try {
            // Test SjNew data retrieval
            $this->info("\n1. Testing SjNew data retrieval...");
            $sjNewList = SjNew::byDateRange($startDate, $endDate)
                ->orderBy('date')
                ->orderBy('doc_number')
                ->get();

            $this->info("Found {$sjNewList->count()} SjNew records");

            if ($sjNewList->count() > 0) {
                $this->info("Sample SjNew data:");
                $sample = $sjNewList->first();
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['doc_number', $sample->getDocNumber()],
                        ['date', $sample->getDateValue()],
                        ['total', $sample->getTotalValue()],
                        ['name', $sample->getCustomerName()],
                        ['kontrabon', $sample->getKontrabonValue()],
                        ['currency', $sample->getCurrency()],
                    ]
                );
            }

            // Test BankMasuk data retrieval
            $this->info("\n2. Testing BankMasuk data retrieval...");
            $bankMasukList = BankMasuk::where('status', 'aktif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();

            $this->info("Found {$bankMasukList->count()} BankMasuk records");

            if ($bankMasukList->count() > 0) {
                $this->info("Sample BankMasuk data:");
                $sample = $bankMasukList->first();
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['id', $sample->id],
                        ['no_bm', $sample->no_bm],
                        ['tanggal', $sample->tanggal],
                        ['nilai', $sample->nilai],
                    ]
                );
            }

            // Test bank matching functionality
            $this->info("\n3. Testing bank matching functionality...");
            $controller = new BankMatchingController();
            $matchingResults = $controller->performBankMatching($sjNewList, $bankMasukList);

            $this->info("Found " . count($matchingResults) . " matching results");

            if (count($matchingResults) > 0) {
                $this->info("Sample matching result:");
                $sample = $matchingResults[0];
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['no_invoice', $sample['no_invoice']],
                        ['tanggal_invoice', $sample['tanggal_invoice']],
                        ['nilai_invoice', $sample['nilai_invoice']],
                        ['customer_name', $sample['customer_name']],
                        ['no_bank_masuk', $sample['no_bank_masuk']],
                        ['tanggal_bank_masuk', $sample['tanggal_bank_masuk']],
                        ['nilai_bank_masuk', $sample['nilai_bank_masuk']],
                        ['is_matched', $sample['is_matched'] ? 'Yes' : 'No'],
                    ]
                );
            }

            // Test export data preparation
            $this->info("\n4. Testing export data preparation (UNMATCHED ONLY)...");

            // Filter hanya data yang belum dimatch
            $matchedSjNumbers = collect($matchingResults)->pluck('no_invoice')->toArray();
            $unmatchedSjNewData = collect($sjNewList)->filter(function($sjNew) use ($matchedSjNumbers) {
                return !in_array($sjNew->getDocNumber(), $matchedSjNumbers);
            })->map(function($sjNew) {
                return [
                    'no_invoice' => $sjNew->getDocNumber(),
                    'tanggal_invoice' => $sjNew->getDateValue() ? Carbon::parse($sjNew->getDateValue())->format('Y-m-d') : null,
                    'nilai_invoice' => (float) $sjNew->getTotalValue(),
                    'customer_name' => $sjNew->getCustomerName(),
                    'kontrabon' => $sjNew->getKontrabonValue(),
                    'currency' => $sjNew->getCurrency(),
                    'status_match' => 'Belum Dimatch'
                ];
            });

            $this->info("Prepared {$unmatchedSjNewData->count()} UNMATCHED records for export");
            $this->info("Total SJ New records: " . $sjNewList->count());
            $this->info("Matched records: " . count($matchingResults));
            $this->info("Unmatched records for export: " . $unmatchedSjNewData->count());

            if ($unmatchedSjNewData->count() > 0) {
                $this->info("Sample unmatched export data:");
                $sample = $unmatchedSjNewData->first();
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['no_invoice', $sample['no_invoice']],
                        ['tanggal_invoice', $sample['tanggal_invoice']],
                        ['nilai_invoice (raw)', $sample['nilai_invoice']],
                        ['nilai_invoice (type)', gettype($sample['nilai_invoice'])],
                        ['customer_name', $sample['customer_name']],
                        ['kontrabon', $sample['kontrabon']],
                        ['currency', $sample['currency']],
                        ['status_match', $sample['status_match']],
                    ]
                );

                // Test multiple samples to verify numeric format
                $this->info("Testing numeric format consistency:");
                $samples = $unmatchedSjNewData->take(5);
                foreach ($samples as $index => $sample) {
                    $this->info("Sample " . ($index + 1) . ": nilai_invoice = " . $sample['nilai_invoice'] . " (type: " . gettype($sample['nilai_invoice']) . ")");
                }

                // Test actual Excel export
                $this->info("\n5. Testing actual Excel export with formatting...");
                try {
                    $filename = "test_export_" . date('Y-m-d_H-i-s') . ".xlsx";
                    $export = new \App\Exports\BankMatchingExport($unmatchedSjNewData->take(10));
                    \Maatwebsite\Excel\Facades\Excel::store($export, $filename, 'public');

                    $this->info("✅ Excel file created successfully: {$filename}");
                    $this->info("File location: storage/app/public/{$filename}");
                    $this->info("Note: Check the Excel file to verify column C (Nilai Invoice) has currency formatting without decimals");

                } catch (\Exception $e) {
                    $this->error("❌ Excel export test failed: " . $e->getMessage());
                }
            }

            $this->info("\n✅ All tests completed successfully!");

        } catch (\Exception $e) {
            $this->error("❌ Error during testing: " . $e->getMessage());
            Log::error('TestSjNew command error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
}
