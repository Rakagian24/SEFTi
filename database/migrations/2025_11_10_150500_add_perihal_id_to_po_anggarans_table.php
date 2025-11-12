<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            if (!Schema::hasColumn('po_anggarans', 'perihal_id')) {
                $table->foreignId('perihal_id')->nullable()->after('department_id')
                    ->constrained('perihals')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('po_anggarans', function (Blueprint $table) {
            if (Schema::hasColumn('po_anggarans', 'perihal_id')) {
                $table->dropConstrainedForeignId('perihal_id');
            }
        });
    }
};
