<?php

namespace App\Repositories\Eloquent\Traits;

use DB;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Discovery;
use Venturecraft\Revisionable\Revision;
use App\Contracts\AccountRepositoryInterface;

trait TimeMachine
{
    protected $rollbackDate;

    protected $rollbackTable;

    public function rollback(Carbon $rollbackDate)
    {
        //Store the rollback date
        $this->rollbackDate = $rollbackDate;

        //Do nothing if we are trying to rollback to the future
        if (! $this->shouldRollback()) {
            return $this;
        }

        //Fetch all revisions for this type that happened after the rollback date
        $revisions = Revision::query()
            ->where('revisionable_type', get_class($this->getModel()))
            ->where('created_at', '>', $rollbackDate)
            ->orderBy('created_at', 'DESC')
            ->get();

        //Nothing has happened since this date, do nothing
        if (! $revisions->count()) {
            return $this;
        }

        //Fetch original table name
        $tableName = $this->getModel()->getTable();

        //Generate rollback table name
        $this->rollbackTable = $tableName.'_'.$this->account->id.'_'.$rollbackDate->format('Ymd');

        //Create a temporary table containing all discoveries for this account
        DB::statement('
            CREATE TEMPORARY TABLE '.$this->rollbackTable.'
                SELECT *
                FROM '.$tableName.'
                WHERE account_id = :account
        ', ['account' => $this->account->id]);

        //Loop revisions and reverse on temporary table
        foreach ($revisions as $revision)
        {
            //We have come across a create revision, remove the entity
            if ('created_at' == $revision->key) {
                DB::delete('
                    DELETE FROM '.$this->rollbackTable.'
                    WHERE id = :id
                ', ['id' => $revision->revisionable_id]);
            }

            //We have come across a field update revision, update the field
            else {
                DB::update('
                    UPDATE '.$this->rollbackTable.'
                    SET '.$revision->key.' = :value
                    WHERE id = :id
                ', [
                    'id'    => $revision->revisionable_id,
                    'value' => $revision->old_value
                ]);
            }
        }

        return $this;
    }

    public function shouldRollback()
    {
        return Carbon::now() > $this->rollbackDate;
    }

    public function getTimeMachineModel()
    {
        $model = $this->getModel();

        if ($this->rollbackTable) {
            $model->setTable($this->rollbackTable);
        }

        return $model;
    }
}
