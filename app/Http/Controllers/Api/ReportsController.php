<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\Contracts;
use Cake\Chronos\Date;
use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    protected $reports;

    public function __construct(Contracts\ReportRepositoryInterface $reports)
    {
        $this->reports = $reports;
    }

    /**
     * Returns a collection of discovery status counts on a given date.
     */
    public function discoveryStatuses()
    {
        // Fetch discovery statuses
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getDiscoveryStatuses();

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }

    /**
     * Returns a collection of daily discovery status counts between two dates.
     */
    public function dailyDiscoveryStatuses()
    {
        // Capture dates from request
        $firstDay = request()->has('first_day') ? new Date(request()->get('first_day')) : Date::now()->subDays(10);
        $lastDay  = request()->has('last_day')  ? new Date(request()->get('last_day'))  : Date::now();

        // Set platform scope
        if (request()->has('platform')) {
            $this->reports->getScope()->platform = request()->get('platform');
        }

        // Fetch discovery statuses
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getDailyDiscoveryStatuses($firstDay, $lastDay);

        // Transform date objects to ISO format
        $collection = $collection->map(function ($item) {
            $item->date = $item->date->toIso8601String();
            return $item;
        });

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }

    /**
     * Returns a collection of daily seller status counts between two dates.
     */
    public function dailySellerStatuses()
    {
        // Capture dates from request
        $firstDay = request()->has('first_day') ? new Date(request()->get('first_day')) : Date::now()->subDays(10);
        $lastDay  = request()->has('last_day')  ? new Date(request()->get('last_day'))  : Date::now();

        // Set platform scope
        if (request()->has('platform')) {
            $this->reports->getScope()->platform = request()->get('platform');
        }

        // Fetch seller statuses
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getDailySellerStatuses($firstDay, $lastDay);

        // Transform date objects to ISO format
        $collection = $collection->map(function ($item) {
            $item->date = $item->date->toIso8601String();
            return $item;
        });

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }

    /**
     * Returns a collection of daily average prices by status between two dates.
     */
    public function dailyAvgPrices()
    {
        // Capture dates from request
        $firstDay = request()->has('first_day') ? new Date(request()->get('first_day')) : Date::now()->subDays(10);
        $lastDay  = request()->has('last_day')  ? new Date(request()->get('last_day'))  : Date::now();

        // Set platform scope
        if (request()->has('platform')) {
            $this->reports->getScope()->platform = request()->get('platform');
        }

        // Fetch avg prices
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getDailyAvgPrices($firstDay, $lastDay);

        // Transform date objects to ISO format
        $collection = $collection->map(function ($item) {
            $item->date = $item->date->toIso8601String();
            return $item;
        });

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }

    /**
     * Returns a collection of status counts by platform.
     */
    public function platformStatusCounts()
    {
        // Capture day from request
        $day = request()->has('day') ? new Date(request()->get('day')) : Date::now();

        // Fetch platform status counts
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getPlatformStatusCounts($day);

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }

    /**
     * Returns a collection of 6 top sellers.
     */
    public function topSellers()
    {
        // Capture day from request
        $day = request()->has('day') ? new Date(request()->get('day')) : Date::now();

        // Set platform scope
        if (request()->has('platform')) {
            $this->reports->getScope()->platform = request()->get('platform');
        }

        // Fetch top sellers
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getTopSellers($day);

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }

    /**
     * Returns a collection of grouped discovery location countries.
     */
    public function locationBreakdown()
    {
        // Capture day from request
        $day = request()->has('day') ? new Date(request()->get('day')) : Date::now();

        // Set status scope
        if (request()->has('status')) {
            $this->reports->getScope()->status = request()->get('status');
        }

        // Set platform scope
        if (request()->has('platform')) {
            $this->reports->getScope()->platform = request()->get('platform');
        }

        // Fetch location groups
        $collection = $this->reports
            ->setAccount(Auth::getAccount())
            ->getLocationBreakdown($day);

        // Return JSON response
        return response()->json([
            'data' => $collection
        ]);
    }
}
