<?php

namespace App\Models;

use App\Contracts;
use Carbon\Carbon;
use App\Repositories;
use Illuminate\Database\Eloquent\Model;

class Crawl extends Model implements Contracts\Models\Source
{
    public $guarded = [];

    public $dates = [
        'crawl_started_at',
        'crawl_ended_at',
        'processing_started_at',
        'processing_ended_at',
        'last_ping_at',
        'created_at',
        'updated_at'
    ];

    public function crawler()
    {
        return $this->belongsTo(Crawler::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class)->withTrashed();
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function failures()
    {
        return $this->hasMany(Submission::class)->where('status', 'failure');
    }

    public function getSettingAttribute()
    {
        return ParsehubSetting::query()
            ->where('keyword_id', $this->keyword_id)
            ->where('crawler_id', $this->crawler_id)
            ->first();
    }

    public function getGeneratedStatusAttribute()
    {
        if (
            'complete' == $this->status
            && $this->submission_count < 0
            && ($this->accepted_count + $this->rejected_count) == 0
        ) return 'waiting';

        if (
            'complete' == $this->status
            && ($this->accepted_count + $this->rejected_count) < $this->submission_count
        ) return 'processing';

        return $this->status;
    }

    public function getLogAttribute()
    {
        return base_path('storage/logs/crawls/'.$this->id.'.txt');
    }

    public function getLogContentsAttribute()
    {
        if (file_exists($this->log)) {
            $contents = file_get_contents($this->log);
            $modified = \Carbon\Carbon::createFromTimestamp(filemtime($this->log), 'UTC');

            return [$contents,$modified];
        }

        return null;
    }

    /**
     * Return whether or not this crawl should be run by a worker.
     *
     * @return bool
     */
    public function shouldRun()
    {
        return 'scheduled' == $this->status;
    }

    /////////////////////////////////
    // App\Contracts\Models\Source //
    /////////////////////////////////

    /**
     * Increment the submission count for this source.
     *
     * @return void
     */
    public function incrementSubmissionCount()
    {
        return app(Repositories\Eloquent\CrawlRepository::class)->increment($this, 'submission_count');
    }

    /**
     * Increment the accepted count for this source.
     *
     * @return void
     */
    public function incrementAcceptedCount()
    {
        return app(Repositories\Eloquent\CrawlRepository::class)->increment($this, 'accepted_count');
    }

    /**
     * Increment the rejected count for this source.
     *
     * @return void
     */
    public function incrementRejectedCount()
    {
        return app(Repositories\Eloquent\CrawlRepository::class)->increment($this, 'rejected_count');
    }

    /**
     * Return an asset for this source.
     *
     * @return App\Models\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Return an platform for this source.
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->crawler->platform;
    }

    /**
     * Get the discovery comment that should be emmited for this source.
     *
     * @return string
     */
    public function getDiscoveryComment()
    {
        return 'Discovered in crawl '.$this->id;
    }
}
