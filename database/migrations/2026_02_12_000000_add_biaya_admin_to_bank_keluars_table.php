<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_keluars') && !Schema::hasColumn('bank_keluars', 'biaya_admin')) {
            Schema::table('bank_keluars', function (Blueprint $table) {
                $table->decimal('biaya_admin', 15, 5)->nullable()->after('nominal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_keluars') && Schema::hasColumn('bank_keluars', 'biaya_admin')) {
            Schema::table('bank_keluars', function (Blueprint $table) {
                $table->dropColumn('biaya_admin');
            });
        }
    }
};
