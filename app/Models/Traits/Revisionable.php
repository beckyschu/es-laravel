<?php

namespace App\Models\Traits;

use Auth;
use Carbon\Carbon;
use App\Models\Transaction;
use Transaction as TransactionManager;
use Venturecraft\Revisionable\RevisionableTrait;

trait Revisionable
{
    use RevisionableTrait;

    /**
     * Enable creations to be logged as a revision.
     *
     * @var bool
     */
    protected $revisionCreationsEnabled = true;

    /**
     * Transform revision data. This is designed to be overloaded in a child
     * trait for application specific purposes.
     *
     * @param  array $data
     * @return array
     */
    protected function transformRevision(array $data)
    {
        if ($transaction = TransactionManager::current()) {
            $data['transaction_id'] = $transaction->id;
        }

        return $data;
    }

    /**
     * Override the base getUserId method for the AuthStore.
     *
     * @return int|null
     */
    public function getUserId()
    {
        if (Auth::isAuthenticated() && Auth::isUser()) {
            return Auth::getUser()->id;
        }

        return null;
    }

    /**
     * Return this entity in the state it was in at the given datetime.
     *
     * @param  Carbon $datetime
     * @return self
     */
    public function rollbackTo(Carbon $datetime)
    {
        //This entity didn't actually exist at the given datetime
        if ($datetime < $this->created_at) return null;

        //Begin rolling back through revisions
        foreach ($this->revisionHistory->reverse() as $entry)
        {
            //This entry occured before given datetime, stop rolling back
            if ($entry->created_at < $datetime) break;

            //Update attribute specified in key
            $this->setAttribute($entry->key, $entry->oldValue());
        }

        return $this;
    }

    /**
     * Return this entity with all tracked changes rolled back.
     *
     * @return self
     */
    public function rollback()
    {
        return $this->rollbackTo($this->created_at);
    }

    /**
     * Replay the revision history without the given transaction and return
     * the resulting entity.
     *
     * @param  int|Transaction $transaction
     * @return self
     */
    public function replayWithoutTransaction($transaction)
    {
        if ($transaction instanceof Transaction) {
            $transaction = $transaction->id;
        }

        $this->rollback();

        //Begin rolling through revisions and applying changes
        foreach ($this->revisionHistory as $entry)
        {
            //This entry belongs to given transaction, ignore it
            if ($entry->transaction_id == $transaction) continue;

            //Update attribute specified in key
            $this->setAttribute($entry->key, $entry->newValue());
        }

        return $this;
    }
}
