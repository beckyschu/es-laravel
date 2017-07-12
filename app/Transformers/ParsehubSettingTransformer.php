<?php

namespace App\Transformers;

use App\Models\ParsehubSetting;
use League\Fractal\TransformerAbstract;

class ParsehubSettingTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['crawler'];

	public function transform(ParsehubSetting $setting)
	{
	    return [
	        'id'             => $setting->id,
            'crawler_id'     => $setting->crawler_id,
	        'start_url'      => $setting->start_url,
	        'start_template' => $setting->start_template,
	        'created_at'     => $setting->created_at ? $setting->created_at->toIso8601String() : null,
	        'updated_at'     => $setting->updated_at ? $setting->updated_at->toIso8601String() : null
	    ];
	}

    public function includeCrawler(ParsehubSetting $setting)
    {
        return $this->item($setting->crawler, new CrawlerTransformer);
    }
}
