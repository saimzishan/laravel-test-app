<?php

namespace App\Console;

use App\Console\Commands\FirmDailyRefreshToken;
use App\Console\Commands\FirmsSyncUpdate;
use App\Console\Commands\PromotionShiftRequestCommand;
use App\Jobs\CheckAndNotifyFirmTrialDaysLeft;
use App\Jobs\FirmsDailyRefreshTokenJob;
use App\Jobs\FirmSyncJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(FirmsSyncUpdate::class)->daily()->timezone('America/Chicago');
        //$schedule->job(new CheckAndNotifyFirmTrialDaysLeft())->daily();
        $schedule->command(FirmDailyRefreshToken::class)->daily()->timezone('America/Chicago');
        $schedule->command(PromotionShiftRequestCommand::class)->daily()->timezone('America/Chicago');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
