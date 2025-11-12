<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bisnis_partners') && Schema::hasColumn('bisnis_partners', 'terms_of_payment')) {
            Schema::table('bisnis_partners', function (Blueprint $table) {
                $table->dropColumn('terms_of_payment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bisnis_partners') && !Schema::hasColumn('bisnis_partners', 'terms_of_payment')) {
            Schema::table('bisnis_partners', function (Blueprint $table) {
                $table->string('terms_of_payment')->nullable();
            });
        }
    }
};
