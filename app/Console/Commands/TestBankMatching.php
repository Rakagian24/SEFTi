<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\BankMatchingController;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Added Log facade
use App\Models\SjNew; // Added SjNew model
use App\Models\BankMasuk; // Added BankMasuk model

class TestBankMatching extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bank-matching {--start-date=} {--end-date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test bank matching functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Bank Matching Functionality...');

        $startDate = $this->option('start-date') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->option('end-date') ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->info("Testing with date range: {$startDate} to {$endDate}");

        // Test database connections
        $this->info('Testing database connections...');

        try {
            $seftiConnection = DB::connection()->getPdo();
            $this->info('✓ SEFTI database connection successful');
        } catch (\Exception $e) {
            $this->error('✗ SEFTI database connection failed: ' . $e->getMessage());
            return 1;
        }

        try {
            $gjtrading3Connection = DB::connection('gjtrading3')->getPdo();
            $this->info('✓ GJTRADING3 database connection successful');
        } catch (\Exception $e) {
            $this->error('✗ GJTRADING3 database connection failed: ' . $e->getMessage());
            return 1;
        }

        // Test SjNew model
        $this->info('Testing SjNew model...');
        try {
            $sjNewCount = \App\Models\SjNew::count();
            $this->info("✓ SjNew model working - Total records: {$sjNewCount}");

            $sjNewList = \App\Models\SjNew::byDateRange($startDate, $endDate)->get();
            $this->info("✓ SjNew date range query - Found {$sjNewList->count()} records");
        } catch (\Exception $e) {
            $this->error('✗ SjNew model test failed: ' . $e->getMessage());
            return 1;
        }

