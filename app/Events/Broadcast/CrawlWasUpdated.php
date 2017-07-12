<?php

namespace App\Events\Broadcast;

use App\Models\Crawl;
use App\Events\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CrawlWasUpdated extends Event implements ShouldBroadcastNow
{
    use SerializesModels;

    public $crawl;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Crawl $crawl)
    {
        $this->crawl = $crawl;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return new Channel('crawl.'.$this->crawl->id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return ['crawl' => app('App\Transformers\CrawlTransformer')->transform($this->crawl)];
    }
}
