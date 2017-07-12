<?php

namespace App\Repositories\Eloquent;

use App\Jobs;
use App\Models;
use Carbon\Carbon;
use App\Models\Crawl;
use App\Models\Crawler;
use Illuminate\Support\Facades\Log;
use App\Events\Broadcast\CrawlWasUpdated;
use App\Contracts\CrawlRepositoryInterface;
use App\Contracts\AssetRepositoryInterface;
use App\Contracts\CrawlerRepositoryInterface;
use LaravelArdent\Ardent\InvalidModelException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CrawlRepository implements CrawlRepositoryInterface
{
    public function find($id)
    {
        return Crawl::findOrFail($id);
    }

    public function schedule($assetId, $crawlerIds, $keywordIds, $mode = 'light')
    {
        // Allow array's of ID's to be scheduled
        if (! is_array($crawlerIds) && ! $crawlerIds instanceof \Illuminate\Support\Collection) {
            $crawlerIds = [$crawlerIds];
        }

        // Allow array's of keywords to be scheduled
        if (! is_array($keywordIds) && ! $keywordIds instanceof \Illuminate\Support\Collection) {
            $keywordIds = [$keywordIds];
        }

        // Fetch the requested asset
        $asset = app('App\Contracts\AssetRepositoryInterface')->find($assetId);

        // Loop provided crawlers
        foreach ($crawlerIds as $crawlerId)
        {
            // Loop provided keywords
            foreach ($keywordIds as $keywordId)
            {
                // Fetch the requested crawler
                $crawler = app('App\Contracts\CrawlerRepositoryInterface')->find($crawlerId);

                // Create the crawl
                $crawl = Crawl::create([
                    'keyword_id' => $keywordId,
                    'crawler_id' => $crawlerId,
                    'asset_id'   => $asset->id,
                    'mode'       => $mode,
                    'status'     => 'scheduled'
                ]);

                // Add to queue for processing
                dispatch(new Jobs\RunCrawlJob($crawl));

                // Log some info
                Log::info('[Repositories\CrawlRepository] [Crawl '.$crawl->id.'] Dispatched Jobs\RunCrawlJob');
            }
        }

        return true;
    }

    /**
     * Start the given crawl.
     *
     * @param  Crawl|int $crawl
     * @return Crawl
     */
    public function start($crawl)
    {
        // Fetch crawl entity
        if (! $crawl instanceof Crawl) {
            $crawl = $this->find($crawl);
        }

        // Update crawler
        app(CrawlerRepositoryInterface::class)->update($crawl->crawler, ['status' => 'crawling']);

        // Update crawl
        $this->update($crawl, [
            'status'           => 'crawling',
            'crawl_started_at' => Carbon::now()
        ]);

        // Log some info
        Log::info('[Repositories\CrawlRepository] [Crawl '.$crawl->id.'] Started crawl');

        // Return our crawl
        return $crawl;
    }

    /**
     * Stop the given crawl.
     *
     * @param  Crawl|int $crawl
     * @param  array     $attributes
     * @return Crawl
     */
    public function stop($crawl, $attributes = [])
    {
        // Fetch crawl entity
        if (! $crawl instanceof Crawl) {
            $crawl = $this->find($crawl);
        }

        // Crawl has already been completed
        if ('crawling' !== $crawl->status) {
            throw new ConflictHttpException('Crawl has already been completed.');
        }

        // Update crawler
        app(CrawlerRepositoryInterface::class)->update($crawl->crawler, ['status' => 'healthy']);

        // Update crawl
        $this->update($crawl, array_merge($attributes, [
            'crawl_ended_at' => Carbon::now(),
            'status'         => 'complete'
        ]));

        // Schedule close pending discoveries job
        dispatch(new Jobs\CloseDiscoveries($crawl));

        // Log some info
        Log::info('[Repositories\CrawlRepository] [Crawl '.$crawl->id.'] Stopped crawl and dispatched Jobs\ClosePendingDiscoveries');

        // Return crawl
        return $crawl;
    }

    /**
     * Ping the given crawl.
     *
     * @param  Crawl|int $crawl
     * @param  array     $attributes
     * @return Crawl
     */
    public function ping($crawl, $attributes = [])
    {
        // Fetch crawl entity
        if (! $crawl instanceof Crawl) {
            $crawl = $this->find($crawl);
        }

        // Crawl has already been completed
        if ('crawling' !== $crawl->status) {
            throw new ConflictHttpException('Crawl has already been completed.');
        }

        // Update attributes
        $this->update($crawl, $attributes);

        // Return crawl
        return $crawl;
    }

    /**
     * Cancel the given crawl.
     *
     * @param  Crawl|int $crawl
     * @return Crawl
     */
    public function cancel($crawl)
    {
        // Fetch crawl entity
        if (! $crawl instanceof Crawl) {
            $crawl = $this->find($crawl);
        }

        // Crawl cannot be cancelled
        if (! in_array($crawl->status, ['crawling', 'scheduled'])) {
            throw new ConflictHttpException('Crawl cannot be cancelled with status '.$this->status.'.');
        }

        // Update the status
        $this->update($crawl, ['status' => 'cancelled']);

        // Return the crawl
        return $crawl;
    }

    /**
     * Fail the given crawl.
     *
     * @param  Crawl|int $crawl
     * @return Crawl
     */
    public function fail($crawl)
    {
        // Fetch crawl entity
        if (! $crawl instanceof Crawl) {
            $crawl = $this->find($crawl);
        }

        // Update crawler
        app(CrawlerRepositoryInterface::class)->update($crawl->crawler, ['status' => 'failure']);

        // Update crawl
        $this->update($crawl, ['status' => 'failure']);

        // Log some info
        Log::error('[Repositories\CrawlRepository] [Crawl '.$crawl->id.'] Crawl failed');

        // Return the entity
        return $crawl;
    }

    /**
     * Update the given crawl.
     *
     * @param  Crawl|int $crawl
     * @param  array     $attributes
     * @return Crawl
     */
    public function update($crawl, $attributes)
    {
        // Fetch crawl entity
        if (! $crawl instanceof Crawl) {
            $crawl = $this->find($crawl);
        }

        // Update only the provided attributes
        $crawl->fill($attributes);
        $crawl->save();

        // Fire a broadcast event
        event(new CrawlWasUpdated($crawl));

        // Return the entity
        return $crawl;
    }

    /**
     * Increment the provided column.
     *
     * @param  Models\Crawl $crawl
     * @param  string       $column
     * @return void
     */
    public function increment(Models\Crawl $crawl, $column)
    {
        // Increment provided column
        $crawl->increment($column);

        // Fire a broadcast event
        event(new CrawlWasUpdated($crawl));

        // Return the crawl
        return $crawl;
    }
}
