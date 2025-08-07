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
            // Invoice customer and department fields
            $table->string('invoice_customer_name')->nullable()->after('sj_nilai');
            $table->string('invoice_department')->nullable()->after('invoice_customer_name');

            // Bank masuk customer and department fields
            $table->string('bank_masuk_customer_name')->nullable()->after('bm_nilai');
            $table->string('bank_masuk_department')->nullable()->after('bank_masuk_customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_customer_name',
                'invoice_department',
                'bank_masuk_customer_name',
                'bank_masuk_department'
            ]);
        });
    }
};
