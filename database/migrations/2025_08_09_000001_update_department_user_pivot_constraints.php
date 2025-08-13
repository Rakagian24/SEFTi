<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure the pivot table has the correct columns
        Schema::table('department_user', function (Blueprint $table) {
            if (!Schema::hasColumn('department_user', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('department_user', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('user_id');
            }
        });

        // Note: Skip adding foreign key constraints here to avoid failures on
        // existing inconsistent data in production. We rely on unique index below
        // and application-level integrity. FK constraints can be added later
        // after data cleanup if required.

        // Remove rows without valid references (nulls) to keep table clean
        DB::table('department_user')->whereNull('user_id')->orWhereNull('department_id')->delete();

        // Remove exact duplicates, keeping the lowest id per pair
        DB::statement('DELETE du1 FROM department_user du1 INNER JOIN department_user du2 ON du1.user_id = du2.user_id AND du1.department_id = du2.department_id AND du1.id > du2.id');

        // Add a unique index to prevent future duplicates
        Schema::table('department_user', function (Blueprint $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM department_user WHERE Key_name = 'department_user_user_department_unique'"));
            if ($indexes->isEmpty()) {
                $table->unique(['user_id', 'department_id'], 'department_user_user_department_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            try {
                $table->dropUnique('department_user_user_department_unique');
            } catch (\Throwable $e) {
                // ignore if not exists
            }
        });
    }
};

