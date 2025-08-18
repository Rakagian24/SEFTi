<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(\Database\Seeders\DepartmentSeeder::class);
        $this->call(\Database\Seeders\RoleSeeder::class);
        $this->call(\Database\Seeders\BankSeeder::class);
        $this->call(\Database\Seeders\BisnisPartnerSeeder::class);
        $this->call(\Database\Seeders\ArPartnerSeeder::class);
        $this->call(\Database\Seeders\PengeluaranSeeder::class);
        $this->call(\Database\Seeders\PphSeeder::class);
        $this->call(\Database\Seeders\BankAccountSeeder::class);
        $this->call(\Database\Seeders\SupplierSeederNew::class);
        $this->call(\Database\Seeders\BankSupplierAccountSeeder::class);
        $this->call(\Database\Seeders\PurchaseOrderSeeder::class);
    }
}
