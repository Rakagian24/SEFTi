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
        Schema::table('auto_matches', function (Blueprint $table) {
            // Add new fields for sj_new view
            $table->string('doc_number')->nullable()->after('kwitansi_id');
            $table->string('doc_no')->nullable()->after('doc_number');
            $table->date('doc_tanggal')->nullable()->after('doc_no');
            $table->double('doc_nilai', 20, 2)->nullable()->after('doc_tanggal');

            // Rename existing fields to be more generic (keeping old ones for backward compatibility)
            $table->renameColumn('kwitansi_id', 'legacy_kwitansi_id');
            $table->renameColumn('kwitansi_no', 'legacy_kwitansi_no');
            $table->renameColumn('kwitansi_tanggal', 'legacy_kwitansi_tanggal');
            $table->renameColumn('kwitansi_nilai', 'legacy_kwitansi_nilai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            // Drop new fields
            $table->dropColumn(['doc_number', 'doc_no', 'doc_tanggal', 'doc_nilai']);

            // Restore original field names
            $table->renameColumn('legacy_kwitansi_id', 'kwitansi_id');
            $table->renameColumn('legacy_kwitansi_no', 'kwitansi_no');
            $table->renameColumn('legacy_kwitansi_tanggal', 'kwitansi_tanggal');
            $table->renameColumn('legacy_kwitansi_nilai', 'kwitansi_nilai');
        });
    }
};
