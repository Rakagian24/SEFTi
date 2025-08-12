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
            // Change decimal precision from 20,2 to 20,5 for selisih fields
            $table->decimal('selisih_penambahan', 20, 5)->nullable()->change();
            $table->decimal('selisih_pengurangan', 20, 5)->nullable()->change();
            $table->decimal('nominal_akhir', 20, 5)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            // Revert back to 20,2 precision
            $table->decimal('selisih_penambahan', 20, 2)->nullable()->change();
            $table->decimal('selisih_pengurangan', 20, 2)->nullable()->change();
            $table->decimal('nominal_akhir', 20, 2)->nullable()->change();
        });
    }
};
