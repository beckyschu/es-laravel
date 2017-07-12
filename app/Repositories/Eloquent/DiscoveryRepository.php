<?php

namespace App\Repositories\Eloquent;

use DB;
use App\Jobs;
use Exception;
use App\Models;
use App\Helpers;
use App\Contracts;
use Carbon\Carbon;
use App\Repositories;
use Ramsey\Uuid\Uuid;
use App\Models\Crawl;
use App\Models\Asset;
use App\Models\Import;
use App\Models\Seller;
use App\Models\Discovery;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\Eloquent\Traits\Scoped;
use App\Contracts\SellerRepositoryInterface;
use App\Contracts\DiscoveryRepositoryInterface;
use App\Repositories\Eloquent\Traits\Accountable;
use App\Repositories\Eloquent\Traits\TimeMachine;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DiscoveryRepository implements DiscoveryRepositoryInterface
{
    use Accountable, Scoped;

    /**
     * Find discovery with the given ID.
     *
     * @param  string $id
     * @return Models\Discovery
     */
    public function find($id)
    {
        return Discovery::findOrFail($id);
    }

    /**
     * Discover the given attributes.
     *
     * This method will either create or update a discovery depending on if one
     * already exists with a matching URL.
     *
     * @param  array                   $attributes
     * @param  Contracts\Models\Source $source
     * @return Models\Discovery
     */
    public function discover(array $attributes, Contracts\Models\Source $source)
    {
        //Set Import Attributes that doesn't appear in Crawls
        $attributes['status'] = $attributes['status'] ?? null;
        $attributes['comment'] = $attributes['comment'] ?? null;
        $attributes['created_at'] = $attributes['last_seen_at'] ?? null;
        $attributes['last_seen_at'] = $attributes['last_seen_at'] ?? null;
        //$attributes['platform'] = $attributes['platform'] ?? null;

        // Output debug
        //Log::info('[Repositories\DiscoveryRepository] Discovering '.json_encode($attributes));

        // No URL provided, bail out
        if (! $attributes['url']) {
            Log::error('[Repositories\DiscoveryRepository] No URL attribute provided with discovery '.json_encode($attributes));
            throw new BadRequestHttpException('You must provide a URL attribute to create a discovery.');
        }

        // Asset not contained in source, attempt to find by keyword
        if (! ($asset = $source->getAsset()) && isset($attributes['keyword'])) {
            $asset = app(Repositories\Eloquent\AssetRepository::class)->findByKeyword($attributes['keyword']);
        }

        // No asset found, bail out
        // @todo Throw exception, remember to catch in imports
        if (! $asset) {
            Log::error('[Repositories\DiscoveryRepository] No asset attached to source '.json_encode($source));
            return false;
        }

        // Discover the seller first
        $seller = null;
        if (! empty($attributes['seller'])) {
            $seller = app(Repositories\Eloquent\SellerRepository::class)->discover(
                $attributes['seller'], $source, $asset
            );
        }

        // Check if platform is imported, if it is, then bypass source platform
        if(isset($attributes['platform'])) {
          $inputPlatform = $attributes['platform'];
        }
        else {
          $inputPlatform = $source->getPlatform();
        }

        // Search for discovery with this URL and account
        $discovery = Models\Discovery::query()
            ->where('asset_id', $asset->id)
            ->where('account_id', $asset->account->id)
            ->where('seller_id', $seller ? $seller->id : null)
            ->where('platform', $inputPlatform)
            ->where('url', $attributes['url'])
            ->first();

        // Discovery does not exist yet, create it
        if (! $discovery) {

            // Create a new discovery
            $discovery = new Models\Discovery;

            // Associate relationships
            $discovery->account()->associate($asset->account);
            $discovery->asset()->associate($asset);

            // Associate seller
            if ($seller) {
                $discovery->seller()->associate($seller);
            }

            // Assign a UID
            $discovery->id = Uuid::uuid4();

            // Update created_at
            if(strlen($attributes['created_at']) > 0) { // If Import created_at is filled
              $discovery->created_at = $attributes['created_at'];
            }

            // Set platform
            $discovery->platform = $inputPlatform;

            if(strlen($attributes['status']) > 0) // If Import Status is filled
            {
              // Add inital comment to attributes
              $attributes['comment'] = $source->getDiscoveryComment();
              // Add initial comment
              $discovery->addComment($source->getDiscoveryComment());
            }
            else
            {
              // Add initial comment
              $discovery->addComment($source->getDiscoveryComment());
            }

        }
        else //if an update
        {
          $attributes['comment'] = 'Update Import Status : ' . $attributes['status'];
        }

        // Save original price for later processing
        $originalPrice = $discovery->price;

        // Fill attributes
        $discovery = $this->fill($discovery, $attributes);

        // Update last seen
        if(strlen($attributes['last_seen_at']) > 0) { // If Import last_seen_at is filled
          $discovery->last_seen_at = $attributes['last_seen_at'];
        }
        else {
          $discovery->last_seen_at = Carbon::now();
        }

        // Save discovery
        $discovery->save();

        // Increment source submission count
        $source->incrementSubmissionCount();

        if(strlen($discovery->status) > 0) // Add check for existing imported status, if not null, then do not need to process.
        {
          // Finish processing
          $this->finishProcessing($discovery, $source);
          return $discovery;
        }
        else
        {
          // Dispatch process job
          dispatch(new Jobs\ProcessDiscovery($discovery, $originalPrice, $source));
        }


        // Return our discovery
        return $discovery;
    }

    /**
     * Generic update discovery method.
     *
     * For crawls, you should use the discover method. For status updates only,
     * you should use the specific updateStatus method.
     *
     * @param  Models\Discovery $discovery
     * @param  array            $attributes
     * @param  string           $comment
     * @return Models\Discovery
     */
    public function update(Models\Discovery $discovery, $attributes, $comment = null)
    {
        // Fill attributes
        $discovery = $this->fill($discovery, $attributes);

        // Add optional comment
        if ($comment) {
            $discovery->addComment($comment);
        }

        // Save changes
        $discovery->save();

        // Return discovery
        return $discovery;
    }

    /**
     * Update discovery status only.
     *
     * This method allows the user to provide a specific comment for the status
     * update whereas the generic update method does not allow this.
     *
     * @param  Models\Discovery $discovery
     * @param  string           $status
     * @param  string           $comment
     * @return Models\Discovery
     */
    public function updateStatus(Models\Discovery $discovery, $status, $comment = null)
    {
        // Set status attribute (return if no update needed)
        if (! $discovery->setStatus($status, $comment)) return $discovery;

        // Save changes
        $discovery->save();

        // Mark seller as requiring a refresh
        if ($discovery->seller) {
            app(Repositories\Eloquent\SellerRepository::class)->queueRefresh($discovery->seller);
        }

        // Return discovery
        return $discovery;
    }

    /**
     * Mass update status based on provided filters.
     *
     * @param  string $status
     * @param  array  $filters
     * @param  string $comment [description]
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function massUpdateStatus($status, $filters = [], $comment = null)
    {
        $query = $this->buildQuery(null, $filters);

	    $discoveries = $query->get();

    	foreach ($discoveries as $discovery){
    		$this->updateStatus($discovery, $status, $comment);
    	}

	    return $discoveries;
    }

    /**
     * Process the given discovery against the rules table and update with the
     * resulting status if matched.
     *
     * @param  Models\Discovery $discovery
     * @param  string           $originalPrice Used by some rules
     * @return Models\Discovery
     */
    public function process(
        Models\Discovery $discovery,
        $originalPrice = null,
        Contracts\Models\Source $source = null
    ) {
        // Fetch all rules that are applicable to this disco
        $rules = Models\DiscoveryRule::query()
            ->where('is_active', true)
            ->orderBy('priority', 'ASC')
            ->get();

        // No applicable rules to process
        if ($rules->isEmpty()) {
            Log::info('[Repositories\DiscoveryRepository] [Discovery '.$discovery->id.'] No rules available for processing');
            return $this->finishProcessing($discovery, $source);
        }

        // Complete data for rule environment
        $ruleData = $discovery->rule_data;

        // Add original price to rule data
        $ruleData['discovery']['original_price'] = $originalPrice;

        // Find the first matching rule
        $matchedRule = $rules->first(function ($rule) use ($ruleData) {
            return \JWadhams\JsonLogic::apply($rule->rule, $ruleData);
        });

        // No matching rule to process
        if (! $matchedRule) {
            Log::info('[Repositories\DiscoveryRepository] [Discovery '.$discovery->id.'] No matching rule found');
            return $this->finishProcessing($discovery, $source);
        }

        // Log some info
        Log::info('[Repositories\DiscoveryRepository] [Discovery '.$discovery->id.'] Matched rule '.$matchedRule->id.' for status '.$matchedRule->status);

        // Update discovery status
        $discovery = $this->updateStatus(
            $discovery,
            $matchedRule->status,
            'Set via rule '.$matchedRule->id.' with content '.json_encode($matchedRule->rule).' and data '.json_encode($ruleData).'.'
        );

        // Finish processing
        $this->finishProcessing($discovery, $source);

        // Return updated discovery
        return $discovery;
    }

    /**
     * Finish processing discovery by updating source count if applicable.
     *
     * @param  Models\Discovery        $discovery
     * @param  Contracts\Models\Source $source
     * @return void
     */
    protected function finishProcessing(Models\Discovery $discovery, Contracts\Models\Source $source)
    {
        if (! $source) return;

        if ('rejected' == $discovery->status) $source->incrementRejectedCount();
        else                                  $source->incrementAcceptedCount();

        return $discovery;
    }

    /**
     * Fill the given discovery with the provided attributes.
     *
     * @param  Models\Discovery $discovery
     * @param  array            $attributes
     * @return Models\Discovery
     */
    public function fill(Models\Discovery $discovery, $attributes)
    {
        // Fill generic attributes
        $discovery->fill($attributes);

        // Set status
        if (isset($attributes['status'])) {
            $discovery->setStatus($attributes['status'], $attributes['comment']);
        }

        // Attach account
        if (
            isset($attributes['account_id'])
            && ($account = app('App\Contracts\AccountRepositoryInterface')->find($attributes['account_id']))
        ) {
            $discovery->account()->associate($account);
        }

        // Attach asset
        if (
            isset($attributes['asset_id'])
            && ($asset = app('App\Contracts\AssetRepositoryInterface')->find($attributes['asset_id']))
        ) {
            $discovery->asset()->associate($asset);
        }

        // Attach seller
        if (
            isset($attributes['seller_id'])
            && ($seller = app('App\Contracts\SellerRepositoryInterface')->find($attributes['seller_id']))
        ) {
            $discovery->seller()->associate($seller);
        }

        return $discovery;
    }

    /**
     * Build a base filtered query for fetching discoveries.
     *
     * @param  array $sort
     * @param  array $filters
     * @param  bool  $groupBy
     * @return Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery($sort = null, $filters = [], $groupBy = true)
    {
        // Build base query
        $query = Discovery::query()->select('discoveries.*');

        // Apply account constraint
        if ($account = $this->getAccount()) {
            $query = $query->where('discoveries.account_id', $account->id);
        }

        // Apply asset constraint
        if ($asset = $this->getScope()->asset) {
            $query = $query->where('discoveries.asset_id', $asset);
        }

        // Apply seller constraint
        if ($seller = $this->getScope()->seller) {
            $query = $query->where('discoveries.seller_id', $seller);
        }

        // Apply ID filter
        if (array_key_exists('id', $filters)) {
            $query = $query
                ->whereIn('discoveries.id', explode(',', $filters['id']));
        }

        // Apply asset filter
        if (array_key_exists('asset', $filters)) {
            $query = $query
                ->whereIn('discoveries.asset_id', explode(',', $filters['asset']));
        }

        // Apply platform filter
        if (array_key_exists('platform', $filters)) {
            $query = $query
                ->whereIn('discoveries.platform', explode(',', $filters['platform']));
        }

        // Apply category like filter
        if (array_key_exists('category', $filters))
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
        }

        // Apply status filter
        if (array_key_exists('status', $filters)) {
            $query = $query
                ->whereIn('discoveries.cached_status', explode(',', $filters['status']));
        }

        // "Reject" status has not been supplied and we are not selecting by ID
        // so implicitly ignore rejected discoveries
        if (
            ! array_key_exists('id', $filters)
            && (
                ! array_key_exists('status', $filters) ||
                ! in_array('rejected', explode(',', $filters['status'])) &&
                ! in_array('closed', explode(',', $filters['status']))
            )
        ) {
            $query = $query->whereNotIn('discoveries.cached_status', ['rejected', 'closed']);
        }

        // Apply seller filter
        if (array_key_exists('seller', $filters)) {
            $query = $query
                ->whereIn('discoveries.seller_id', str_getcsv($filters['seller']));
        }

        // Apply origin filter
        if (array_key_exists('origin', $filters))
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
        }

        // Apply price from filter
        if (array_key_exists('price_from', $filters)) {
            $query = $query
                ->where('discoveries.price', '>=', $filters['price_from']);
        }

        // Apply price to filter
        if (array_key_exists('price_to', $filters)) {
            $query = $query
                ->where('discoveries.price', '<=', $filters['price_to']);
        }

        // Apply search filter
        if (array_key_exists('query', $filters))
        {
            // Allow comma delimited searches
            $keywords = str_getcsv($filters['query']);

            // Loop keywords and add WHERE clauses
            $query->where(function($query) use ($keywords) {
                foreach ($keywords as $key => $keyword) {
                    $keyword = trim($keyword);

                    if (0 == $key) {
                        $query = $query->where('discoveries.title', 'LIKE', '%'.$keyword.'%');
                    } else {
                        $query = $query->orWhere('discoveries.title', 'LIKE', '%'.$keyword.'%');
                    }
                }
            });
        }

        // Apply a sort if necessary
        if ($sort) {
            foreach ($sort as $s) {
                list($col, $dir) = $s;

                if ('sellers.name' == $col) {
                    $query = $query
                        ->join('sellers', 'discoveries.seller_id', '=', 'sellers.id')
                        ->select('discoveries.*', 'sellers.name')
                        ->groupBy('discoveries.id');
                }

                // Append table name to order column to prevent ambiguity
                if ('status' == $col) {
                    $col = 'discoveries.cached_status';
                } elseif (! str_contains($col, '.')) {
                    $col = 'discoveries.'.$col;
                }

                $query = $query->orderBy($col, $dir);
            }
        }

        // Return query object
        return $query;
    }

    /**
     * Return a paginated collection of discoveries.
     *
     * @param  integer $page
     * @param  integer $limit
     * @param  array   $sort
     * @param  array   $filters
     * @param  array   $includes
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function paginate($page = 1, $limit = 20, $sort = null, $filters = [], $includes = [])
    {
        return $this
            ->buildQuery($sort, $filters)
            ->with($includes)
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Get all discoveries matching the given filters.
     *
     * @param  array $sort
     * @param  array $filters
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function get($sort = null, $filters = [])
    {
        return $this->buildQuery($sort, $filters)->get();
    }

    /**
     * Return an array of statuses and discovery counts.
     *
     * @param  boolean $includeZero
     * @return Illuminate\Eloquent\Collection
     */
    public function countByStatus($includeZero = true)
    {
        $query = $this->buildQuery()
            ->select('discoveries.cached_status as status', DB::raw('COUNT(discoveries.id) as count'))
            ->groupBy('discoveries.cached_status');

        $statuses = [];

        if ($includeZero) {
            foreach (app(Helpers\DiscoveryStatusHelper::class)->getKeys() as $s) {
                $statuses[$s] = 0;
            }
        }

        $result = $query->pluck('count', 'status');

        foreach ($result as $status => $count) {
            $statuses[$status] = $count;
        }

        return $statuses;
    }

    public function searchCategories($search = null, $limit = 100)
    {
        $query = $this->buildQuery(null, [], false);

        if ($search) {
            $query = $query->where('category', 'LIKE', '%'.$search.'%');
        }

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query->select('discoveries.category', \DB::raw('COUNT(discoveries.id) as count'))
            ->whereNotNull('discoveries.category')
            ->orderBy('count', 'DESC')
            ->groupBy('discoveries.category')
            ->get();
    }

    public function searchOrigins($search = null, $limit = 100)
    {
        $query = $this->buildQuery();

        if ($search) {
            $query = $query->where('origin', 'LIKE', '%'.$search.'%');
        }

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query->select('origin')->distinct()
            ->whereNotNull('origin')
            ->orderBy('origin', 'ASC')
            ->pluck('origin');
    }

    /**
     * Count discoveries for the given status.
     *
     * @param  string $status
     * @return int
     */
    public function countForStatus($status)
    {
        return $this
            ->buildQuery(null, [], false)
            ->where('discoveries.cached_status', $status)
            ->count();
    }

    /**
     * Return all pending discoveries in a global scope.
     *
     * This is used by enforcers to fetch pending discoveries for the platform
     * being scanned.
     *
     * @param  string $platform
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getPendingForPlatform($platform)
    {
        return Discovery::query()
            ->select('discoveries.*')
            ->where('discoveries.cached_status', 'pending')
            ->get();
    }

    public function clearConsumerStatuses(Seller $seller)
    {
        // Seller is still a consumer, we don't need to clear anything
        if ('consumer' == $seller->status) return;

        // Fetch any "consumer" discoveries attached to this seller
        $discoveries = $seller
            ->discoveries()
            ->where('status', 'consumer')
            ->get();

        // Loop through result and update status to "discovered"
        foreach ($discoveries as $discovery) {
            $this->update($discovery, ['status' => 'discovered']);
        }
    }

}
