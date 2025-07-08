<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BisnisPartner;

class BisnisPartnerSeeder extends Seeder
{
    public function run(): void
    {
        BisnisPartner::factory()->count(10)->create();
    }
}
