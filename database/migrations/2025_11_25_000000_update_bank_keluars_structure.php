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
        Schema::table('bank_keluars', function (Blueprint $table) {
            // Rename tipe_pv -> tipe_bk
            if (Schema::hasColumn('bank_keluars', 'tipe_pv')) {
                $table->renameColumn('tipe_pv', 'tipe_bk');
            }

            // Drop foreign key for perihal_id if exists, then drop column
            if (Schema::hasColumn('bank_keluars', 'perihal_id')) {
                // Try common FK names safely
                try {
                    $table->dropForeign(['perihal_id']);
                } catch (\Throwable $e) {
                    // ignore if FK name is different / missing
                }
                $table->dropColumn('perihal_id');
            }

            // Add references to bank_supplier_accounts and credit_cards
            if (!Schema::hasColumn('bank_keluars', 'bank_supplier_account_id')) {
                $table->unsignedBigInteger('bank_supplier_account_id')->nullable()->after('supplier_id');
            }

            if (!Schema::hasColumn('bank_keluars', 'credit_card_id')) {
                $table->unsignedBigInteger('credit_card_id')->nullable()->after('bank_supplier_account_id');
            }
        });

        // Add foreign keys in a separate call to avoid issues with rename/drop
        Schema::table('bank_keluars', function (Blueprint $table) {
            if (Schema::hasColumn('bank_keluars', 'bank_supplier_account_id')) {
                try {
                    $table->foreign('bank_supplier_account_id')
                        ->references('id')->on('bank_supplier_accounts')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // ignore if FK already exists
                }
            }

            if (Schema::hasColumn('bank_keluars', 'credit_card_id')) {
                try {
                    $table->foreign('credit_card_id')
                        ->references('id')->on('credit_cards')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // ignore if FK already exists
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_keluars', function (Blueprint $table) {
            // Drop new foreign keys
            try {
                $table->dropForeign(['bank_supplier_account_id']);
            } catch (\Throwable $e) {}

            try {
                $table->dropForeign(['credit_card_id']);
            } catch (\Throwable $e) {}

            // Drop new columns
            if (Schema::hasColumn('bank_keluars', 'bank_supplier_account_id')) {
                $table->dropColumn('bank_supplier_account_id');
            }
            if (Schema::hasColumn('bank_keluars', 'credit_card_id')) {
                $table->dropColumn('credit_card_id');
            }

            // Recreate perihal_id column (without FK details, keep simple)
            if (!Schema::hasColumn('bank_keluars', 'perihal_id')) {
                $table->unsignedBigInteger('perihal_id')->nullable()->after('department_id');
            }

            // Rename tipe_bk back to tipe_pv
            if (Schema::hasColumn('bank_keluars', 'tipe_bk')) {
                $table->renameColumn('tipe_bk', 'tipe_pv');
            }
        });
    }
};
