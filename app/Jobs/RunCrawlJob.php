<?php

namespace App\Jobs;

use App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunCrawlJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $crawl;

    /**
     * Create a new job instance.
     *
     * @param  Models\Crawl $crawl
     * @return RunCrawlJob
     */
    public function __construct(Models\Crawl $crawl)
    {
        $this->onQueue('crawls');

        $this->crawl = $crawl;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // This crawl should not be run for some reason (probably been cancelled)
        if (! $this->crawl->shouldRun()) return;

        // Resolve command name
        $commandName = 'crawl:'.$this->crawl->crawler->platform;

        // Use ParseHub crawler for applicable crawls
        if ($this->crawl->crawler->parsehub_project_token) {
            $commandName = 'crawl:parsehub';
        }

        // Resolve path to binary
        $binary = base_path('artisan');

        // Build command
        $command = "bash -o pipefail -c \"php -d max_execution_time=7200 {$binary} {$commandName} {$this->crawl->id} 2>&1 | tee -a {$this->crawl->log} >/dev/null 2>&1\"";

        // Initiate log file
        file_put_contents($this->crawl->log, $command."\n\n");

        // Log some info
        Log::info('[Jobs\RunCrawlJob] [Crawl '.$this->crawl->id.'] Running command '.$command);

        // Execute the crawler binary (send stdOut and stdErr realtime to log file via tee)
        exec($command, $output, $return);

        // Append exit code
        file_put_contents($this->crawl->log, "\n\nExit code: ".$return, FILE_APPEND);

        // Return status was non-zero, fail the job
        if (0 < $return) {

            app('App\Contracts\CrawlRepositoryInterface')->fail($this->crawl);

            // Commented out so that the actual job doesn't fail & rerun, if the
            // crawl fails it should fail forever
            // throw new \RuntimeException('Crawler failed with exit code '.$return.'.');

        }
    }
}
