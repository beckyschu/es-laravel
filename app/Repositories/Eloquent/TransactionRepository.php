<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use Transaction as TransactionManager;
use App\Repositories\Eloquent\Traits\Accountable;
use App\Contracts\TransactionRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TransactionRepository implements TransactionRepositoryInterface
{
    use Accountable;

    public function find($id)
    {
        return Transaction::findOrFail($id);
    }

    public function paginate($page = 1, $limit = 20, $filters = [])
    {
        $query = Transaction::query();

        if ($account = $this->getAccount()) {
            $query = $query->where('transactions.account_id', $account->id);
        }

        if (array_key_exists('search', $filters)) {
            $query
                ->join('users', 'transactions.user_id', '=', 'users.id')
                ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->select('transactions.*')
                ->where('users.first_name', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('users.last_name', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('users.email', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('accounts.name', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('accounts.name', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('transactions.action', 'LIKE', '%'.$filters['search'].'%');
        }

        return $query
            ->orderBy('transactions.created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function paginateForUser($userId, $page = 1, $limit = 20)
    {
        $user = app('App\Contracts\UserRepositoryInterface')->find($userId);

        $query = Transaction::query();

        return $query
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function create($attributes)
    {
        return Transaction::create($attributes);
    }

    public function undo($transaction)
    {
        if (! $transaction instanceof Transaction) {
            $transaction = $this->find($transaction);
        }

        if (! $transaction->canUndo()) {
            throw new ConflictHttpException('This transaction cannot be undone.');
        }

        //If we have an open transaction, change action to be the same as the
        //action that we are undoing (more descriptive)
        if ($currentTransaction = TransactionManager::current()) {
            $currentTransaction->action = $transaction->action;
        }

        /**
         * Here we are looping through each revision that occured within this
         * transaction.
         *
         * For each revision, we grab the actual entity it was performed on.
         * We then "replay" all the revisions that have occured on it but
         * ignore any that were performed within this transaction.
         *
         * Essentially, we are pretending this transaction never happened. This
         * is robust as if revisions have occured *after* this transaction,
         * they will still be retained with a rollback.
         *
         * If anything needs changing due to the rollback, a new set of
         * revisions will be created and assigned to our new "undo"
         * transaction.
         */
        foreach ($transaction->revisions as $revision) {
            if ($entity = $revision->historyOf()) {
                $entity->replayWithoutTransaction($transaction)->save();
            }
        }

        $transaction->update(['status' => 'undone']);

        return $transaction;
    }
}
