<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_keluars', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_keluars', 'source_type')) {
                $table->string('source_type', 20)->nullable()->after('tipe_bk');
            }

            if (!Schema::hasColumn('bank_keluars', 'realisasi_id')) {
                $table->foreignId('realisasi_id')->nullable()->after('payment_voucher_id')->constrained('realisasis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_keluars', function (Blueprint $table) {
            if (Schema::hasColumn('bank_keluars', 'realisasi_id')) {
                $table->dropConstrainedForeignId('realisasi_id');
            }
            if (Schema::hasColumn('bank_keluars', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
