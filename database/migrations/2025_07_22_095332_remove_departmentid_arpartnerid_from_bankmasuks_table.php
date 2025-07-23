<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            if (Schema::hasColumn('bank_masuks', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('bank_masuks', 'ar_partner_id')) {
                $table->dropForeign(['ar_partner_id']);
                $table->dropColumn('ar_partner_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_masuks', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('ar_partner_id')->nullable()->constrained('ar_partners')->onDelete('set null');
        });
    }
};
