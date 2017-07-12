<?php

namespace App\Console\Commands;

use App\Jobs;
use App\Models;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Contracts\ReportRepositoryInterface;

class ScheduleCrawls extends Command
{
    protected $signature = 'shark:schedule-crawls {--crawler=} {--schedule=daily}';

    protected $description = 'Schedule platform crawls for all assets';

    public function handle()
    {

        $crawlerIds = $this->option('crawler');

        // Fetch schedule
        $targetSchedule = $this->option('schedule');

        // Fetch all matching schedules
        $schedules = Models\Schedule::query()
            ->with('keyword', 'keyword.asset')
            ->where('schedule', $targetSchedule);

        // Fetch manually defined crawler ID
        if ($crawlerId = $this->option('crawler')) {
            $schedules = $schedules->where('crawler_id', $crawlerId);
        }

        // Loop matching schedules
        foreach ($schedules->get() as $schedule) {

            // Asset is not active
            if ('active' !== $schedule->keyword->asset->status) continue;

            // Schedule crawl
            app('App\Contracts\CrawlRepositoryInterface')->schedule(
                $schedule->keyword->asset->id,
                $schedule->crawler_id,
                $schedule->keyword_id
            );

        }
    }
}
