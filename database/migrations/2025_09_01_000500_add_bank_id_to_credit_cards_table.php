<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->foreignId('bank_id')->after('department_id')->constrained('banks')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
        });
    }
};

