<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'All', 'alias' => 'ALL'],
            ['name' => 'SGT 1', 'alias' => 'SGT1'],
            ['name' => 'SGT 2', 'alias' => 'SGT2'],
            ['name' => 'SGT 3', 'alias' => 'SGT3'],
            ['name' => 'Nirwana Textile Hasanudin', 'alias' => 'HSD09'],
            ['name' => 'Nirwana Textile Bkr', 'alias' => 'BKR92'],
            ['name' => 'Nirwana Textile Yogyakarta HOS Cokro', 'alias' => 'HOS199'],
            ['name' => 'Nirwana Textile Bali', 'alias' => 'BALI292'],
            ['name' => 'Nirwana Textile Surabaya', 'alias' => 'SBY299'],
            ['name' => 'Human Greatness', 'alias' => 'HG'],
            ['name' => 'Zi&Glo', 'alias' => 'ZG']
        ];

        foreach ($departments as $department) {
            Department::create([
                'name' => $department['name'],
                'alias' => $department['alias'],
                'status' => 'active'
            ]);
        }
    }
}
