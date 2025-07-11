<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop the foreign key constraints first
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['bank_account_id']);

            // Drop the old columns
            $table->dropColumn(['bank_id', 'bank_account_id']);

            // Add new columns for multiple bank accounts (up to 3)
            $table->string('bank_1')->nullable();
            $table->string('nama_rekening_1')->nullable();
            $table->string('no_rekening_1')->nullable();

            $table->string('bank_2')->nullable();
            $table->string('nama_rekening_2')->nullable();
            $table->string('no_rekening_2')->nullable();

            $table->string('bank_3')->nullable();
            $table->string('nama_rekening_3')->nullable();
            $table->string('no_rekening_3')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop the new columns
            $table->dropColumn([
                'bank_1', 'nama_rekening_1', 'no_rekening_1',
                'bank_2', 'nama_rekening_2', 'no_rekening_2',
                'bank_3', 'nama_rekening_3', 'no_rekening_3'
            ]);

            // Add back the old columns
            $table->unsignedBigInteger('bank_id');
            $table->unsignedBigInteger('bank_account_id');

            // Add back the foreign key constraints
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });
    }
};
