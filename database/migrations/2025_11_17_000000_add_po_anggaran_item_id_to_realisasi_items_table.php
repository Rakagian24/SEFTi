<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('realisasi_items', function (Blueprint $table) {
            if (!Schema::hasColumn('realisasi_items', 'po_anggaran_item_id')) {
                $table->unsignedBigInteger('po_anggaran_item_id')->nullable()->after('realisasi_id');
                $table->foreign('po_anggaran_item_id')->references('id')->on('po_anggaran_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_items', function (Blueprint $table) {
            if (Schema::hasColumn('realisasi_items', 'po_anggaran_item_id')) {
                $table->dropForeign(['po_anggaran_item_id']);
                $table->dropColumn('po_anggaran_item_id');
            }
        });
    }
};
