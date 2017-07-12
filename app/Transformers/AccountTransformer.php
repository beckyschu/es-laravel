<?php

namespace App\Transformers;

use App\Models\Account;
use App\Transformers\UserTransformer;
use App\Transformers\AssetTransformer;
use League\Fractal\TransformerAbstract;

class AccountTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['assets', 'users'];

	public function transform(Account $account)
	{
	    return [
	        'id'              => $account->id,
	        'name'            => $account->name,
	        'address_line1'   => $account->address_line1,
	        'address_line2'   => $account->address_line2,
	        'address_city'    => $account->address_city,
	        'address_state'   => $account->address_state,
	        'address_zip'     => $account->address_zip,
	        'address_country' => $account->address_country,
            'primary_user'    => $account->primary_user,
	        'status'          => $account->status,
	        'created_at'      => $account->created_at->toIso8601String(),
	        'updated_at'      => $account->updated_at->toIso8601String(),
	    ];
	}

    public function includeAssets(Account $account)
    {
        return $this->collection($account->assets, new AssetTransformer, 'assets');
    }

    public function includeUsers(Account $account)
    {
        return $this->collection($account->users, new UserTransformer, 'users');
    }
}
