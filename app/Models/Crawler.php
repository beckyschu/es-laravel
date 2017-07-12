<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Crawler extends Model
{
    protected $guarded = [];

    public function crawls()
    {
        return $this->hasMany(Crawl::class);
    }

    public function getAvgSubmissionCountAttribute()
    {
        return (int) $this->crawls()->avg('submission_count');
    }

    public function getAvgMemoryUsageAttribute()
    {
        return (int) $this->crawls()->avg('memory_usage');
    }

    public function getAvgMemoryUsageHumanAttribute()
    {
        return format_bytes($this->avg_memory_usage);
    }

    public function getAvgDurationAttribute()
    {
        return (int) $this->crawls()->avg(DB::raw('TIMESTAMPDIFF(SECOND, crawl_started_at, crawl_ended_at)'));
    }

    public function getAvgDurationHumanAttribute()
    {
        return format_seconds($this->avg_duration);
    }

    public function getAvgCrawlTimePerSubmissionAttribute()
    {
        return (int) $this->crawls()
            ->where('status', 'complete')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, crawl_started_at, crawl_ended_at) / result_count'));
    }

    public function getAvgProcessingTimePerSubmissionAttribute()
    {
        return (int) $this->crawls()
            ->where('status', 'complete')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, processing_started_at, processing_ended_at) / submission_count'));
    }

    public function getLastHealthyCrawlAttribute()
    {
        $crawl = $this->crawls()
            ->where('status', 'complete')
            ->orderBy('crawl_ended_at', 'DESC')
            ->first();

        if ($crawl) {
            return $crawl->ended_at;
        }

        return null;
    }

    public function isCrawling()
    {
        return 'crawling' == $this->status;
    }

    public function hasFailed()
    {
        return in_array($this->status, ['failure', 'timeout']);
    }

    public function getRole()
    {
        return app('App\Roles\CrawlerRole');
    }
}
