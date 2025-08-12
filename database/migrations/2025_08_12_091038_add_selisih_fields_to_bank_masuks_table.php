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
            $table->decimal('selisih_penambahan', 20, 2)->nullable()->after('nilai');
            $table->decimal('selisih_pengurangan', 20, 2)->nullable()->after('selisih_penambahan');
            $table->decimal('nominal_akhir', 20, 2)->nullable()->after('selisih_pengurangan');

            // Add indexes for better performance
            $table->index(['selisih_penambahan']);
            $table->index(['selisih_pengurangan']);
            $table->index(['nominal_akhir']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->dropIndex(['selisih_penambahan']);
            $table->dropIndex(['selisih_pengurangan']);
            $table->dropIndex(['nominal_akhir']);

            $table->dropColumn(['selisih_penambahan', 'selisih_pengurangan', 'nominal_akhir']);
        });
    }
};
