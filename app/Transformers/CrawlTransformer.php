<?php

namespace App\Transformers;

use App\Models\Crawl;
use App\Models\Keyword;
use League\Fractal\TransformerAbstract;

class CrawlTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['crawler', 'asset', 'account', 'keyword'];

	public function transform(Crawl $crawl)
	{
	    return [
	        'id'               => $crawl->id,
            'keyword'          => $crawl->keyword->keyword,
	        'predicted_count'  => $crawl->predicted_count,
	        'submission_count' => $crawl->submission_count,
	        'accepted_count'   => $crawl->accepted_count,
	        'rejected_count'   => $crawl->rejected_count,
            'status'           => $crawl->status,
            'generatedStatus'  => $crawl->generatedStatus,
            'crawl_started_at' => $crawl->crawl_started_at ? $crawl->crawl_started_at->toIso8601String() : null,
            'crawl_ended_at'   => $crawl->crawl_ended_at ? $crawl->crawl_ended_at->toIso8601String() : null,
            'created_at'       => $crawl->created_at->toIso8601String(),
            'updated_at'       => $crawl->updated_at->toIso8601String()
	    ];
	}

    public function includeCrawler(Crawl $crawl)
    {
        return $this->item($crawl->crawler, new CrawlerTransformer, 'crawlers');
    }

    public function includeAsset(Crawl $crawl)
    {
        return $this->item($crawl->asset, new AssetTransformer, 'assets');
    }

    public function includeKeyword(Crawl $crawl)
    {
        return $this->item($crawl->keyword, new KeywordTransformer, 'keywords');
    }
}
