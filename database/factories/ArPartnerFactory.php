<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BisnisPartnerFactory extends Factory
{
    protected $model = \App\Models\BisnisPartner::class;

    public function definition(): array
    {
        return [
            'nama_bp' => $this->faker->company(),
            'jenis_bp' => $this->faker->randomElement(['Customer', 'Karyawan', 'Cabang']),
            'alamat' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'no_telepon' => $this->faker->phoneNumber(),
            'nama_bank' => $this->faker->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI']),
            'nama_rekening' => $this->faker->name(),
            'no_rekening_va' => $this->faker->bankAccountNumber(),
            'terms_of_payment' => $this->faker->randomElement(['7 Hari','15 Hari','30 Hari', '45 Hari', '60 Hari', '90 Hari']),
            'status' => 'aktif',
        ];
    }
}
