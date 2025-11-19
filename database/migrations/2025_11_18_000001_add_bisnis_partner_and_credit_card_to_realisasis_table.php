<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            $table->foreignId('bisnis_partner_id')
                ->nullable()
                ->after('po_anggaran_id')
                ->constrained('bisnis_partners')
                ->nullOnDelete();

            $table->foreignId('credit_card_id')
                ->nullable()
                ->after('bisnis_partner_id')
                ->constrained('credit_cards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            $table->dropForeign(['bisnis_partner_id']);
            $table->dropColumn('bisnis_partner_id');

            $table->dropForeign(['credit_card_id']);
            $table->dropColumn('credit_card_id');
        });
    }
};
