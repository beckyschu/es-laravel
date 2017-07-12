<?php

namespace App\Console;

use Shark;
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
        Commands\ScheduleCrawls::class,
        Commands\BackupDatabase::class,
        Commands\GetEbayCategories::class,
        Shark\Parsehub\CrawlCommand::class,
        Shark\eBay\CrawlCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Set timezone for scheduling
        $timezone = 'America/Los_Angeles';

        // Schedule crawls for midnight PST
        $schedule->command('shark:schedule-crawls --schedule=daily')->timezone($timezone)->cron('0 0 * * * *');
        $schedule->command('shark:schedule-crawls --schedule=2days')->timezone($timezone)->cron('0 0 */2 * * *');
        $schedule->command('shark:schedule-crawls --schedule=3days')->timezone($timezone)->cron('0 0 */3 * * *');
        $schedule->command('shark:schedule-crawls --schedule=4days')->timezone($timezone)->cron('0 0 */4 * * *');
        $schedule->command('shark:schedule-crawls --schedule=5days')->timezone($timezone)->cron('0 0 */5 * * *');

        // Backup database to Dropbox
        $schedule->command('shark:backup')->timezone($timezone)->dailyAt('23:00');

        // Refresh eBay categories
        $schedule->command('shark:get-ebay-categories')->timezone($timezone)->daily();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
