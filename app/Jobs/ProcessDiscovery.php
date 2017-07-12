<?php

namespace App\Jobs;

use App\Models;
use App\Jobs\Job;
use App\Contracts;
use App\Repositories;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessDiscovery extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $discovery;

    protected $originalPrice;

    protected $source;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        Models\Discovery $discovery,
        $originalPrice = null,
        Contracts\Models\Source $source = null
    ) {
        $this->discovery     = $discovery;
        $this->originalPrice = $originalPrice;
        $this->source        = $source;

        $this->onQueue('submissions');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Repositories\Eloquent\DiscoveryRepository $discoveryRepo)
    {
        // Log some info
        Log::info('[Jobs\ProcessDiscovery] [Discovery '.$this->discovery->id.'] Processing discovery');

        // Run process logic
        $discoveryRepo->process($this->discovery, $this->originalPrice, $this->source);
    }
}
