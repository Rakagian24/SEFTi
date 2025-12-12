<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->string('satuan')->nullable()->after('nama');
            $table->foreignId('perihal_id')->nullable()->after('deskripsi')->constrained('perihals');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropForeign(['perihal_id']);
            $table->dropColumn(['satuan', 'perihal_id']);
        });
    }
};
