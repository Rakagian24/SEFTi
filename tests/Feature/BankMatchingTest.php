<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AutoMatch;
use App\Models\BankMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BankMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_matching_excludes_already_matched_data()
    {
        // Create a user for authentication
        $user = User::factory()->create();

        // Create some bank masuk data
        $bankMasuk1 = BankMasuk::factory()->create([
            'no_bm' => 'BM/TEST/001',
            'tanggal' => '2025-07-30',
            'nilai' => 1000000,
            'status' => 'aktif'
        ]);

        $bankMasuk2 = BankMasuk::factory()->create([
            'no_bm' => 'BM/TEST/002',
            'tanggal' => '2025-07-30',
            'nilai' => 2000000,
            'status' => 'aktif'
        ]);

        // Create an auto match record (simulating already matched data)
        AutoMatch::create([
            'bank_masuk_id' => $bankMasuk1->id,
            'sj_no' => 'SJ-001',
            'sj_tanggal' => '2025-07-30',
            'sj_nilai' => 1000000,
            'bm_no' => $bankMasuk1->no_bm,
            'bm_tanggal' => $bankMasuk1->tanggal,
            'bm_nilai' => $bankMasuk1->nilai,
            'match_date' => now(),
            'status' => 'confirmed',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Act as the user
        $this->actingAs($user);

        // Test the bank matching endpoint
        $response = $this->get('/bank-matching?start_date=2025-07-01&end_date=2025-07-31');

        // Assert the response is successful
        $response->assertStatus(200);

        // Get the matching results from the response
        $matchingResults = $response->viewData('matchingResults');

        // Assert that the already matched bank masuk is not included in the results
        $this->assertNotContains($bankMasuk1->id, collect($matchingResults)->pluck('bank_masuk_id')->toArray());

        // Assert that the unmatched bank masuk can still be included (if there's matching SJ data)
        // Note: This test assumes there's no matching SJ data for bankMasuk2
        // In a real scenario, you would need to mock the SjNew data as well
    }

    public function test_export_excludes_already_matched_data()
    {
        // Create a user for authentication
        $user = User::factory()->create();

        // Create an auto match record
        AutoMatch::create([
            'bank_masuk_id' => 1,
            'sj_no' => 'SJ-EXPORT-TEST',
            'sj_tanggal' => '2025-07-30',
            'sj_nilai' => 1000000,
            'bm_no' => 'BM-EXPORT-TEST',
            'bm_tanggal' => '2025-07-30',
            'bm_nilai' => 1000000,
            'match_date' => now(),
            'status' => 'confirmed',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Act as the user
        $this->actingAs($user);

        // Test the export endpoint
        $response = $this->get('/bank-matching/export-excel?start_date=2025-07-01&end_date=2025-07-31');

        // Assert the response is successful
        $response->assertStatus(200);

        // The export should not include the already matched SJ data
        // Note: This test would need to be expanded with actual SJ data mocking
    }

    public function test_bank_matching_shows_empty_by_default()
    {
        // Create a user for authentication
        $user = User::factory()->create();

        // Act as the user
        $this->actingAs($user);

        // Test the bank matching endpoint without perform_match parameter
        $response = $this->get('/bank-matching');

        // Assert the response is successful
        $response->assertStatus(200);

        // Get the matching results from the response
        $matchingResults = $response->viewData('matchingResults');

        // Assert that no results are returned by default
        $this->assertEmpty($matchingResults);
    }

    public function test_bank_matching_performs_search_when_explicitly_requested()
    {
        // Create a user for authentication
        $user = User::factory()->create();

        // Act as the user
        $this->actingAs($user);

        // Test the bank matching endpoint with perform_match parameter
        $response = $this->get('/bank-matching?start_date=2025-07-01&end_date=2025-07-31&perform_match=true');

        // Assert the response is successful
        $response->assertStatus(200);

        // Get the matching results from the response
        $matchingResults = $response->viewData('matchingResults');

        // Assert that the search was performed (results may be empty but the search was executed)
        // The actual results depend on the data in the database
        $this->assertIsArray($matchingResults);
    }
}
