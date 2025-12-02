<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpbs', function (Blueprint $table) {
            $table->string('surat_jalan_no')->nullable()->after('keterangan');
            $table->string('surat_jalan_file')->nullable()->after('surat_jalan_no');
        });
    }

    public function down(): void
    {
        Schema::table('bpbs', function (Blueprint $table) {
            $table->dropColumn(['surat_jalan_no', 'surat_jalan_file']);
        });
    }
};
