<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('barang_department')) {
            Schema::create('barang_department', function (Blueprint $table) {
                $table->id();
                $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
                $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
                $table->unique(['barang_id', 'department_id']);
            });
        }

        // Optional: backfill from barangs.department_id if exists
        if (Schema::hasColumn('barangs', 'department_id')) {
            $pairs = DB::table('barangs')
                ->whereNotNull('department_id')
                ->select('id as barang_id', 'department_id')
                ->get();

            foreach ($pairs as $row) {
                $exists = DB::table('barang_department')
                    ->where('barang_id', $row->barang_id)
                    ->where('department_id', $row->department_id)
                    ->exists();
                if (!$exists) {
                    DB::table('barang_department')->insert([
                        'barang_id' => $row->barang_id,
                        'department_id' => $row->department_id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_department');
    }
};
