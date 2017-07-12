<?php

namespace App\Transformers;

use App\Models\Transaction;
use App\Transformers\UserTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\AccountTransformer;

class TransactionTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['user', 'account'];

	public function transform(Transaction $transaction)
	{
	    return [
	        'id'         => $transaction->id,
            'type'       => $transaction->type,
            'action'     => $transaction->action,
            'status'     => $transaction->status,
            'can_undo'   => $transaction->canUndo(),
	        'created_at' => $transaction->created_at->toIso8601String(),
	        'updated_at' => $transaction->updated_at->toIso8601String()
	    ];
	}

    public function includeUser(Transaction $transaction)
    {
        if ($user = $transaction->user) {
            return $this->item($user, new UserTransformer, 'users');
        }

        return $this->null();
    }

    public function includeAccount(Transaction $transaction)
    {
        if ($account = $transaction->account) {
            return $this->item($account, new AccountTransformer, 'accounts');
        }

        return $this->null();
    }
}
