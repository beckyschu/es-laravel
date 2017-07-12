<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Keyword extends Model
{
    use SoftDeletes;

    protected $fillable = ['keyword'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function settings()
    {
        return $this->hasMany(ParsehubSetting::class);
    }

    /**
     * Return a collection of settings including empty sets for other ParseHub
     * enabled crawlers.
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getAllSettingsAttribute()
    {
        $crawlers = Crawler::whereNotNull('parsehub_project_token')->get();
        $settings = $this->settings->keyBy('crawler_id');

        $emptySetting = new ParsehubSetting;
        $emptySetting->keyword_id = $this->id;

        $crawlers->each(function ($crawler) use ($settings, $emptySetting) {
            if (! $settings->has($crawler->id))
            {
                $setting = $emptySetting->replicate();
                $setting->crawler_id = $crawler->id;
                $settings->put($crawler->id, $setting);
            }
        });

        return $settings;
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Return a collection of settings including empty sets for other ParseHub
     * enabled crawlers.
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getAllSchedulesAttribute()
    {
        $crawlers = Crawler::all();
        $schedules = $this->schedules->keyBy('crawler_id');

        $emptySchedule = new Schedule;
        $emptySchedule->keyword_id = $this->id;

        $crawlers->each(function ($crawler) use ($schedules, $emptySchedule) {
            if (! $schedules->has($crawler->id))
            {
                $schedule = $emptySchedule->replicate();
                $schedule->crawler_id = $crawler->id;
                $schedules->put($crawler->id, $schedule);
            }
        });

        return $schedules;
    }
}
