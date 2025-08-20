<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('purchase_orders', 'tipe_po')) {
                $table->enum('tipe_po', ['Reguler', 'Lainnya'])->default('Reguler')->after('no_po');
            }

            if (!Schema::hasColumn('purchase_orders', 'no_invoice')) {
                $table->string('no_invoice')->nullable()->after('perihal_id');
            }

            if (!Schema::hasColumn('purchase_orders', 'harga')) {
                $table->decimal('harga', 20, 2)->nullable()->after('no_invoice');
            }

            if (!Schema::hasColumn('purchase_orders', 'detail_keperluan')) {
                $table->text('detail_keperluan')->nullable()->after('harga');
            }

            if (!Schema::hasColumn('purchase_orders', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('detail_keperluan')->constrained()->onDelete('set null');
            }

            if (!Schema::hasColumn('purchase_orders', 'bank_id')) {
                $table->foreignId('bank_id')->nullable()->after('supplier_id')->constrained()->onDelete('set null');
            }

            if (!Schema::hasColumn('purchase_orders', 'nama_rekening')) {
                $table->string('nama_rekening')->nullable()->after('bank_id');
            }

            if (!Schema::hasColumn('purchase_orders', 'no_rekening')) {
                $table->string('no_rekening')->nullable()->after('nama_rekening');
            }

            if (!Schema::hasColumn('purchase_orders', 'no_kartu_kredit')) {
                $table->string('no_kartu_kredit')->nullable()->after('no_rekening');
            }

            if (!Schema::hasColumn('purchase_orders', 'no_giro')) {
                $table->string('no_giro')->nullable()->after('no_kartu_kredit');
            }

            if (!Schema::hasColumn('purchase_orders', 'tanggal_giro')) {
                $table->date('tanggal_giro')->nullable()->after('no_giro');
            }

            if (!Schema::hasColumn('purchase_orders', 'tanggal_cair')) {
                $table->date('tanggal_cair')->nullable()->after('tanggal_giro');
            }

            if (!Schema::hasColumn('purchase_orders', 'diskon')) {
                $table->decimal('diskon', 20, 2)->nullable()->after('tanggal_cair');
            }

            if (!Schema::hasColumn('purchase_orders', 'ppn')) {
                $table->boolean('ppn')->default(false)->after('diskon');
            }

            if (!Schema::hasColumn('purchase_orders', 'ppn_nominal')) {
                $table->decimal('ppn_nominal', 20, 2)->nullable()->after('ppn');
            }

            if (!Schema::hasColumn('purchase_orders', 'pph_id')) {
                $table->foreignId('pph_id')->nullable()->after('ppn_nominal')->constrained()->onDelete('set null');
            }

            if (!Schema::hasColumn('purchase_orders', 'pph_nominal')) {
                $table->decimal('pph_nominal', 20, 2)->nullable()->after('pph_id');
            }

            if (!Schema::hasColumn('purchase_orders', 'grand_total')) {
                $table->decimal('grand_total', 20, 2)->nullable()->after('pph_nominal');
            }

            if (!Schema::hasColumn('purchase_orders', 'dokumen')) {
                $table->string('dokumen')->nullable()->after('grand_total');
            }

            if (!Schema::hasColumn('purchase_orders', 'cicilan')) {
                $table->decimal('cicilan', 20, 2)->nullable()->after('dokumen');
            }

            if (!Schema::hasColumn('purchase_orders', 'termin')) {
                $table->integer('termin')->nullable()->after('cicilan');
            }

            if (!Schema::hasColumn('purchase_orders', 'nominal')) {
                $table->decimal('nominal', 20, 2)->nullable()->after('termin');
            }

            if (!Schema::hasColumn('purchase_orders', 'keterangan')) {
                $table->string('keterangan')->nullable()->after('nominal');
            }
        });
    }

    public function down(): void
    {
        // This migration only adds missing fields, so we don't need to drop them
        // as they might be needed by other parts of the application
    }
};
