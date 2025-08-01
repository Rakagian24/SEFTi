<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            // Change sj_nilai and bm_nilai columns to support up to 5 decimal places
            $table->double('sj_nilai', 20, 5)->change();
            $table->double('bm_nilai', 20, 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            // Revert back to 2 decimal places
            $table->double('sj_nilai', 20, 2)->change();
            $table->double('bm_nilai', 20, 2)->change();
        });
    }
};
