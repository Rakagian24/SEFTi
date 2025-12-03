<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'Kadiv Finance',
                'description' => 'Role ini hanya dapat mengakses menu Approval untuk Payment Voucher dan Tipe Pajak',
                'permissions' => json_encode(['approval']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Direksi Finance',
                'description' => 'Role ini hanya dapat mengakses menu Approval untuk Payment Voucher dan Tipe Pajak',
                'permissions' => json_encode(['approval']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->whereIn('name', ['Kadiv Finance', 'Direksi Finance'])->delete();
    }
};
