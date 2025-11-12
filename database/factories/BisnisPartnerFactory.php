<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Bank;

class BisnisPartnerFactory extends Factory
{
    protected $model = \App\Models\BisnisPartner::class;

    public function definition(): array
    {
        // Ambil random bank_id dari tabel banks
        $bank = Bank::inRandomOrder()->first();

        return [
            'nama_bp' => $this->faker->company(),
            'jenis_bp' => $this->faker->randomElement(['Customer', 'Karyawan', 'Cabang']),
            'alamat' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'no_telepon' => $this->faker->phoneNumber(),
            'bank_id' => $bank ? $bank->id : null, // Gunakan bank_id dari relasi
            'nama_rekening' => $this->faker->name(),
            'no_rekening_va' => $this->faker->bankAccountNumber(),
        ];
    }
}
