<?php

namespace App\Events\Broadcast;

use App\Models\Scan;
use App\Events\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ScanWasUpdated extends Event implements ShouldBroadcastNow
{
    use SerializesModels;

    public $scan;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Scan $scan)
    {
        $this->scan = $scan;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return new Channel('scan.'.$this->scan->id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return ['scan' => app('App\Transformers\ScanTransformer')->transform($this->scan)];
    }
}
