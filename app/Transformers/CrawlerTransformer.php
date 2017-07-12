<?php

namespace App\Transformers;

use App\Models\Crawler;
use League\Fractal\TransformerAbstract;

class CrawlerTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['crawls'];

	public function transform(Crawler $crawler)
	{
	    return [
	        'id'                     => $crawler->id,
	        'platform'               => $crawler->platform,
            'parsehub_project_token' => $crawler->parsehub_project_token,
            'status'                 => $crawler->status,
            'created_at'             => $crawler->created_at->toIso8601String(),
            'updated_at'             => $crawler->updated_at->toIso8601String()
	    ];
	}

    public function includeCrawls(Crawler $crawler)
    {
        $crawls = $crawler->crawls()
            ->orderBy('id', 'DESC') // More granular than created_at
            ->take(50)
            ->get();

        return $this->collection($crawls, new CrawlTransformer, 'crawls');
    }
}
