<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MigrasiPelangganService;


class MigrasiPelangganCommand extends Command
{
    protected $signature = 'migrasi:pelanggan';
    protected $description = 'Migrasi data pelanggan dari PostgreSQL ke MySQL';

    public function handle(MigrasiPelangganService $service)
    {
        $this->info('Memulai migrasi pelanggan...');

        $count = $service->jalankanMigrasi();

        $this->info("Migrasi selesai. Total data: $count");
    }
}
