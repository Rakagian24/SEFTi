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
        // Check if cicilan column exists, if not add it
        if (!Schema::hasColumn('memo_pembayarans', 'cicilan')) {
            Schema::table('memo_pembayarans', function (Blueprint $table) {
                $table->decimal('cicilan', 20, 5)->nullable()->after('total');
            });
        }

        // Drop foreign key constraints first if they exist
        if (Schema::hasColumn('memo_pembayarans', 'pph_id')) {
            Schema::table('memo_pembayarans', function (Blueprint $table) {
                $table->dropForeign(['pph_id']);
            });
        }

        // Then drop the columns if they exist
        $columnsToDrop = [];
        if (Schema::hasColumn('memo_pembayarans', 'diskon')) $columnsToDrop[] = 'diskon';
        if (Schema::hasColumn('memo_pembayarans', 'ppn')) $columnsToDrop[] = 'ppn';
        if (Schema::hasColumn('memo_pembayarans', 'ppn_nominal')) $columnsToDrop[] = 'ppn_nominal';
        if (Schema::hasColumn('memo_pembayarans', 'pph_id')) $columnsToDrop[] = 'pph_id';
        if (Schema::hasColumn('memo_pembayarans', 'pph_nominal')) $columnsToDrop[] = 'pph_nominal';
        if (Schema::hasColumn('memo_pembayarans', 'grand_total')) $columnsToDrop[] = 'grand_total';

        if (!empty($columnsToDrop)) {
            Schema::table('memo_pembayarans', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Remove cicilan field
            $table->dropColumn('cicilan');

            // Add back removed fields
            $table->decimal('diskon', 20, 5)->nullable();
            $table->tinyInteger('ppn')->default(0);
            $table->decimal('ppn_nominal', 20, 5)->nullable();
            $table->unsignedBigInteger('pph_id')->nullable();
            $table->decimal('pph_nominal', 20, 5)->nullable();
            $table->decimal('grand_total', 20, 5)->nullable();
        });
    }
};
