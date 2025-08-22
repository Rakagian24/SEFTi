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
        // First, let's check the current column type
        $columns = DB::select("SHOW COLUMNS FROM purchase_orders LIKE 'keterangan'");

        if (!empty($columns)) {
            $column = $columns[0];

            // If the column is still varchar/string, change it to text
            if (str_contains(strtolower($column->Type), 'varchar') || str_contains(strtolower($column->Type), 'char')) {
                DB::statement('ALTER TABLE purchase_orders MODIFY COLUMN keterangan TEXT NULL');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to varchar(255) if needed
        DB::statement('ALTER TABLE purchase_orders MODIFY COLUMN keterangan VARCHAR(255) NULL');
    }
};
