<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update all existing status values to valid enum values
        DB::table('bank_accounts')->where('status', 'aktif')->update(['status' => 'active']);
        DB::table('bank_accounts')->where('status', 'nonaktif')->update(['status' => 'inactive']);
        DB::table('bank_accounts')->where('status', 'tidak aktif')->update(['status' => 'inactive']);
        DB::table('bank_accounts')->where('status', 'suspend')->update(['status' => 'inactive']);
        DB::table('bank_accounts')->where('status', 'Aktif')->update(['status' => 'active']);
        DB::table('bank_accounts')->where('status', 'Nonaktif')->update(['status' => 'inactive']);

        // Then alter the column to enum
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }
};
