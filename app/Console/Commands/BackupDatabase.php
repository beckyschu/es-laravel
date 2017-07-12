<?php

namespace App\Console\Commands;

use Artisan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'shark:backup';

    protected $description = 'Backup database to default location';

    public function handle()
    {
        return Artisan::call('db:backup', [
            '--database'        => 'mysql',
            '--destination'     => 'dropbox',
            '--destinationPath' => '/'.env('APP_ENV').'/ipshark_'.env('APP_ENV').'_'.Carbon::now()->format('Y-m-d_His'),
            '--compression'     => 'gzip'
        ]);
    }
}
