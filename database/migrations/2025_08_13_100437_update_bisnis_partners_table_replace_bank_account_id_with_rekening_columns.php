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
        Schema::table('bisnis_partners', function (Blueprint $table) {
            // Hapus index bank_account_id terlebih dahulu
            $table->dropIndex('bisnis_partners_bank_account_id_foreign');

            // Hapus kolom bank_account_id
            $table->dropColumn('bank_account_id');

            // Tambah kolom nama_rekening dan no_rekening_va
            $table->string('nama_rekening')->nullable()->after('bank_id');
            $table->string('no_rekening_va')->nullable()->after('nama_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bisnis_partners', function (Blueprint $table) {
            // Hapus kolom nama_rekening dan no_rekening_va
            $table->dropColumn(['nama_rekening', 'no_rekening_va']);

            // Tambah kembali kolom bank_account_id
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('bank_id');
            $table->index('bank_account_id', 'bisnis_partners_bank_account_id_foreign');
        });
    }
};
