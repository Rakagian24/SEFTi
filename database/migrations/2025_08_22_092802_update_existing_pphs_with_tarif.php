<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing PPH records with proper tarif_pph values
        DB::table('pphs')->where('kode_pph', '21')->update(['tarif_pph' => 5.0]);
        DB::table('pphs')->where('kode_pph', '22')->update(['tarif_pph' => 2.5]);
        DB::table('pphs')->where('kode_pph', '23')->update(['tarif_pph' => 15.0]);
        DB::table('pphs')->where('kode_pph', '25')->update(['tarif_pph' => 10.0]);

        // For any other PPH records that might not have tarif_pph, set a default value
        DB::table('pphs')->whereNull('tarif_pph')->update(['tarif_pph' => 0.0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset tarif_pph to null for the records we updated
        DB::table('pphs')->whereIn('kode_pph', ['21', '22', '23', '25'])->update(['tarif_pph' => null]);
    }
};