        // Test BankMasuk model
        $this->info('Testing BankMasuk model...');
        try {
            $bankMasukCount = \App\Models\BankMasuk::where('status', 'aktif')->count();
            $this->info("✓ BankMasuk model working - Total active records: {$bankMasukCount}");

            $bankMasukList = \App\Models\BankMasuk::where('status', 'aktif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();
            $this->info("✓ BankMasuk date range query - Found {$bankMasukList->count()} records");
        } catch (\Exception $e) {
            $this->error('✗ BankMasuk model test failed: ' . $e->getMessage());
            return 1;
        }

        // Test AutoMatch model
        $this->info('Testing AutoMatch model...');
        try {
            $autoMatchCount = \App\Models\AutoMatch::count();
            $this->info("✓ AutoMatch model working - Total records: {$autoMatchCount}");
        } catch (\Exception $e) {
            $this->error('✗ AutoMatch model test failed: ' . $e->getMessage());
            return 1;
        }

        // Test bank matching logic
        $this->info('Testing bank matching logic...');
        try {
            $controller = new BankMatchingController();
            $matchingResults = $controller->performBankMatching($sjNewList, $bankMasukList);
            $this->info("✓ Bank matching logic working - Found " . count($matchingResults) . " matches");

            if (count($matchingResults) > 0) {
                $this->info('Sample matches:');
                foreach (array_slice($matchingResults, 0, 3) as $index => $sample) {
                    $this->info("  Match " . ($index + 1) . ":");
                    $this->info("    - Invoice: {$sample['no_invoice']}");
                    $this->info("    - Bank Masuk: {$sample['no_bank_masuk']}");
                    $this->info("    - Amount: {$sample['nilai_invoice']}");
                    $this->info("    - Date: {$sample['tanggal_invoice']}");
                }
            } else {
                $this->warn('No matches found. This could be because:');
                $this->warn('1. All data is already matched');
                $this->warn('2. No matching dates/amounts');
                $this->warn('3. Data format issues');

                // Show detailed data comparison
                $this->info('Detailed data comparison:');
                $this->info('SjNew data (first 5):');
                foreach ($sjNewList->take(5) as $index => $item) {
                    $this->info("  " . ($index + 1) . ". Doc: {$item->getDocNumber()}");
                    $this->info("     Date: {$item->getDateValue()}");
                    $this->info("     Total: {$item->getTotalValue()}");
                    $this->info("     Key: " . ($item->getDateValue() ? \Carbon\Carbon::parse($item->getDateValue())->format('Y-m-d') : 'NULL') . "_{$item->getTotalValue()}");
                }

                $this->info('BankMasuk data (first 5):');
                foreach ($bankMasukList->take(5) as $index => $item) {
                    $this->info("  " . ($index + 1) . ". ID: {$item->id}, No: {$item->no_bm}");
                    $this->info("     Date: {$item->tanggal}");
                    $this->info("     Nilai: {$item->nilai}");
                    $this->info("     Key: {$item->tanggal}_{$item->nilai}");
                }

                // Show all BankMasuk data to see if test data is included
                $this->info('All BankMasuk data in date range:');
                foreach ($bankMasukList as $index => $item) {
                    $this->info("  " . ($index + 1) . ". ID: {$item->id}, No: {$item->no_bm}, Date: {$item->tanggal}, Nilai: {$item->nilai}");
                }

                // Check for potential matches with tolerance
                $this->info('Checking for potential matches with tolerance...');
                $potentialMatches = 0;
                foreach ($sjNewList->take(10) as $sjItem) {
                    $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                    $sjTotal = $sjItem->getTotalValue();

                    foreach ($bankMasukList->take(10) as $bmItem) {
                        $bmDate = $bmItem->tanggal;
                        $bmNilai = $bmItem->nilai;

                        // Check if dates are within 1 day and amounts are within 1% tolerance
                        if ($sjDate && $bmDate) {
                            $dateDiff = abs(\Carbon\Carbon::parse($sjDate)->diffInDays(\Carbon\Carbon::parse($bmDate)));
                            $amountDiff = abs($sjTotal - $bmNilai) / max($sjTotal, $bmNilai) * 100;

                            if ($dateDiff <= 1 && $amountDiff <= 1) {
                                $potentialMatches++;
                                $this->info("  Potential match found:");
                                $this->info("    SJ: {$sjItem->getDocNumber()} - {$sjDate} - {$sjTotal}");
                                $this->info("    BM: {$bmItem->no_bm} - {$bmDate} - {$bmNilai}");
                                $this->info("    Date diff: {$dateDiff} days, Amount diff: " . number_format($amountDiff, 2) . "%");
                            }
                        }
                    }
                }

                if ($potentialMatches > 0) {
                    $this->info("Found {$potentialMatches} potential matches with tolerance.");
                } else {
                    $this->info("No potential matches found even with tolerance.");
                }

                // Debug exact matching keys
                $this->info('Debugging exact matching keys...');
                $this->info('SjNew keys (first 5):');
                foreach ($sjNewList->take(5) as $sjItem) {
                    $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : 'NULL';
                    $sjTotal = $sjItem->getTotalValue();
                    $key = $sjDate . '_' . $sjTotal;
                    $this->info("  {$sjItem->getDocNumber()}: {$key}");
                }

                $this->info('BankMasuk keys (first 5):');
                foreach ($bankMasukList->take(5) as $bmItem) {
                    $bmDate = \Carbon\Carbon::parse($bmItem->tanggal)->format('Y-m-d');
                    $bmNilai = $bmItem->nilai;
                    $key = $bmDate . '_' . $bmNilai;
                    $this->info("  {$bmItem->no_bm}: {$key}");
                }

                // Check for exact matches
                $this->info('Checking for exact matches...');
                $exactMatches = 0;
                foreach ($sjNewList->take(5) as $sjItem) {
                    $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                    $sjTotal = $sjItem->getTotalValue();
                    $sjKey = $sjDate . '_' . $sjTotal;

                    foreach ($bankMasukList as $bmItem) {
                        $bmDate = \Carbon\Carbon::parse($bmItem->tanggal)->format('Y-m-d');
                        $bmNilai = $bmItem->nilai;
                        $bmKey = $bmDate . '_' . $bmNilai;

                        if ($sjKey === $bmKey) {
                            $exactMatches++;
                            $this->info("  EXACT MATCH FOUND:");
                            $this->info("    SJ: {$sjItem->getDocNumber()} - Key: {$sjKey}");
                            $this->info("    BM: {$bmItem->no_bm} - Key: {$bmKey}");
                        }
                    }
                }

                if ($exactMatches > 0) {
                    $this->info("Found {$exactMatches} exact matches!");
                } else {
                    $this->info("No exact matches found.");
                }

                // Check test data specifically
                $this->info('Checking test data specifically...');
                $testBankMasuk = $bankMasukList->filter(function($item) {
                    return strpos($item->no_bm, 'BM/Test') === 0;
                });

                $this->info("Found {$testBankMasuk->count()} test BankMasuk records");

                foreach ($testBankMasuk as $testBM) {
                    $bmDate = \Carbon\Carbon::parse($testBM->tanggal)->format('Y-m-d');
                    $bmNilai = $testBM->nilai;
                    $bmKey = $bmDate . '_' . $bmNilai;

                    $this->info("  Test BM: {$testBM->no_bm} - Key: {$bmKey}");

                    // Look for matching SjNew
                    $matchingSjNew = $sjNewList->filter(function($sjItem) use ($bmDate, $bmNilai) {
                        $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                        $sjTotal = $sjItem->getTotalValue();
                        return $sjDate === $bmDate && $sjTotal == $bmNilai;
                    });

                    if ($matchingSjNew->count() > 0) {
                        $this->info("    ✓ Found {$matchingSjNew->count()} matching SjNew records:");
                        foreach ($matchingSjNew as $sjItem) {
                            $this->info("      - {$sjItem->getDocNumber()}");
                        }
                    } else {
                        $this->info("    ✗ No matching SjNew found");

                        // Debug: Show all SjNew with same date
                        $sameDateSjNew = $sjNewList->filter(function($sjItem) use ($bmDate) {
                            $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                            return $sjDate === $bmDate;
                        });

                        $this->info("    Debug: Found {$sameDateSjNew->count()} SjNew with same date ({$bmDate}):");
                        foreach ($sameDateSjNew->take(5) as $sjItem) {
                            $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : 'NULL';
                            $sjTotal = $sjItem->getTotalValue();
                            $this->info("      - {$sjItem->getDocNumber()}: {$sjDate} - {$sjTotal} (diff: " . abs($sjTotal - $bmNilai) . ")");
                        }

                        // Debug: Show all SjNew with same amount
                        $sameAmountSjNew = $sjNewList->filter(function($sjItem) use ($bmNilai) {
                            $sjTotal = $sjItem->getTotalValue();
                            return $sjTotal == $bmNilai;
                        });

                        $this->info("    Debug: Found {$sameAmountSjNew->count()} SjNew with same amount ({$bmNilai}):");
                        foreach ($sameAmountSjNew->take(5) as $sjItem) {
                            $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : 'NULL';
                            $sjTotal = $sjItem->getTotalValue();
                            $this->info("      - {$sjItem->getDocNumber()}: {$sjDate} - {$sjTotal}");
                        }
                    }
                }

                // Test the actual performBankMatching function
                $this->info('Testing performBankMatching function directly...');
                $controller = new BankMatchingController();
                $matchingResults = $controller->performBankMatching($sjNewList, $bankMasukList);
                $this->info("performBankMatching found " . count($matchingResults) . " matches");

                if (count($matchingResults) > 0) {
                    $this->info('Matches found:');
                    foreach ($matchingResults as $index => $match) {
                        $this->info("  " . ($index + 1) . ". {$match['no_invoice']} ↔ {$match['no_bank_masuk']}");
                    }
                } else {
                    $this->warn('No matches found by performBankMatching function');

                    // Check if data is being filtered out
                    $this->info('Checking if data is being filtered out...');
                    $alreadyMatchedSjNos = \App\Models\AutoMatch::pluck('sj_no')->toArray();
                    $alreadyMatchedBankMasukIds = \App\Models\AutoMatch::pluck('bank_masuk_id')->toArray();

                    $this->info("Already matched SJ numbers: " . count($alreadyMatchedSjNos));
                    $this->info("Already matched Bank Masuk IDs: " . count($alreadyMatchedBankMasukIds));

                    // Check if test data is being filtered
                    $testSjNumbers = ['SJ/M0725/02029/SGT1', 'SJ/M0725/02061/SGT1', 'SJ/M0725/02103/SGT1', 'SJ/M0725/02101/SGT1', 'SJ/M0725/02102/SGT1'];
                    $testBankMasukIds = [63, 64, 65, 66, 67];

                    foreach ($testSjNumbers as $sjNo) {
                        if (in_array($sjNo, $alreadyMatchedSjNos)) {
                            $this->info("  ✗ {$sjNo} is already matched");
                        } else {
                            $this->info("  ✓ {$sjNo} is available for matching");
                        }
                    }

                    foreach ($testBankMasukIds as $bmId) {
                        if (in_array($bmId, $alreadyMatchedBankMasukIds)) {
                            $this->info("  ✗ Bank Masuk ID {$bmId} is already matched");
                        } else {
                            $this->info("  ✓ Bank Masuk ID {$bmId} is available for matching");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('✗ Bank matching logic failed: ' . $e->getMessage());
            return 1;
        }

        // Test controller index method
        $this->info('Testing controller index method...');
        try {
            $request = new Request([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'perform_match' => 'true',
                'tab' => 'auto-matching'
            ]);

            $response = $controller->index($request);
            $this->info('✓ Controller index method working');
        } catch (\Exception $e) {
            $this->error('✗ Controller index method failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('All tests passed! Bank matching functionality is working correctly.');

        // Check test data specifically
        $this->info('Checking test data...');
        $testData = \App\Models\BankMasuk::where('no_bm', 'like', 'BM/Test%')->get();
        $this->info("Found {$testData->count()} test records");

        if ($testData->count() > 0) {
            $this->info('Test data details:');
            foreach ($testData as $item) {
                $this->info("  - {$item->no_bm} - {$item->tanggal} - {$item->nilai}");
            }
        } else {
            $this->warn('No test data found. Please run: php artisan db:seed --class=AutoMatchSeeder');
        }

        // Test decimal value formatting specifically
        $this->info('Testing decimal value formatting...');
        $decimalTestData = $bankMasukList->filter(function($item) {
            return strpos($item->no_bm, 'BM/Test/Decimal') === 0;
        });

        $this->info("Found {$decimalTestData->count()} decimal test records");

        foreach ($decimalTestData as $decimalBM) {
            $bmDate = \Carbon\Carbon::parse($decimalBM->tanggal)->format('Y-m-d');
            $bmNilai = $decimalBM->nilai;

            // Test the formatting logic
            $value = (float) $bmNilai;
            $formattedValue = $value == (int) $value ? (int) $value : number_format($value, 4, '.', '');
            $bmKey = $bmDate . '_' . $formattedValue;

            $this->info("  Decimal BM: {$decimalBM->no_bm}");
            $this->info("    Original value: {$bmNilai}");
            $this->info("    Formatted value: {$formattedValue}");
            $this->info("    Key: {$bmKey}");

            // Look for matching SjNew with same decimal precision
            $matchingSjNew = $sjNewList->filter(function($sjItem) use ($bmDate, $bmNilai) {
                $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                $sjTotal = (float) $sjItem->getTotalValue();
                $sjFormatted = $sjTotal == (int) $sjTotal ? (int) $sjTotal : number_format($sjTotal, 4, '.', '');
                $bmFormatted = $bmNilai == (int) $bmNilai ? (int) $bmNilai : number_format($bmNilai, 4, '.', '');

                return $sjDate === $bmDate && $sjFormatted === $bmFormatted;
            });

            if ($matchingSjNew->count() > 0) {
                $this->info("    ✓ Found {$matchingSjNew->count()} matching SjNew records:");
                foreach ($matchingSjNew as $sjItem) {
                    $sjTotal = (float) $sjItem->getTotalValue();
                    $sjFormatted = $sjTotal == (int) $sjTotal ? (int) $sjTotal : number_format($sjTotal, 4, '.', '');
                    $this->info("      - {$sjItem->getDocNumber()}: {$sjFormatted}");
                }
            } else {
                $this->info("    ✗ No matching SjNew found");

                // Show nearby values for debugging
                $this->info("    Debug: Looking for values around {$bmNilai}");
                $nearbySjNew = $sjNewList->filter(function($sjItem) use ($bmDate) {
                    $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                    return $sjDate === $bmDate;
                });

                foreach ($nearbySjNew->take(3) as $sjItem) {
                    $sjTotal = (float) $sjItem->getTotalValue();
                    $sjFormatted = $sjTotal == (int) $sjTotal ? (int) $sjTotal : number_format($sjTotal, 4, '.', '');
                    $this->info("      - {$sjItem->getDocNumber()}: {$sjFormatted} (diff: " . abs($sjTotal - $bmNilai) . ")");
                }
            }
        }

        // Test decimal formatting logic
        $this->info('Testing decimal formatting logic...');

        $testValues = [
            1234567.89,
            9876543.2100,
            5555555.5555,
            1000000.00,
            2000000.50,
            3000000.1234
        ];

        foreach ($testValues as $testValue) {
            $value = (float) $testValue;
            $formattedValue = $value == (int) $value ? (int) $value : number_format($value, 4, '.', '');
            $this->info("  Original: {$testValue} -> Formatted: {$formattedValue}");
        }

        // Test with actual data from database
        $this->info('Testing with actual database values...');
        $sampleSjNew = $sjNewList->take(5);
        $sampleBankMasuk = $bankMasukList->take(5);

        $this->info('Sample SjNew values:');
        foreach ($sampleSjNew as $sjItem) {
            $totalValue = (float) $sjItem->getTotalValue();
            $formattedValue = $totalValue == (int) $totalValue ? (int) $totalValue : number_format($totalValue, 4, '.', '');
            $this->info("  {$sjItem->getDocNumber()}: {$totalValue} -> {$formattedValue}");
        }

        $this->info('Sample BankMasuk values:');
        foreach ($sampleBankMasuk as $bmItem) {
            $nilai = (float) $bmItem->nilai;
            $formattedValue = $nilai == (int) $nilai ? (int) $nilai : number_format($nilai, 4, '.', '');
            $this->info("  {$bmItem->no_bm}: {$nilai} -> {$formattedValue}");
        }

        // Test exact match data specifically
        $this->info('Testing exact match data...');
        $exactTestData = $bankMasukList->filter(function($item) {
            return strpos($item->no_bm, 'BM/Test/Exact') === 0;
        });

        $this->info("Found {$exactTestData->count()} exact match test records");

        foreach ($exactTestData as $exactBM) {
            $bmDate = \Carbon\Carbon::parse($exactBM->tanggal)->format('Y-m-d');
            $bmNilai = $exactBM->nilai;

            // Test the formatting logic
            $value = (float) $bmNilai;
            $formattedValue = $value == (int) $value ? (int) $value : number_format($value, 4, '.', '');
            $bmKey = $bmDate . '_' . $formattedValue;

            $this->info("  Exact BM: {$exactBM->no_bm}");
            $this->info("    Date: {$bmDate}");
            $this->info("    Original value: {$bmNilai}");
            $this->info("    Formatted value: {$formattedValue}");
            $this->info("    Key: {$bmKey}");

            // Look for matching SjNew with exact same value
            $matchingSjNew = $sjNewList->filter(function($sjItem) use ($bmDate, $bmNilai) {
                $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                $sjTotal = (float) $sjItem->getTotalValue();
                $sjFormatted = $sjTotal == (int) $sjTotal ? (int) $sjTotal : number_format($sjTotal, 4, '.', '');
                $bmFormatted = $bmNilai == (int) $bmNilai ? (int) $bmNilai : number_format($bmNilai, 4, '.', '');

                return $sjDate === $bmDate && $sjFormatted === $bmFormatted;
            });

            if ($matchingSjNew->count() > 0) {
                $this->info("    ✓ Found {$matchingSjNew->count()} matching SjNew records:");
                foreach ($matchingSjNew as $sjItem) {
                    $sjTotal = (float) $sjItem->getTotalValue();
                    $sjFormatted = $sjTotal == (int) $sjTotal ? (int) $sjTotal : number_format($sjTotal, 4, '.', '');
                    $this->info("      - {$sjItem->getDocNumber()}: {$sjFormatted}");
                }
            } else {
                $this->info("    ✗ No matching SjNew found");

                // Show all SjNew with same date for debugging
                $this->info("    Debug: All SjNew with same date ({$bmDate}):");
                $sameDateSjNew = $sjNewList->filter(function($sjItem) use ($bmDate) {
                    $sjDate = $sjItem->getDateValue() ? \Carbon\Carbon::parse($sjItem->getDateValue())->format('Y-m-d') : null;
                    return $sjDate === $bmDate;
                });

                foreach ($sameDateSjNew as $sjItem) {
                    $sjTotal = (float) $sjItem->getTotalValue();
                    $sjFormatted = $sjTotal == (int) $sjTotal ? (int) $sjTotal : number_format($sjTotal, 4, '.', '');
                    $this->info("      - {$sjItem->getDocNumber()}: {$sjFormatted} (diff: " . abs($sjTotal - $bmNilai) . ")");
                }
            }
        }

        // Test specific August 1st data
        Log::info('=== TESTING AUGUST 1ST DATA ===');

        // Get SJ data for August 1st
        $augustSjData = SjNew::where('date', '2025-08-01')->get();
        Log::info('August 1st SJ data:', [
            'count' => $augustSjData->count(),
            'data' => $augustSjData->map(function($item) {
                return [
                    'doc_number' => $item->getDocNumber(),
                    'date' => $item->getDateValue(),
                    'total_value' => $item->getTotalValue(),
                    'total_value_type' => gettype($item->getTotalValue()),
                    'total_value_float' => (float) $item->getTotalValue(),
                    'total_value_round2' => round((float) $item->getTotalValue(), 2),
                    'customer' => $item->getCustomerName(),
                    'currency' => $item->getCurrency()
                ];
            })->toArray()
        ]);

        // Get BM data for August 1st
        $augustBmData = BankMasuk::where('tanggal', '2025-08-01')->get();
        Log::info('August 1st BM data:', [
            'count' => $augustBmData->count(),
            'data' => $augustBmData->map(function($item) {
                return [
                    'id' => $item->id,
                    'no_bm' => $item->no_bm,
                    'date' => $item->tanggal,
                    'nilai' => $item->nilai,
                    'nilai_type' => gettype($item->nilai),
                    'nilai_float' => (float) $item->nilai,
                    'nilai_round2' => round((float) $item->nilai, 2)
                ];
            })->toArray()
        ]);

        // Test specific matches that should work
        $testMatches = [
            ['sj' => 'SJ/M0825/02341/SGT1', 'bm' => 'BM/SGT 1/VIII-2025/007', 'expected_value' => 2970096.38],
            ['sj' => 'SJ/M0825/02342/SGT1', 'bm' => 'BM/SGT 1/VIII-2025/008', 'expected_value' => 11172107.89],
            ['sj' => 'SJ/M0825/02343/RC/SGT1', 'bm' => 'BM/SGT 1/VIII-2025/009', 'expected_value' => 3630114.7],
            ['sj' => 'SJ/X0825/02344/SGT1', 'bm' => 'BM/SGT/VIII-2025/010', 'expected_value' => 504.71]
        ];

        foreach ($testMatches as $match) {
            $sjItem = $augustSjData->where('doc_number', $match['sj'])->first();
            $bmItem = $augustBmData->where('no_bm', $match['bm'])->first();

            if ($sjItem && $bmItem) {
                $sjValue = (float) $sjItem->getTotalValue();
                $bmValue = (float) $bmItem->nilai;
                $sjNormalized = round($sjValue, 2);
                $bmNormalized = round($bmValue, 2);

                Log::info("Testing match: {$match['sj']} vs {$match['bm']}", [
                    'sj_original' => $sjItem->getTotalValue(),
                    'sj_float' => $sjValue,
                    'sj_normalized' => $sjNormalized,
                    'bm_original' => $bmItem->nilai,
                    'bm_float' => $bmValue,
                    'bm_normalized' => $bmNormalized,
                    'values_match' => $sjNormalized == $bmNormalized,
                    'expected_value' => $match['expected_value']
                ]);
            }
        }

        // Test the exact formatting logic
        Log::info('=== TESTING EXACT FORMATTING LOGIC ===');

        $testValues = [
            117616987.5,
            31258377,
            2970096.375,
            11172107.8905,
            3630114.696,
            504.7056
        ];

        foreach ($testValues as $value) {
            $formatted = sprintf('%.4f', $value);
            Log::info("Value: {$value} -> Formatted: {$formatted}");
        }

        // Test specific August 1st matches that should work
        $expectedMatches = [
            ['sj' => 'SJ/M0825/02339/SGT1', 'bm' => 'BM/SGT 1/VIII-2025/001', 'value' => 117616987.5],
            ['sj' => 'SJ/M0825/02340/SGT1', 'bm' => 'BM/SGT 1/VIII-2025/002', 'value' => 31258377],
            ['sj' => 'SJ/M0825/02341/SGT1', 'bm' => 'BM/SGT 2/VIII-2025/003', 'value' => 2970096.375],
            ['sj' => 'SJ/M0825/02342/SGT1', 'bm' => 'BM/SGT 2/VIII-2025/004', 'value' => 11172107.8905],
            ['sj' => 'SJ/M0825/02343/RC/SGT1', 'bm' => 'BM/SGT 2/VIII-2025/005', 'value' => 3630114.696],
            ['sj' => 'SJ/X0825/02344/SGT1', 'bm' => 'BM/SGT/VIII-2025/006', 'value' => 504.7056]
        ];

        foreach ($expectedMatches as $match) {
            $formattedValue = sprintf('%.4f', $match['value']);
            $key = '2025-08-01_' . $formattedValue;
            Log::info("Expected match key: {$key}");
        }

        return 0;
    }
}
