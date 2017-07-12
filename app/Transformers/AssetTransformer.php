<?php

namespace App\Transformers;

use App\Models\Asset;
use League\Fractal\TransformerAbstract;

class AssetTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['account', 'crawls', 'keywords'];

	public function transform(Asset $asset)
	{
	    return [
	        'id'               => $asset->id,
	        'name'             => $asset->name,
	        'description'      => $asset->description,
            'counter_keywords' => $asset->counter_keywords_array,
            'ebay_category'    => $asset->ebay_category,
	        'status'           => $asset->status,
	        'created_at'       => $asset->created_at->toIso8601String(),
	        'updated_at'       => $asset->updated_at->toIso8601String()
	    ];
	}

    public function includeAccount(Asset $asset)
    {
        return $this->item($asset->account()->withTrashed()->first(), new AccountTransformer, 'accounts');
    }

    public function includeKeywords(Asset $asset)
    {
        return $this->collection($asset->keywords, new KeywordTransformer, 'keywords');
    }

    public function includeCrawls(Asset $asset)
    {
        $crawls = $asset->crawls()
            ->orderBy('id', 'DESC') // More granular than created_at
            ->take(50)
            ->get();

        return $this->collection($crawls, new CrawlTransformer, 'crawls');
    }
}
