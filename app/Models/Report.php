<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = ['pdf'];

    public $incrementing = false;

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function getPdfAttribute()
    {
        if (app('filesystem')->disk('public')->exists('reports/'.$this->id.'.pdf')) {
            return url('storage/reports/'.$this->id.'.pdf');
        }

        return null;
    }
}
