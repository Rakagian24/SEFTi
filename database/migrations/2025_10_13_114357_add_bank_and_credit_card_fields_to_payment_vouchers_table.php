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
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('supplier_address');
            $table->string('account_owner_name')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_owner_name');
            $table->string('no_kartu_kredit')->nullable()->after('account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_owner_name', 'account_number', 'no_kartu_kredit']);
        });
    }
};
