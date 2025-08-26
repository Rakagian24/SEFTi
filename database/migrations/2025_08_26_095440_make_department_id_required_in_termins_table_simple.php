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

        // Then make the column not nullable
        Schema::table('termins', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('termins', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
        });
    }
};
