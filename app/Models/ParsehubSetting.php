<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParsehubSetting extends Model
{
    protected $fillable = ['start_url', 'start_template'];

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }

    public function crawler()
    {
        return $this->belongsTo(Crawler::class);
    }
}
