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
        Schema::table('ar_partners', function (Blueprint $table) {
            if (!Schema::hasColumn('ar_partners', 'contact_person')) {
                $table->string('contact_person', 100)->nullable()->after('no_telepon');
            }
            if (!Schema::hasColumn('ar_partners', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('contact_person');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_partners', function (Blueprint $table) {
            if (Schema::hasColumn('ar_partners', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('ar_partners', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
        });
    }
};
