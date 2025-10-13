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
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['bank_id']);

            // Remove redundant bank/account columns
            $table->dropColumn([
                'bank_id',
                'nama_rekening',
                'no_rekening',
                'no_kartu_kredit',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            // Restore redundant bank/account columns
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null')->after('bank_supplier_account_id');
            $table->string('nama_rekening')->nullable()->after('bank_id');
            $table->string('no_rekening')->nullable()->after('nama_rekening');
            $table->string('no_kartu_kredit')->nullable()->after('no_rekening');
        });
    }
};
