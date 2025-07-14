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
            'SGT 1',
            'SGT 2',
            'SGT 3',
            'Nirwana Textile Hasanudin',
            'Nirwana Textile Bkr',
            'Nirwana Textile Yogyakarta',
            'Nirwana Textile Bali',
            'Nirwana Textile Surabaya',
            'Human Greatness',
            'Zi&Glo'
        ];

        foreach ($departments as $department) {
            Department::create([
                'name' => $department,
                'status' => 'active'
            ]);
        }
    }
}
