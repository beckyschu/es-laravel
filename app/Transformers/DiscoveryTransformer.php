<?php

namespace App\Transformers;

use App\Models\Discovery;
use App\Transformers\AssetTransformer;
use App\Transformers\SellerTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\RevisionTransformer;

class DiscoveryTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['account', 'asset', 'seller', 'statuses'];

	public function transform(Discovery $discovery)
	{
	    return [
            'id'            => $discovery->id,
            'title'         => $discovery->title,
            'sku'           => $discovery->sku,
            'category'      => $discovery->category,
            'keyword'       => $discovery->keyword,
            'platform'      => $discovery->platform,
            'origin'        => $discovery->origin ?: null,
            'price'         => $discovery->price ?: null,
            'picture'       => $discovery->picture ?: null,
            'url'           => $discovery->url,
            'listing_url'   => $discovery->listing_url,
            'qty_available' => $discovery->qty_available,
            'qty_sold'      => $discovery->qty_sold,
            'status'        => (string) $discovery->status,
            'comment'       => $discovery->comment,
            'created_at'    => $discovery->created_at->toIso8601String(),
            'updated_at'    => $discovery->updated_at->toIso8601String(),
            'last_seen_at'  => $discovery->last_seen_at ? $discovery->last_seen_at->toIso8601String() : null,
	    ];
	}

    public function includeAccount(Discovery $discovery)
    {
        return $this->item($discovery->account, new AccountTransformer, 'accounts');
    }

    public function includeAsset(Discovery $discovery)
    {
        return $this->item($discovery->asset, new AssetTransformer, 'assets');
    }

    public function includeSeller(Discovery $discovery)
    {
        if ($discovery->seller) {
            return $this->item($discovery->seller, new SellerTransformer, 'sellers');
        }

        return $this->null();
    }

    public function includeStatuses(Discovery $discovery)
    {
        return $this->collection($discovery->statuses, new DiscoveryStatusTransformer, 'discoveryStatuses');
    }
}
