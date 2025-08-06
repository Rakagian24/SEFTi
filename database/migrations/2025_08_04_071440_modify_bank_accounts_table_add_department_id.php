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
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Add foreign key constraint for existing department_id column
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });

        // Drop nama_pemilik column
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('nama_pemilik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Add back nama_pemilik column
            $table->string('nama_pemilik')->after('id');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['department_id']);
        });
    }
};
