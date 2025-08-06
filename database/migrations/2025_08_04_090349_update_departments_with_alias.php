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
        // Update existing departments with alias
        $departmentAliases = [
            'All' => 'ALL',
            'SGT 1' => 'SGT1',
            'SGT 2' => 'SGT2',
            'SGT 3' => 'SGT3',
            'Nirwana Textile Hasanudin' => 'HSD09',
            'Nirwana Textile Bkr' => 'BKR92',
            'Nirwana Textile Yogyakarta HOS Cokro' => 'HOS199',
            'Nirwana Textile Bali' => 'BALI292',
            'Nirwana Textile Surabaya' => 'SBY299',
            'Human Greatness' => 'HG',
            'Zi&Glo' => 'ZG'
        ];

        foreach ($departmentAliases as $name => $alias) {
            DB::table('departments')
                ->where('name', $name)
                ->update(['alias' => $alias]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all aliases
        DB::table('departments')->update(['alias' => null]);
    }
};
