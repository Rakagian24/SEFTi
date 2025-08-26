<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Debugging Termin sequence generation logic...\n";

try {
    // Get BALI292 department
    $dept = \App\Models\Department::where('alias', 'BALI292')->first();
    if (!$dept) {
        echo "BALI292 department not found\n";
        exit(1);
    }

    echo "Using department: {$dept->name} (ID: {$dept->id}, Alias: {$dept->alias})\n";

    // Check ALL termins for BALI292 (including soft deleted) for current month/year
    echo "\n=== ALL TERMINS FOR CURRENT MONTH/YEAR ===\n";
    $allTermins = \App\Models\Termin::withTrashed()
        ->where('department_id', $dept->id)
        ->whereYear('created_at', date('Y'))
        ->whereMonth('created_at', date('n'))
        ->orderBy('id')
        ->get();

    foreach ($allTermins as $termin) {
        $deleted = $termin->trashed() ? ' (SOFT DELETED)' : '';
        echo "- ID: {$termin->id}, No: {$termin->no_referensi}, Status: {$termin->status}{$deleted}\n";
    }

    // Check what getLastDocumentExcludeDraft returns (this is what generateFormPreviewNumber uses)
    echo "\n=== TESTING SEQUENCE GENERATION ===\n";

    // Simulate the sequence generation logic
    $bulan = date('n');
    $tahun = date('Y');

    echo "Current month: {$bulan}, year: {$tahun}\n";

    // Get the last document for this document type, department, month, and year
    $lastDocument = \App\Models\Termin::where('department_id', $dept->id)
        ->where('status', 'active')
        ->whereNotNull('no_referensi')
        ->whereYear('created_at', $tahun)
        ->whereMonth('created_at', $bulan)
        ->orderBy('id', 'desc')
        ->first();

    if ($lastDocument) {
        echo "Last active document found: ID {$lastDocument->id}, No: {$lastDocument->no_referensi}\n";

        // Extract sequence number from last document number
        $documentNumber = $lastDocument->no_referensi;
        $parts = explode('/', $documentNumber);

        echo "Document parts: " . implode(', ', $parts) . "\n";
        echo "Parts count: " . count($parts) . "\n";

        if (count($parts) === 5) {
            $lastSequence = (int) $parts[4];
            echo "Last sequence number: {$lastSequence}\n";
            echo "Next sequence should be: " . ($lastSequence + 1) . "\n";
        }
    } else {
        echo "No last active document found - should start from sequence 1\n";
    }

    // Generate preview number
    echo "\n=== GENERATING PREVIEW NUMBER ===\n";
    $previewNumber = \App\Services\DocumentNumberService::generateFormPreviewNumber(
        'Termin',
        null,
        $dept->id,
        $dept->alias
    );

    echo "Generated preview number: {$previewNumber}\n";

    // Parse the generated number
    $parts = explode('/', $previewNumber);
    echo "Generated number parts: " . implode(', ', $parts) . "\n";
    echo "Generated sequence: " . $parts[4] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
