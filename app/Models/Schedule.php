<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['schedule'];

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }

    public function crawler()
    {
        return $this->belongsTo(Crawler::class);
    }
}
