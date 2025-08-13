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

        // Add foreign key constraints if not present
        Schema::table('department_user', function (Blueprint $table) {
            $foreignKeys = collect(DB::select("SHOW CREATE TABLE department_user"))->first();
            $createSql = $foreignKeys ? ($foreignKeys->{'Create Table'} ?? '') : '';
            if ($createSql && !str_contains($createSql, 'CONSTRAINT `department_user_user_id_foreign`')) {
                $table->foreign('user_id', 'department_user_user_id_foreign')->references('id')->on('users')->onDelete('cascade');
            }
            if ($createSql && !str_contains($createSql, 'CONSTRAINT `department_user_department_id_foreign`')) {
                $table->foreign('department_id', 'department_user_department_id_foreign')->references('id')->on('departments')->onDelete('cascade');
            }
        });

        // Remove existing duplicate rows, keeping the lowest id per pair
        // Note: Works on MySQL. Adjust if using a different driver.
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

