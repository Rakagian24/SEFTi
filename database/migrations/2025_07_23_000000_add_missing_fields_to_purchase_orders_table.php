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
            if (!Schema::hasColumn('purchase_orders', 'tipe_po')) {
                $table->enum('tipe_po', ['Reguler', 'Lainnya'])->default('Reguler')->after('no_po');
            }

            // Field untuk tipe Reguler
            if (!Schema::hasColumn('purchase_orders', 'no_invoice')) {
                $table->string('no_invoice')->nullable()->after('perihal');
            }
            if (!Schema::hasColumn('purchase_orders', 'harga')) {
                $table->decimal('harga', 20, 2)->nullable()->after('no_invoice');
            }
            if (!Schema::hasColumn('purchase_orders', 'detail_keperluan')) {
                $table->text('detail_keperluan')->nullable()->after('harga');
            }

            // Field untuk metode pembayaran
            if (!Schema::hasColumn('purchase_orders', 'nama_rekening')) {
                $table->string('nama_rekening')->nullable()->after('metode_pembayaran');
            }
            if (!Schema::hasColumn('purchase_orders', 'no_rekening')) {
                $table->string('no_rekening')->nullable()->after('nama_rekening');
            }

            // Field untuk Cek/Giro
            if (!Schema::hasColumn('purchase_orders', 'no_giro')) {
                $table->string('no_giro')->nullable()->after('no_rekening');
            }
            if (!Schema::hasColumn('purchase_orders', 'tanggal_giro')) {
                $table->date('tanggal_giro')->nullable()->after('no_giro');
            }
            if (!Schema::hasColumn('purchase_orders', 'tanggal_cair')) {
                $table->date('tanggal_cair')->nullable()->after('tanggal_giro');
            }

            // Field untuk perhitungan pajak
            if (!Schema::hasColumn('purchase_orders', 'diskon')) {
                $table->decimal('diskon', 20, 2)->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('purchase_orders', 'ppn')) {
                $table->boolean('ppn')->default(false)->after('diskon');
            }
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
