<?php

namespace App\Models;

use Venturecraft\Revisionable\Revision;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $fillable = ['user_id', 'account_id', 'type', 'action', 'status'];

    public function canUndo()
    {
        return 'undo' != $this->type && 'undone' != $this->status;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function revisions()
    {
        return $this->hasMany(Revision::class);
    }
}
