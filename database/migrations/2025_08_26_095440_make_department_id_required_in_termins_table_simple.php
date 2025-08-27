<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure all existing records have a department_id
        // Set default department_id to 1 (or any existing department) for existing records
        DB::statement('UPDATE termins SET department_id = 1 WHERE department_id IS NULL');

        // Drop the existing foreign key constraint
        Schema::table('termins', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        // Make the column not nullable
        Schema::table('termins', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
        });

        // Recreate the foreign key constraint with cascade instead of nullOnDelete
        Schema::table('termins', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('termins', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        // Make the column nullable again
        Schema::table('termins', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
        });

        // Recreate the original foreign key constraint with nullOnDelete
        Schema::table('termins', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }
};
