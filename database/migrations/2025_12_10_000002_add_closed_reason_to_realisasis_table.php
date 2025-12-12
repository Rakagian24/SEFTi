<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            if (!Schema::hasColumn('realisasis', 'closed_reason')) {
                $table->text('closed_reason')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('realisasis', function (Blueprint $table) {
            if (Schema::hasColumn('realisasis', 'closed_reason')) {
                $table->dropColumn('closed_reason');
            }
        });
    }
};
