<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Ardent
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'counter_keywords', 'ebay_category', 'status'
    ];

    public static $rules = [
        'name'   => 'required',
        'status' => 'required',
    ];

    public function discoveries()
    {
        return $this->hasMany(Discovery::class);
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function crawls()
    {
        return $this->hasMany(Crawl::class);
    }

    public function getCounterKeywordsArrayAttribute()
    {
        if ($keywords = trim($this->attributes['counter_keywords'])) {
            return explode(',', $keywords);
        }

        return [];
    }

    public function setCounterKeywordsAttribute($keywords)
    {
        if (is_array($keywords)) {
            $keywords = implode(',', $keywords);
        }

        $this->attributes['counter_keywords'] = $keywords;
    }
}
