<?php

namespace App\Jobs;

use App\Models;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Repositories\Eloquent\DiscoveryRepository;

class CloseDiscoveries extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $crawl;

    /**
     * Create a new job instance.
     *
     * @param  Models\Crawl $crawl
     * @return CloseDiscoveries
     */
    public function __construct(Models\Crawl $crawl)
    {
        $this->onQueue('submissions');

        $this->crawl = $crawl;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Search for open discoveries that should have been found by this crawl
        // but have not been seen for more than a week
        $discoveries = Models\Discovery::query()
            ->where('asset_id', $this->crawl->asset_id)
            ->where('keyword', $this->crawl->keyword->keyword)
            ->whereNotIn('cached_status', ['closed','rejected'])
            ->where('last_seen_at', '<=', Carbon::now()->subDays(7)->format('Y-m-d H:i:s'))
            ->get();

        Log::info('[Jobs\CloseDiscoveries] [Crawl '.$this->crawl->id.'] asset_id:' . $this->crawl->asset_id . ', keyword:' . $this->crawl->keyword->keyword);

        // No results found
        if (! $discoveries->count()) {
            Log::info('[Jobs\CloseDiscoveries] [Crawl '.$this->crawl->id.'] No open discoveries available for closure');
            return;
        }

        // Write to log
        Log::info('[Jobs\CloseDiscoveries] [Crawl '.$this->crawl->id.'] '.$discoveries->count().' open discoveries available for closure');

        // Loop and close discoveries
        foreach ($discoveries as $discovery) {
            $message = 'Closed from '.$discovery->cached_status.' after crawl '.$this->crawl->id.'. Not seen for more than 7 days.';
            Log::info('[Jobs\CloseDiscoveries] [Crawl '.$this->crawl->id.'] [Discovery '.$discovery->id.'] '.$message);
            app(DiscoveryRepository::class)->updateStatus($discovery, 'closed', $message);
        }

        // Write to log
        Log::info('[Jobs\CloseDiscoveries] [Crawl '.$this->crawl->id.'] '.$discoveries->count().' discoveries closed');
    }
}
