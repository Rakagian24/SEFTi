<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `po_anggarans`
            MODIFY `status` ENUM('Draft','In Progress','Verified','Validated','Rejected','Approved','Canceled')
            NOT NULL DEFAULT 'Draft'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE `po_anggarans`
            MODIFY `status` ENUM('Draft','In Progress','Rejected','Approved','Canceled')
            NOT NULL DEFAULT 'Draft'"
        );
    }
};
