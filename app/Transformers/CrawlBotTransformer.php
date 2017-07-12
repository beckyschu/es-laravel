<?php

namespace App\Transformers;

use App\Models\Crawl;
use League\Fractal\TransformerAbstract;

class CrawlBotTransformer extends TransformerAbstract
{
    protected $keywordSettings;

	public function transform(Crawl $crawl)
	{
	    return [
	        'id'                      => $crawl->id,
            'crawler_id'              => $crawl->crawler_id,
            'keyword_id'              => $crawl->keyword_id,
            'status'                  => $crawl->status,
            'generated_status'        => $crawl->generated_status,
            'keyword'                 => $crawl->keyword->keyword,
            'counter_keywords'        => $crawl->asset->counter_keywords_array,
            'parsehub_project_token'  => $crawl->crawler->parsehub_project_token,
            'parsehub_start_url'      => $crawl->setting ? $crawl->setting->start_url : null,
            'parsehub_start_template' => $crawl->setting ? $crawl->setting->start_template : null,
	    ];
	}
}
