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
        Schema::table('bank_masuks', function (Blueprint $table) {
            // Change nilai column from decimal(20,2) to decimal(20,5) to support up to 5 decimal places
            $table->decimal('nilai', 20, 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            // Revert back to decimal(20,2)
            $table->decimal('nilai', 20, 2)->change();
        });
    }
};
