<?php

namespace App\Repositories\Eloquent;

use DB;
use Cache;
use App\Models;
use Carbon\Carbon;
use Cake\Chronos\Date;
use Illuminate\Support;

use App\Models\Report;
use App\Models\Account;
use App\Helpers\SellerStatusHelper;
use App\Helpers\DiscoveryStatusHelper;
use App\Repositories\Eloquent\Traits\Scoped;
use App\Contracts\ReportRepositoryInterface;
use App\Contracts\SellerRepositoryInterface;
use App\Contracts\DiscoveryRepositoryInterface;
use App\Repositories\Eloquent\Traits\Accountable;

class ReportRepository implements ReportRepositoryInterface
{
    use Accountable, Scoped;

    /**
     * Returns a collection of status counts for each day between the provided
     * dates. Includes first and last day.
     *
     * @param  Cake\Chronos\Date $firstDate
     * @param  Cake\Chronos\Date $lastDate
     * @return Illuminate\Support\Collection
     */
    public function getDailyDiscoveryStatuses(Date $firstDate, Date $lastDate)
    {
        // Start with an empty collection
        $collection = new Support\Collection;

        // Loop through each day in the range
        for ($d = $firstDate; $d <= $lastDate; $d = $d->addDay()) {
            $collection->push(new Support\Fluent([
                'date'    => $d,
                'results' => $this->getDiscoveryStatuses($d)
            ]));
        }

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of status counts that were evident on the given day.
     *
     * @param  Cake\Chronos\Date $date
     * @return Illuminate\Support\Collection
     */
    public function getDiscoveryStatuses(Date $date = null)
    {
        // Default to today
        if (! $date) $date = Date::now();

        // Get base query
        $query = (new Models\Discovery)
            ->setTable('discovery_statuses')
            ->newQuery()
            ->join('discoveries', 'discoveries.id', '=', 'discovery_statuses.discovery_id')
            ->select('discoveries.*', 'discovery_statuses.status');

        // Add validity scope
        $query = $date->isToday() ? $query->validNow() : $query->validOn($date);

        // Add selects
        $query = $query->select(
            'discovery_statuses.status',
            DB::raw("COUNT(discoveries.id) as discovery_count")
        );

        // Group by status
        $query = $query->groupBy('status');

        // Only select discoveries for the current account
        if ($this->getAccount()) {
            $query = $query->where('discoveries.account_id', $this->getAccount()->id);
        }

        // Add platform scope
        if ($this->getScope()->platform) {
            $query = $query->where('discoveries.platform', $this->getScope()->platform);
        }

        // Execute the query
        $result = $query->get();

        // Reorgaise result keys and place into a collection
        $collection = new Support\Collection(array_pluck($result, 'discovery_count', 'status'));

        // Ensure all statuses are accounted for
        foreach (app('App\Helpers\DiscoveryStatusHelper')->getKeys() as $status) {
            if (! $collection->has($status)) {
                $collection->put($status, 0);
            }
        }

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of status counts for each day between the provided
     * dates. Includes first and last day.
     *
     * @param  Cake\Chronos\Date $firstDate
     * @param  Cake\Chronos\Date $lastDate
     * @return Illuminate\Support\Collection
     */
    public function getDailySellerStatuses(Date $firstDate, Date $lastDate)
    {
        // Start with an empty collection
        $collection = new Support\Collection;

        // Loop through each day in the range
        for ($d = $firstDate; $d <= $lastDate; $d = $d->addDay()) {
            $collection->push(new Support\Fluent([
                'date'    => $d,
                'results' => $this->getSellerStatuses($d)
            ]));
        }

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of status counts that were evident on the given day.
     *
     * @param  Cake\Chronos\Date $day
     * @return Illuminate\Support\Collection
     */
    public function getSellerStatuses(Date $date)
    {
        // Get base query
        $query = (new Models\Seller)
            ->setTable('seller_statuses')
            ->newQuery()
            ->join('sellers', 'sellers.id', '=', 'seller_statuses.seller_id')
            ->select('sellers.*', 'seller_statuses.status');

        // Add validity scope
        $query = $date->isToday() ? $query->validNow() : $query->validOn($date);

        // Add selects
        $query = $query->select(
            'seller_statuses.status',
            DB::raw("COUNT(sellers.id) as seller_count")
        );

        // Group by status
        $query = $query->groupBy('status');

        // Only select sellers for the current account
        if ($this->getAccount()) {
            $query = $query->where('sellers.account_id', $this->getAccount()->id);
        }

        // Add platform scope
        if ($this->getScope()->platform) {
            $query = $query->where('sellers.platform', $this->getScope()->platform);
        }

        // Execute the query
        $result = $query->get();

        // Reorgaise result keys and place into a collection
        $collection = new Support\Collection(array_pluck($result, 'seller_count', 'status'));

        // Ensure all statuses are accounted for
        foreach (app('App\Helpers\SellerStatusHelper')->getKeys() as $status) {
            if (! $collection->has($status)) {
                $collection->put($status, 0);
            }
        }

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of average prices by status for each day between the
     * provided dates. Includes first and last day.
     *
     * @param  Cake\Chronos\Date $firstDate
     * @param  Cake\Chronos\Date $lastDate
     * @return Illuminate\Support\Collection
     */
    public function getDailyAvgPrices(Date $firstDate, Date $lastDate)
    {
        // Start with an empty collection
        $collection = new Support\Collection;

        // Loop through each day in the range
        for ($d = $firstDate; $d <= $lastDate; $d = $d->addDay()) {
            $collection->push(new Support\Fluent([
                'date'    => $d,
                'results' => $this->getAvgPrices($d)
            ]));
        }

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of average prices by status that were evident on the
     * given day.
     *
     * @param  Cake\Chronos\Date $date
     * @return Illuminate\Support\Collection
     */
    public function getAvgPrices(Date $date = null)
    {
        // Default to today
        if (! $date) $date = Date::now();

        // Get base query
        $query = (new Models\Discovery)
            ->setTable('discovery_statuses')
            ->newQuery()
            ->join('discoveries', 'discoveries.id', '=', 'discovery_statuses.discovery_id')
            ->select('discoveries.*', 'discovery_statuses.status');

        // Add validity scope
        $query = $date->isToday() ? $query->validNow() : $query->validOn($date);

        // Add selects
        $query = $query->select(
            'status',
            DB::raw("ROUND(AVG(price)) as avg_price")
        );

        // Group by status
        $query = $query->groupBy('status');

        // Only select discoveries for the current account
        if ($this->getAccount()) {
            $query = $query->where('account_id', $this->getAccount()->id);
        }

        // Add platform scope
        if ($this->getScope()->platform) {
            $query = $query->where('platform', $this->getScope()->platform);
        }

        // Execute the query
        $result = $query->get();

        // Reorgaise result keys and place into a collection
        $collection = new Support\Collection(array_pluck($result, 'avg_price', 'status'));

        // Ensure all statuses are accounted for
        foreach (app('App\Helpers\DiscoveryStatusHelper')->getKeys() as $status) {
            if (! $collection->has($status)) {
                $collection->put($status, 0);
            }
        }

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of status counts by platform that were evident on
     * the given day.
     *
     * @param  Cake\Chronos\Date $date
     * @return Illuminate\Support\Collection
     */
    public function getPlatformStatusCounts(Date $date = null)
    {
        // Default to today
        if (! $date) $date = Date::now();

        // Get base query
        $query = (new Models\Discovery)
            ->setTable('discovery_statuses')
            ->newQuery()
            ->join('discoveries', 'discoveries.id', '=', 'discovery_statuses.discovery_id')
            ->select('discoveries.*', 'discovery_statuses.status');

        // Add validity scope
        $query = $date->isToday() ? $query->validNow() : $query->validOn($date);

        // Add selects
        $query = $query->select(
            'status',
            'discoveries.platform',
            DB::raw("COUNT(discoveries.id) as count")
        );

        // Group by status and platform
        $query = $query->groupBy('status', 'platform');

        // Only select discoveries for the current account
        if ($this->getAccount()) {
            $query = $query->where('discoveries.account_id', $this->getAccount()->id);
        }

        // Add platform scope
        if ($this->getScope()->platform) {
            $query = $query->where('discoveries.platform', $this->getScope()->platform);
        }

        // Execute the query
        $result = $query->get();

        // Init an empty collection
        $collection = new Support\Collection($result);

        // Group the collection by platform
        $collection = $collection->groupBy('platform');

        // Ensure all platforms are accounted for
        foreach (app('App\Helpers\PlatformHelper')->getKeys() as $platform) {
            if (! $collection->has($platform)) {
                $collection->put($platform, new Support\Collection);
            }
        }

        // Loop each platform to make adjustments
        $collection = $collection->map(function ($platform) {

            // Reorgnaise keys
            $platform = $platform->pluck('count', 'status');

            // Ensure all statuses are accounted for
            foreach (app('App\Helpers\DiscoveryStatusHelper')->getKeys() as $status) {
                if (! $platform->has($status)) {
                    $platform->put($status, 0);
                }
            }

            return $platform;

        });

        // Return our collection
        return $collection;
    }

    /**
     * Returns a collection of top sellers that were evident on the given day.
     *
     * @param  Cake\Chronos\Date $date
     * @return Illuminate\Support\Collection
     */
    public function getTopSellers(Date $date = null)
    {
        // Default to today
        if (! $date) $date = Date::now();

        // Setup base query
        $query = Models\Discovery::query();

        // Add selects
        $query = $query->select(
            'discoveries.seller_id as seller_id',
            'sellers.username as seller_username',
            DB::raw("COUNT(discoveries.id) as discovery_count")
        );

        // Add sellers join (for username)
        $query = $query->join('sellers', 'discoveries.seller_id', '=', 'sellers.id');

        // Add order and limit
        $query = $query->orderBy('discovery_count', 'DESC')->take(6);

        // Group by seller
        $query = $query->groupBy('seller_id', 'seller_username');

        // Only select discoveries for the current account
        if ($this->getAccount()) {
            $query = $query->where('discoveries.account_id', $this->getAccount()->id);
        }

        // Add platform scope
        if ($this->getScope()->platform) {
            $query = $query->where('discoveries.platform', $this->getScope()->platform);
        }

        // Execute the query
        $result = $query->get();

        // Return our collection
        return new Support\Collection($result);
    }

    /**
     * Returns a collection of location groups.
     *
     * @param  Cake\Chronos\Date $date
     * @return Illuminate\Support\Collection
     */
    public function getLocationBreakdown(Date $date)
    {
        // Default to today
        if (! $date) $date = Date::now();

        // Setup base query
        $query = Models\Discovery::query();

        // Date is not today
        if (! $date->isToday()) {
            $query = $query->withoutGlobalScope(Models\Scopes\LatestRevisionScope::class);
            $query = $query->validOn($date);
        }

        // Add selects
        $query = $query->select(
            'country',
            DB::raw("COUNT(discoveries.id) as discovery_count")
        );

        // Order by count
        $query = $query->orderBy('discovery_count', 'DESC');

        // Group by seller
        $query = $query->groupBy('country');

        // Only select discoveries for the current account
        if ($this->getAccount()) {
            $query = $query->where('account_id', $this->getAccount()->id);
        }

        // Add status scope
        if ($this->getScope()->status) {
            $query = $query->where('status', $this->getScope()->status);
        }

        // Add platform scope
        if ($this->getScope()->platform) {
            $query = $query->where('platform', $this->getScope()->platform);
        }

        // Execute the query
        $result = $query->get();

        // Create a collection from results
        $collection = new Support\Collection($result);

        // Resort by count
        $collection = $collection->sortBy(function ($group) {
            return $group->discovery_count;
        })->reverse();

        // Grab the sum of all discovery counts
        $sum = $collection->sum(function ($group) {
            return $group->discovery_count;
        });

        // Select only the first four groups
        $collection = $collection->take(4);

        // Calculate the sum again
        $newSum = $collection->sum(function ($group) {
            return $group->discovery_count;
        });

        // There were items for the "other group"
        if ($sum > $newSum) {
            $collection->push(new Support\Fluent([
                'country'         => 'other',
                'discovery_count' => ($sum - $newSum)
            ]));
        }

        // Organise labels
        $collection = $collection->map(function ($group)
        {
            if (! $group->country) {
                $label = 'Unknown';
            } elseif ('other' == $group->country) {
                $label = ucfirst($group->country);
            } elseif ($name = app('App\Helpers\CountryHelper')->getName($group->country)) {
                $label = $name;
            } else {
                $label = $group->country;
            }

            return [
                'label' => $label,
                'count' => $group->discovery_count
            ];
        });

        // Return our collection
        return $collection->values();
    }
}
