<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\MigrasiPelangganCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Jalankan migrasi pelanggan setiap malam jam 1
        $schedule->command('migrasi:pelanggan')
            ->dailyAt('01:00')
            ->appendOutputTo(storage_path('logs/migrasi-pelanggan.log'))
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
