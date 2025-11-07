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
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'jenis_barang_id')) {
                $table->unsignedBigInteger('jenis_barang_id')->nullable()->after('perihal_id');
                $table->foreign('jenis_barang_id')
                    ->references('id')
                    ->on('jenis_barangs')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'jenis_barang_id')) {
                $table->dropForeign(['jenis_barang_id']);
                $table->dropColumn('jenis_barang_id');
            }
        });
    }
};
