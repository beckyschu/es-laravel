<?php

namespace App\Transformers;

use App\Models\Schedule;
use League\Fractal\TransformerAbstract;

class ScheduleTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['crawler'];

	public function transform(Schedule $schedule)
	{
	    return [
	        'id'         => $schedule->id,
            'crawler_id' => $schedule->crawler_id,
	        'schedule'   => $schedule->schedule,
	        'created_at' => $schedule->created_at ? $schedule->created_at->toIso8601String() : null,
	        'updated_at' => $schedule->updated_at ? $schedule->updated_at->toIso8601String() : null
	    ];
	}

    public function includeCrawler(Schedule $schedule)
    {
        return $this->item($schedule->crawler, new CrawlerTransformer);
    }
}
