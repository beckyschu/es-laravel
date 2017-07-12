<?php

namespace App\Transformers;

use App\Models\Keyword;
use League\Fractal\TransformerAbstract;

class KeywordTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['asset', 'configs', 'settings', 'all_settings', 'schedules', 'all_schedules'];

	public function transform(Keyword $keyword)
	{
	    return [
	        'id'         => $keyword->id,
	        'keyword'    => $keyword->keyword,
            'schedule'   => $keyword->schedule,
	        'created_at' => $keyword->created_at->toIso8601String(),
	        'updated_at' => $keyword->updated_at->toIso8601String()
	    ];
	}

    public function includeAsset(Keyword $keyword)
    {
        return $this->item($keyword->asset, new AssetTransformer, 'assets');
    }

    public function includeSettings(Keyword $keyword)
    {
        return $this->collection($keyword->settings, new ParsehubSettingTransformer);
    }

    public function includeAllSettings(Keyword $keyword)
    {
        return $this->collection($keyword->all_settings, new ParsehubSettingTransformer);
    }

    public function includeSchedules(Keyword $keyword)
    {
        return $this->collection($keyword->schedules, new ScheduleTransformer);
    }

    public function includeAllSchedules(Keyword $keyword)
    {
        return $this->collection($keyword->all_schedules, new ScheduleTransformer);
    }
}
