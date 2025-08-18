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
            // Field untuk tipe PO
            $table->enum('tipe_po', ['Reguler', 'Lainnya'])->default('Reguler')->after('no_po');

            // Field untuk tipe Reguler
            $table->string('no_invoice')->nullable()->after('perihal');
            $table->decimal('harga', 20, 2)->nullable()->after('no_invoice');
            $table->text('detail_keperluan')->nullable()->after('harga');

                        // Field untuk metode pembayaran
            $table->string('nama_rekening')->nullable()->after('metode_pembayaran');
            $table->string('no_rekening')->nullable()->after('nama_rekening');

            // Field untuk Cek/Giro
            $table->string('no_giro')->nullable()->after('no_rekening');
            $table->date('tanggal_giro')->nullable()->after('no_giro');
            $table->date('tanggal_cair')->nullable()->after('tanggal_giro');

            // Field untuk perhitungan pajak
            $table->decimal('diskon', 20, 2)->nullable()->after('keterangan');
            $table->boolean('ppn')->default(false)->after('diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'tipe_po',
                'no_invoice',
                'harga',
                'detail_keperluan',
                'nama_rekening',
                'no_rekening',
                'no_giro',
                'tanggal_giro',
                'tanggal_cair',
                'diskon',
                'ppn'
            ]);
        });
    }
};
