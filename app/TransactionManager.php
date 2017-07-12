<?php

namespace App;

use Auth;
use App\Contracts\TransactionRepositoryInterface;

class TransactionManager
{
    protected $transaction;

    public function open($type, $action)
    {
        return $this->transaction = app(TransactionRepositoryInterface::class)->create([
            'user_id'    => Auth::getUserId(),
            'account_id' => Auth::getAccountId(),
            'type'       => $type,
            'action'     => $action,
            'status'     => 'unrealised'
        ]);
    }

    /**
     * Ping a transaction whenever something happens within it.
     *
     * @return void
     */
    public function ping()
    {
        if ($transaction = $this->current()) {
            $transaction->status = 'realised';
        }
    }

    public function close()
    {
        // We have an active transaction
        if ($transaction = $this->current()) {

            // Nothing actually happened in this transaction, destroy it
            if ('realised' !== $transaction->status) {
                $transaction->delete();
                return $this->transaction = null;
            }

            // Save transaction updates
            $transaction->save();

        }

        // Clear active transaction
        $this->transaction = null;

        // Return the realised transaction
        return $transaction;
    }

    public function current()
    {
        return $this->transaction;
    }

    public function currentId()
    {
        if ($this->transaction) {
            return $this->transaction->id;
        }
        
        return null;
    }
}
