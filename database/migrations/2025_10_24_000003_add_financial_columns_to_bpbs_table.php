<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpbs', function (Blueprint $table) {
            // Monetary columns
            $table->decimal('subtotal', 20, 2)->nullable()->after('keterangan');
            $table->decimal('diskon', 20, 2)->nullable()->after('subtotal');
            $table->decimal('dpp', 20, 2)->nullable()->after('diskon');
            $table->decimal('ppn', 20, 2)->nullable()->after('dpp');
            $table->decimal('pph', 20, 2)->nullable()->after('ppn');
            $table->decimal('grand_total', 20, 2)->nullable()->after('pph');
        });
    }

    public function down(): void
    {
        Schema::table('bpbs', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'diskon', 'dpp', 'ppn', 'pph', 'grand_total']);
        });
    }
};
