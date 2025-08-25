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
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Ar Partners table
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Bisnis Partners table
        Schema::table('bisnis_partners', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Banks table
        Schema::table('banks', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Bank Accounts table
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Bank Masuks table
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Bank Matchings table
        // Schema::table('bank_matchings', function (Blueprint $table) {
        //     $table->softDeletes();
        // });

        // Auto Matches table
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Purchase Orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Purchase Order Items table
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Memo Pembayarans table
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Pengeluarans table
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->softDeletes();
        });

        // PPHs table
        Schema::table('pphs', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Termins table
        Schema::table('termins', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Perihals table
        Schema::table('perihals', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Bank Supplier Accounts table
        Schema::table('bank_supplier_accounts', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Nirwana Invoices table
        // Schema::table('nirwana_invoices', function (Blueprint $table) {
        //     $table->softDeletes();
        // });

        // // SJ News table
        // Schema::table('sj_news', function (Blueprint $table) {
        //     $table->softDeletes();
        // });

        // // Kwitansis table
        // Schema::table('kwitansis', function (Blueprint $table) {
        //     $table->softDeletes();
        // });

        // Conversations table
        Schema::table('conversations', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->softDeletes();
        });

        // OTP Verifications table
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Ar Partners table
        Schema::table('ar_partners', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Bisnis Partners table
        Schema::table('bisnis_partners', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Banks table
        Schema::table('banks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Bank Accounts table
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Bank Masuks table
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Bank Matchings table
        // Schema::table('bank_matchings', function (Blueprint $table) {
        //     $table->dropSoftDeletes();
        // });

        // Auto Matches table
        Schema::table('auto_matches', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Purchase Orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Purchase Order Items table
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Memo Pembayarans table
        Schema::table('memo_pembayarans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Pengeluarans table
        Schema::table('pengeluarans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // PPHs table
        Schema::table('pphs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Termins table
        Schema::table('termins', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Perihals table
        Schema::table('perihals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Bank Supplier Accounts table
        Schema::table('bank_supplier_accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Nirwana Invoices table
        // Schema::table('nirwana_invoices', function (Blueprint $table) {
        //     $table->dropSoftDeletes();
        // });

        // // SJ News table
        // Schema::table('sj_news', function (Blueprint $table) {
        //     $table->dropSoftDeletes();
        // });

        // // Kwitansis table
        // Schema::table('kwitansis', function (Blueprint $table) {
        //     $table->dropSoftDeletes();
        // });

        // Conversations table
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // OTP Verifications table
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
