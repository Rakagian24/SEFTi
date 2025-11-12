<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_vouchers', 'po_anggaran_id')) {
                $table->unsignedBigInteger('po_anggaran_id')->nullable()->after('no_pv');
                $table->foreign('po_anggaran_id')->references('id')->on('po_anggarans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('payment_vouchers', 'po_anggaran_id')) {
                $table->dropForeign(['po_anggaran_id']);
                $table->dropColumn('po_anggaran_id');
            }
        });
    }
};
