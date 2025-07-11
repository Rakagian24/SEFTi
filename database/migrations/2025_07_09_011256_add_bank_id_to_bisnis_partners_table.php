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
            // Tambah foreign key bank_id
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');

            // Hapus kolom nama_bank yang lama (string)
            $table->dropColumn('nama_bank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bisnis_partners', function (Blueprint $table) {
            // Hapus foreign key
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');

            // Kembalikan kolom nama_bank yang lama
            $table->string('nama_bank')->nullable();
        });
    }
};
