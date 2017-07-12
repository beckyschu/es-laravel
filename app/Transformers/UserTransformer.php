<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;
use App\Transformers\AccountTransformer;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['accounts', 'accessible_accounts'];

	public function transform(User $user)
	{
	    return [
	        'id'                      => $user->id,
	        'first_name'              => $user->first_name,
	        'last_name'               => $user->last_name,
	        'email'                   => $user->email,
            'role'                    => $user->role,
            'status'                  => $user->status,
            'default_account'         => $user->default_account,
            'attached_accounts_count' => $user->accounts()->count(),
            'image'                   => $user->image,
            'last_action_at'          => $user->last_action_at ? $user->last_action_at->toIso8601String() : null,
	        'created_at'              => $user->created_at->toIso8601String(),
	        'updated_at'              => $user->updated_at->toIso8601String(),
	    ];
	}

    public function includeAccounts(User $user)
    {
        return $this->collection($user->accounts, new AccountTransformer, 'accounts');
    }

    public function includeAccessibleAccounts(User $user)
    {
        return $this->collection($user->accessible_accounts, new AccountTransformer, 'accounts');
    }
}
