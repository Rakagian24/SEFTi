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
            // Add composite index for date and status filtering
            $table->index(['tanggal', 'status'], 'bank_masuks_tanggal_status_index');

            // Add index for nilai column for value-based filtering
            $table->index('nilai', 'bank_masuks_nilai_index');

            // Add index for terima_dari for filtering
            $table->index('terima_dari', 'bank_masuks_terima_dari_index');

            // Add index for purchase_order_id
            $table->index('purchase_order_id', 'bank_masuks_purchase_order_id_index');

            // Add index for created_at for sorting
            $table->index('created_at', 'bank_masuks_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->dropIndex('bank_masuks_tanggal_status_index');
            $table->dropIndex('bank_masuks_nilai_index');
            $table->dropIndex('bank_masuks_terima_dari_index');
            $table->dropIndex('bank_masuks_purchase_order_id_index');
            $table->dropIndex('bank_masuks_created_at_index');
        });
    }
};
