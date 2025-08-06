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
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->foreignId('ar_partner_id')->nullable()->constrained('ar_partners')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->dropForeign(['ar_partner_id']);
            $table->dropColumn('ar_partner_id');
        });
    }
};
