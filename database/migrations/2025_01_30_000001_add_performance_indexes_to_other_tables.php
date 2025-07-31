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
        // Add indexes to ar_partners table
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->index(['nama_ap', 'jenis_ap'], 'ar_partners_nama_jenis_index');
            $table->index('department_id', 'ar_partners_department_id_index');
            $table->index('created_at', 'ar_partners_created_at_index');
        });

        // Add indexes to bisnis_partners table
        Schema::table('bisnis_partners', function (Blueprint $table) {
            $table->index(['nama_bp', 'jenis_bp'], 'bisnis_partners_nama_jenis_index');
            $table->index('bank_id', 'bisnis_partners_bank_id_index');
            $table->index('terms_of_payment', 'bisnis_partners_terms_index');
            $table->index('created_at', 'bisnis_partners_created_at_index');
        });

        // Add indexes to suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index(['nama_supplier', 'terms_of_payment'], 'suppliers_nama_terms_index');
            $table->index('department_id', 'suppliers_department_id_index');
            $table->index('created_at', 'suppliers_created_at_index');
        });

        // Add indexes to pphs table
        Schema::table('pphs', function (Blueprint $table) {
            $table->index(['kode_pph', 'nama_pph'], 'pphs_kode_nama_index');
            $table->index('status', 'pphs_status_index');
            $table->index('created_at', 'pphs_created_at_index');
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['name', 'email'], 'users_name_email_index');
            $table->index('role_id', 'users_role_id_index');
            $table->index('department_id', 'users_department_id_index');
            $table->index('created_at', 'users_created_at_index');
        });

        // Add indexes to pengeluarans table
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->index(['nama', 'status'], 'pengeluarans_nama_status_index');
            $table->index('created_at', 'pengeluarans_created_at_index');
        });

        // Add indexes to banks table
        Schema::table('banks', function (Blueprint $table) {
            $table->index(['nama_bank', 'status'], 'banks_nama_status_index');
            $table->index('created_at', 'banks_created_at_index');
        });

        // Add indexes to bank_accounts table
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->index(['nama_pemilik', 'no_rekening'], 'bank_accounts_nama_no_index');
            $table->index('bank_id', 'bank_accounts_bank_id_index');
            $table->index('status', 'bank_accounts_status_index');
            $table->index('created_at', 'bank_accounts_created_at_index');
        });

        // Add indexes to purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['no_po', 'status'], 'purchase_orders_no_status_index');
            $table->index('department_id', 'purchase_orders_department_id_index');
            $table->index('tanggal', 'purchase_orders_tanggal_index');
            $table->index('created_at', 'purchase_orders_created_at_index');
        });

        // Add indexes to perihals table
        Schema::table('perihals', function (Blueprint $table) {
            $table->index(['nama', 'status'], 'perihals_nama_status_index');
            $table->index('created_at', 'perihals_created_at_index');
        });

        // Add indexes to departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->index(['name', 'status'], 'departments_name_status_index');
            $table->index('created_at', 'departments_created_at_index');
        });

        // Add indexes to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->index(['name', 'status'], 'roles_name_status_index');
            $table->index('created_at', 'roles_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from ar_partners table
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->dropIndex('ar_partners_nama_jenis_index');
            $table->dropIndex('ar_partners_department_id_index');
            $table->dropIndex('ar_partners_created_at_index');
        });

        // Remove indexes from bisnis_partners table
        Schema::table('bisnis_partners', function (Blueprint $table) {
            $table->dropIndex('bisnis_partners_nama_jenis_index');
            $table->dropIndex('bisnis_partners_bank_id_index');
            $table->dropIndex('bisnis_partners_terms_index');
            $table->dropIndex('bisnis_partners_created_at_index');
        });

        // Remove indexes from suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('suppliers_nama_terms_index');
            $table->dropIndex('suppliers_department_id_index');
            $table->dropIndex('suppliers_created_at_index');
        });

        // Remove indexes from pphs table
        Schema::table('pphs', function (Blueprint $table) {
            $table->dropIndex('pphs_kode_nama_index');
            $table->dropIndex('pphs_status_index');
            $table->dropIndex('pphs_created_at_index');
        });

        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_name_email_index');
            $table->dropIndex('users_role_id_index');
            $table->dropIndex('users_department_id_index');
            $table->dropIndex('users_created_at_index');
        });

        // Remove indexes from pengeluarans table
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropIndex('pengeluarans_nama_status_index');
            $table->dropIndex('pengeluarans_created_at_index');
        });

        // Remove indexes from banks table
        Schema::table('banks', function (Blueprint $table) {
            $table->dropIndex('banks_nama_status_index');
            $table->dropIndex('banks_created_at_index');
        });

        // Remove indexes from bank_accounts table
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex('bank_accounts_nama_no_index');
            $table->dropIndex('bank_accounts_bank_id_index');
            $table->dropIndex('bank_accounts_status_index');
            $table->dropIndex('bank_accounts_created_at_index');
        });

        // Remove indexes from purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_no_status_index');
            $table->dropIndex('purchase_orders_department_id_index');
            $table->dropIndex('purchase_orders_tanggal_index');
            $table->dropIndex('purchase_orders_created_at_index');
        });

        // Remove indexes from perihals table
        Schema::table('perihals', function (Blueprint $table) {
            $table->dropIndex('perihals_nama_status_index');
            $table->dropIndex('perihals_created_at_index');
        });

        // Remove indexes from departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('departments_name_status_index');
            $table->dropIndex('departments_created_at_index');
        });

        // Remove indexes from roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('roles_name_status_index');
            $table->dropIndex('roles_created_at_index');
        });
    }
};
