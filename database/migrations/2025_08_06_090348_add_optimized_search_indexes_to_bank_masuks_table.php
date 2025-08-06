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
            // Add optimized indexes for search performance
            $table->index(['tanggal', 'status'], 'bank_masuks_tanggal_status_opt_index');
            $table->index(['bank_account_id', 'status'], 'bank_masuks_bank_account_status_opt_index');
            $table->index(['nilai', 'status'], 'bank_masuks_nilai_status_opt_index');
        });

        // Add indexes to related tables for better join performance
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->index(['department_id', 'status'], 'bank_accounts_dept_status_opt_index');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->index(['name', 'status'], 'departments_name_status_opt_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->dropIndex('bank_masuks_tanggal_status_opt_index');
            $table->dropIndex('bank_masuks_bank_account_status_opt_index');
            $table->dropIndex('bank_masuks_nilai_status_opt_index');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex('bank_accounts_dept_status_opt_index');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('departments_name_status_opt_index');
        });
    }
};
