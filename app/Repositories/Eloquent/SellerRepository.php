<?php

namespace App\Repositories\Eloquent;

use DB;
use App\Jobs;
use Exception;
use App\Models;
use Carbon\Carbon;
use App\Contracts;
use App\Repositories;
use App\Models\Crawl;
use App\Models\Seller;
use App\Helpers\SellerStatusHelper;
use App\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\SellerRepositoryInterface;
use App\Repositories\Eloquent\Traits\Scoped;
use App\Repositories\Eloquent\Traits\Accountable;
use App\Repositories\Eloquent\Traits\TimeMachine;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SellerRepository implements SellerRepositoryInterface
{
    use Accountable, Scoped;

    /**
     * Find seller with the given ID.
     *
     * @param  string $id
     * @return Models\Seller
     */
    public function find($id)
    {
        return Seller::findOrFail($id);
    }

    /**
     * Discover the given username.
     *
     * This method will either create or update a seler depending on if one
     * already exists with a matching username and platform.
     *
     * @param  array                   $attributes
     * @param  Contracts\Models\Source $source
     * @param  Models\Asset            $asset
     * @return Models\Seller
     */
    public function discover($username, Contracts\Models\Source $source, Models\Asset $asset)
    {
        // Search for seller with this username, platform and account
        $seller = Models\Seller::query()
            ->where('username', $username)
            ->where('platform', $source->getPlatform())
            ->where('account_id', $asset->account->id)
            ->first();

        // Seller does not exist yet, create it
        if (! $seller) {

            // Create a new seller
            $seller = new Models\Seller;

            // Set platform
            $seller->platform = $source->getPlatform();

            // Set initial status
            $seller->setStatus('new', 'Initial discovered status');

            // Associate relationships
            $seller->account()->associate($asset->account);

        }

        // Fill attributes
        $seller = $this->fill($seller, [
            'name'         => $username,
            'username'     => $username,
            'last_seen_at' => Carbon::now()
        ]);

        // Save seller
        $seller->save();

        // Return our seller
        return $seller;
    }

    /**
     * Generic update seller method.
     *
     * For crawls, you should use the discover method. For status updates only,
     * you should use the specific updateStatus method.
     *
     * @param  Models\Seller $seller
     * @param  array         $attributes
     * @return Models\Seller
     */
    public function update(Models\Seller $seller, $attributes)
    {
        // Store old attributes
        $oldAttributes = $seller->getAttributes();

        // Fill seller with new attributes
        $this->fill($seller, $attributes);

        // Save changes
        $seller->save();

        // Seller is moving from "consumer" to something else, ensure that
        // discovery statuses are cleared
        // @todo This should probably be attached to a "SellerWasUpdated" event
        if (
            isset($attributes['status'])
            && 'consumer' == $oldAttributes['status']
            && 'consumer' !== $attributes['status']
        ) {
            app(Repositories\Eloquent\DiscoveryRepository::class)->clearConsumerStatuses($seller);
        }

        // Return seller model
        return $seller;
    }

    /**
     * Update seller status only.
     *
     * This method allows the user to provide a specific comment for the status
     * update whereas the generic update method does not allow this.
     *
     * @param  Models\Seller $seller
     * @param  string        $status
     * @param  string        $comment
     * @return Models\Seller
     */
    public function updateStatus(Models\Seller $seller, $status, $comment = null)
    {
        // Set status attribute (return if no update needed)
        if (! $seller->setStatus($status, $comment)) return $seller;

        // Save changes
        $seller->save();

        // Return seller
        return $seller;
    }

    /**
     * Fill the given seller with the provided attributes.
     *
     * @param  Models\Seller $seller
     * @param  array         $attributes
     * @return Models\Seller
     */
    protected function fill(Models\Seller $seller, array $attributes)
    {
        // Fill generic attributes
        $seller->fill($attributes);

        // Attach account
        if (
            isset($attributes['account_id'])
            && ($account = app('App\Contracts\AccountRepositoryInterface')->find($attributes['account_id']))
        ) {
            $seller->account()->associate($account);
        }

        return $seller;
    }

    /**
     * Mark seller as needing a refresh and queue the job.
     *
     * @param  Models\Seller $seller
     * @return Models\Seller
     */
    public function queueRefresh(Models\Seller $seller)
    {
        // Already queued
        if ($seller->needs_refresh) return;

        // Update flag
        $seller = $this->update($seller, ['needs_refresh' => true]);

        // Queue the job
        dispatch(new Jobs\RefreshSeller($seller));

        // Return the seller
        return $seller;
    }

    /**
     * Refresh the provided seller status.
     *
     * @param  Models\Seller $seller
     * @return Models\Seller
     */
    public function refresh(Models\Seller $seller)
    {
        // Grab a matrix of statuses for this seller
        $counts = app(Repositories\Eloquent\DiscoveryRepository::class)
            ->setScope(['seller' => $seller->id])
            ->countByStatus(false);

        // Generate a refresh status
        $status = $this->generateRefreshStatus($seller, $counts);

        // Save new status
        if ($status !== $seller->status) {
            $seller = $this->updateStatus(
                $seller, $status,
                'Generated in refresh on '.Carbon::now()->toIso8601String()
            );
        }

        // Clear flag
        $seller = $this->update($seller, ['needs_refresh' => false]);

        // Return the seller
        return $seller;
    }

    /**
     * Return the status that should be applied for a seller with the given
     * status matrix (key: status, value: count).
     *
     * @param  Models\Seller $seller
     * @param  array         $matrix
     * @return string
     */
    protected function generateRefreshStatus(Models\Seller $seller, $matrix)
    {
        // Grab status keys
        $keys = array_keys($matrix);

        // Has at least one flagged
        if (in_array('flagged', $keys))
            return 'flagged';

        // Has at least one price flagged
        if (in_array('price', $keys))
            return 'price';

        // Seller has some closed and some discovered listings (is an infringer)
        if (in_array('closed', $keys) && in_array('discovered', $keys))
            return 'infringer';

        // Seller is not an infringer but has a discovered listing
        if ('infringer' !== (string) $seller->status && in_array('discovered', $keys))
            return 'new';

        // All discoveries are one status
        if (1 == count($keys))
            return $keys[0];

        // Return mixed if all else fails
        return 'mixed';
    }

    public function all()
    {
        $query = Seller::query();

        if ($account = $this->getAccount()) {
            $query = $query->where('account_id', $account->id);
        }

        return $query
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Build a base filtered query for fetching sellers.
     *
     * @param  array $sort
     * @param  array $filters
     * @return Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery($sort = null, $filters = [])
    {
        // Get a base query
        // Here, we set the status table as the base query as we shouldn't be
        // querying anything without a status.
        $query = Models\Seller::query()
            ->select('sellers.*');

        // Apply account constraint
        if ($account = $this->getAccount()) {
            $query = $query->where('sellers.account_id', $account->id);
        }

        // Apply ID filter
        if (array_key_exists('id', $filters)) {
            $query = $query
                ->whereIn('sellers.id', explode(',', $filters['id']));
        }

        // Apply asset filter
        if (array_key_exists('asset', $filters)) {
            $query = $query
                ->whereHas('discoveries', function ($query) use ($filters) {
                    $query->whereIn('discoveries.asset_id', str_getcsv($filters['asset']));
                });
        }

        // Apply platform filter
        if (array_key_exists('platform', $filters)) {
            $query = $query
                ->whereIn('sellers.platform', explode(',', $filters['platform']));
        }

        // Apply category filter
        if (array_key_exists('category', $filters))
        {
            $query = $query
                ->whereHas('discoveries', function ($query) use ($filters)
                {
                    // Allow comma delimited searches
                    $categories = str_getcsv($filters['category']);

                    // Loop categories and add WHERE clauses
                    $query->where(function($query) use ($categories) {
                        foreach ($categories as $key => $category) {
                            $category = trim($category);

                            if (0 == $key) {
                                $query = $query->where('discoveries.category', 'LIKE', '%'.$category.'%');
                            } else {
                                $query = $query->orWhere('discoveries.category', 'LIKE', '%'.$category.'%');
                            }
                        }
                    });
                });
        }

        // Apply status filter
        if (array_key_exists('status', $filters)) {
            $query = $query
                ->whereHas('discoveries', function ($query) use ($filters) {
                    $query->whereIn('discoveries.cached_status', str_getcsv($filters['status']));
                });
        }

        // Apply seller filter
        if (array_key_exists('seller', $filters)) {
            $query = $query
                ->whereIn('sellers.id', explode(',', $filters['seller']));
        }

        // Apply origin filter
        if (array_key_exists('origin', $filters))
        {
            $query = $query
                ->whereHas('discoveries', function ($query) use ($filters)
                {
                    // Allow comma delimited searches
                    $origins = str_getcsv($filters['origin']);

                    // Loop origins and add WHERE clauses
                    $query->where(function($query) use ($origins) {
                        foreach ($origins as $key => $origin) {
                            $origin = trim($origin);

                            if (0 == $key) {
                                $query = $query->where('discoveries.origin', 'LIKE', '%'.$origin.'%');
                            } else {
                                $query = $query->orWhere('discoveries.origin', 'LIKE', '%'.$origin.'%');
                            }
                        }
                    });
                });
        }

        // Apply price from filter
        if (array_key_exists('price_from', $filters)) {
            $query = $query
                ->whereHas('discoveries', function ($query) use ($filters) {
                    $query->where('discoveries.price', '<=', $filters['price_from']);
                });
        }

        // Apply price to filter
        if (array_key_exists('price_to', $filters)) {
            $query = $query
                ->whereHas('discoveries', function ($query) use ($filters) {
                    $query->where('discoveries.price', '>=', $filters['price_to']);
                });
        }

        // Apply a sort if necessary
        if ($sort) {
            foreach ($sort as $s) {
                list($col, $dir) = $s;
                $query = $query->orderBy($col, $dir);
            }
        }

        // Return query object
        return $query;
    }

    /**
     * Return a paginated collection of sellers.
     *
     * @param  integer $page
     * @param  integer $limit
     * @param  array   $sort
     * @param  array   $filters
     * @return Illuminate\Database\Collection
     */
    public function paginate($page = 1, $limit = 20, $sort = null, $filters = [])
    {
        return $this->buildQuery($sort, $filters)->paginate($limit, ['*'], 'page', $page);
    }

    public function get($sort = null, $filters = [])
    {
        return $this->buildQuery($sort, $filters)->get();
    }

    public function massUpdateFlags($flag, $filters = [])
    {
        $sellers = $this->get(null, $filters);

        foreach ($sellers as $seller) {
            $this->update($seller, ['flag' => $flag]);
        }

        return $sellers;
    }

    public function countDiscoveries($seller)
    {
        if (! $seller instanceof Seller) {
            $seller = $this->find($seller);
        }

        return $seller->discoveries()->count();
    }

    public function countByStatus()
    {
        $seller = $this->getTimeMachineModel();

        $statuses = [];
        foreach (app(SellerStatusHelper::class)->getKeys() as $s) {
            $statuses[$s] = 0;
        }

        $query = $seller
            ->select('status', DB::raw('COUNT(id) as count'))
            ->groupBy('status');

        if ($account = $this->getAccount()) {
            $query = $query->where('account_id', $account->id);
        }

        if ($asset = $this->getScope()->asset) {
            $query = $query->whereHas('discoveries', function ($query) use ($asset) {
                $query->where('asset_id', $asset);
            });
        }

        $query = $this->appendActiveBetweenQuery($query, 'status');

        $result = $query->get();

        foreach ($result as $row) {
            $statuses[$row->status] = $row->count;
        }

        return $statuses;
    }

    public function countByPlatform()
    {
        $seller = $this->getTimeMachineModel();

        $platforms = [];

        $query = $seller
            ->select('platform', DB::raw('COUNT(id) as count'))
            ->groupBy('platform');

        if ($account = $this->getAccount()) {
            $query = $query->where('account_id', $account->id);
        }

        if ($asset = $this->getScope()->asset) {
            $query = $query->whereHas('discoveries', function ($query) use ($asset) {
                $query->where('asset_id', $asset);
            });
        }

        if ($status = $this->getScope()->status) {
            $query = $query->where('status', $status);
        }

        $query = $this->appendActiveBetweenQuery($query, 'status');

        $result = $query->get();

        foreach ($result as $row) {
            $platforms[$row->platform] = $row->count;
        }

        return $platforms;
    }

    public function countForStatus($status, $set = null)
    {
        $query = $this->getTimeMachineModel();

        if ($account = $this->getAccount()) {
            $query = $query->where('account_id', $account->id);
        }

        if ($set) {
            $query = $query->whereIn('id', $set);
        }

        return $query
            ->where('status', $status)
            ->count();
    }

    public function searchSellers($search, $limit = 100)
    {
        $query = $this->buildQuery();

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query->select(['sellers.id', 'name', 'platform'])
            ->where('name', 'LIKE', '%'.$search.'%')
            ->orderBy('name', 'ASC')
            ->get();
    }

    protected function appendActiveBetweenQuery(Builder $query, $key = null)
    {
        //Fetch start and stop from scope
        $start = $this->getScope()->activeAfter;
        $stop  = $this->getScope()->activeBefore;

        //Neither provided, return query
        if (! $start && ! $stop) return $query;

        //Add join for activity
        $query = $query
            ->whereHas('revisionHistory', function ($query) use ($start, $stop, $key) {
                if ($start) $query->where('created_at', '>=', $start);
                if ($stop)  $query->where('created_at', '<=', $stop);
                if ($key)   $query->whereIn('key', [$key, 'created_at']);
            });

        //Return query
        return $query;
    }
}
