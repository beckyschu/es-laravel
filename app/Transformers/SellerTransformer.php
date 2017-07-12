<?php

namespace App\Transformers;

use App\Models\Seller;
use League\Fractal\TransformerAbstract;

class SellerTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['account'];

	public function transform(Seller $seller)
	{
	    return [
	        'id'           => $seller->id,
            'name'         => $seller->name,
            'username'     => $seller->username,
            'platform'     => $seller->platform,
            'flag'         => $seller->flag,
            'status'       => (string) $seller->status,
	        'last_seen_at' => $seller->last_seen_at->toIso8601String(),
	        'created_at'   => $seller->created_at->toIso8601String(),
	        'updated_at'   => $seller->updated_at->toIso8601String()
	    ];
	}

    public function includeAccount(Seller $seller)
    {
        return $this->item($seller->account, new AccountTransformer, 'accounts');
    }
}
