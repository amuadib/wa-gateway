<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            \Illuminate\Support\Facades\Cache::put('scheduler:last_heartbeat', now()->toIso8601String(), 86400);
        })->everyMinute()->name('scheduler-heartbeat');

        $schedule->command('message:send-to-api')
            ->everyMinute();
        $schedule->command('message:delete')
            ->dailyAt('02:25');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
