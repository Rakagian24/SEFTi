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
            // Change detail_keperluan from string to text (unlimited length)
            $table->text('detail_keperluan')->nullable()->change();
        });

        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Change keterangan (note) from string to text (unlimited length)
            $table->text('keterangan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Revert detail_keperluan back to string with max length
            $table->string('detail_keperluan')->nullable()->change();
        });

        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Revert keterangan back to string with max length
            $table->string('keterangan')->nullable()->change();
        });
    }
};
