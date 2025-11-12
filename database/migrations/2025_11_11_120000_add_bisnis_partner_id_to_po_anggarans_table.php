<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            if (!Schema::hasColumn('po_anggarans', 'bisnis_partner_id')) {
                $table->foreignId('bisnis_partner_id')->nullable()->after('bank_id')
                    ->constrained('bisnis_partners')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            if (Schema::hasColumn('po_anggarans', 'bisnis_partner_id')) {
                $table->dropConstrainedForeignId('bisnis_partner_id');
            }
        });
    }
};
