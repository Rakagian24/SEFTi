<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            // Jika ada foreign key lama dan pakai nama default, bisa perlu di-drop dulu.
            // Kalau error waktu migrate, kirimkan pesan errornya ke saya.
            // $table->dropForeign(['po_anggaran_id']);

            $table->unsignedBigInteger('po_anggaran_id')->nullable()->change();

            // Kalau mau, bisa tambah ulang FK dengan onDelete set null:
            // $table->foreign('po_anggaran_id')
            //     ->references('id')->on('po_anggarans')
            //     ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            // Balik seperti semula: tidak nullable
            $table->unsignedBigInteger('po_anggaran_id')->nullable(false)->change();
        });
    }
};
