<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Drop redundant columns that are available via relations
            if (Schema::hasColumn('payment_vouchers', 'supplier_phone')) {
                $table->dropColumn('supplier_phone');
            }
            if (Schema::hasColumn('payment_vouchers', 'supplier_address')) {
                $table->dropColumn('supplier_address');
            }
            if (Schema::hasColumn('payment_vouchers', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
            if (Schema::hasColumn('payment_vouchers', 'account_owner_name')) {
                $table->dropColumn('account_owner_name');
            }
            if (Schema::hasColumn('payment_vouchers', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('payment_vouchers', 'no_kartu_kredit')) {
                $table->dropColumn('no_kartu_kredit');
            }
            // Remove one of note/keterangan -> keep note, drop keterangan
            if (Schema::hasColumn('payment_vouchers', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            // Recreate columns (types as original best-effort)
            if (!Schema::hasColumn('payment_vouchers', 'supplier_phone')) {
                $table->string('supplier_phone')->nullable()->after('supplier_id');
            }
            if (!Schema::hasColumn('payment_vouchers', 'supplier_address')) {
                $table->string('supplier_address')->nullable()->after('supplier_phone');
            }
            if (!Schema::hasColumn('payment_vouchers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('supplier_address');
            }
            if (!Schema::hasColumn('payment_vouchers', 'account_owner_name')) {
                $table->string('account_owner_name')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('payment_vouchers', 'account_number')) {
                $table->string('account_number')->nullable()->after('account_owner_name');
            }
            if (!Schema::hasColumn('payment_vouchers', 'no_kartu_kredit')) {
                $table->string('no_kartu_kredit')->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('payment_vouchers', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('note');
            }
        });
    }
};


