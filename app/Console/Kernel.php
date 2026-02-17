<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected $commands = [
        'App\Console\Commands\DailyReminderCommand',
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('notification:daily')->daily();
        $schedule->command('notification:weekly')->daily();
        $schedule->command('notification:monthy')->daily();
        $schedule->command('notification:quarterly')->daily();
        $schedule->command('notification:halfYearly')->daily();
        $schedule->command('notification:yearly')->daily();
        $schedule->command('notification:customDateReminderSchedule')->daily();
        $schedule->command('notification:reminderSchedule')->everyTenMinutes();
        $schedule->command('notification:sendEmailSuppliers')->everyTenMinutes();
        
        // Clean up expired blacklisted tokens
        $schedule->call(function () {
            DB::table('jwt_blacklist')->where('expires_at', '<', now())->delete();
        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
