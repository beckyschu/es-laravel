<?php

namespace App\Http\Controllers\Api;

use Auth;
use Response;
use Transaction;
use Carbon\Carbon;
use App\Repositories;
use League\Csv\Writer;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Collection;
use App\Transformers\DiscoveryTransformer;
use App\Contracts\CrawlRepositoryInterface;
use League\Fractal\Serializer\ArraySerializer;
use App\Contracts\DiscoveryRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DiscoveriesController extends Controller
{
    protected $request;

    protected $fractal;

    protected $filtering;

    protected $discoveries;

    public function __construct(
        Request $request,
        Manager $fractal,
        Repositories\Eloquent\FilteringRepository $filtering,
        DiscoveryRepositoryInterface $discoveries
    ) {
        $this->request     = $request;
        $this->fractal     = $fractal;
        $this->filtering   = $filtering;
        $this->discoveries = $discoveries;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //An account must be selected unless admin
        if (! Auth::getAccount() && ! Auth::can('access_global_account')) {
            throw new AccessDeniedHttpException('An account must be selected to fetch discoveries.');
        }

        //Grab requested page
        $page = (int) $this->request->get('page') ?: 1;

        //Grab sort parameters if provided
        if ($sort = $this->request->get('sort')) {
            $sort = array_map(function ($col) {
                $dir = 'ASC';

                if (starts_with($col, '-')) {
                    $col = substr($col, 1);
                    $dir = 'DESC';
                }

                return [$col, $dir];
            }, explode(',', $sort));
        }

        //Enable includes
        if (Auth::getAccount()) {
            $this->fractal->parseIncludes('asset,seller');
        } else {
            $this->fractal->parseIncludes('asset,seller,account');
        }

        //Fetch filters from request
        $filters = $this->request->get('filter', []);

        //Grab discoveries paginator
        $discoveries = $this->discoveries
            ->setAccount(Auth::getAccount())
            ->paginate($page, 100, $sort, $filters, ['asset', 'seller']);

    	$response = [
    		'data' => [],
    		'included' => [],
    		'meta' => [],
    		'links' => []
    	];

        //Build our discoveries collection
        $collection = new Collection($discoveries->items(), new DiscoveryTransformer, 'discoveries');

        //Build response
        $response = $this->fractal->createData($collection)->toArray();

        //Add meta data
        $response['meta'] = [
            'total'        => $discoveries->total(),
            'page'         => $page,
            'enforceCount' => $this->discoveries->countForStatus('enforce')
        ];

        //Grab current URL
        $url = urldecode($this->request->fullUrl());

        //Generate next page link
        $nextPageLink = null;
        if($discoveries->hasMorePages()){
            if (str_contains($url, 'page=')) {
                $nextPageLink = str_replace('page='.$page, 'page='.($page + 1), $url);
            } elseif (str_contains($url, '?')) {
                $nextPageLink = $url.'&page='.($page + 1);
            } else {
                $nextPageLink = $url.'?page='.($page + 1);
            }
        }

        //Add links
        $response['links'] = [
            'prev' => (1 < $page) ? str_replace('page='.$page, 'page='.($page - 1), $url) : null,
            'next' => $nextPageLink,
        ];

        //Return success response
        return Response::json($response);
    }

    public function export()
    {
        //Grab sort parameters if provided
        if ($sort = $this->request->get('sort')) {
            $sort = array_map(function ($col) {
                $dir = 'ASC';

                if (starts_with($col, '-')) {
                    $col = substr($col, 1);
                    $dir = 'DESC';
                }

                return [$col, $dir];
            }, explode(',', $sort));
        }

        //Fetch filters from request
        $filters = $this->request->get('filter', []);

        //Grab discoveries collection
        $discoveries = $this->discoveries
            ->setAccount(Auth::getAccount())
            ->get($sort, $filters);

        //Generate filename for CSV
        $filename = 'discoveries_'.Carbon::now()->format('YmdHis').'_'.strtolower(str_random(10)).'.csv';
        $filepath = storage_path('app/public/exports/'.$filename);

        //Create CSV file
        touch($filepath);

        //Open stream for CSV
        $file = new \SplFileObject($filepath);

        //Instantiate CSV writer
        $csv = Writer::createFromPath($file);

        //Insert headers
        $csv->insertOne(['pagination', 'pagination-href', 'name', 'name-href', 'price', 'price2', 'seller', 'seller2', 'location', 'itemnumber', 'itemnumber2', 'image-src', 'qtyavailable', 'qtysold', 'id', 'account', 'asset', 'platform', 'keyword', 'status', 'last_seen_at', 'date_created','date_updated']);

        //Loop rows and insert data
        foreach ($discoveries as $discovery) {
            $csv->insertOne([
                null,
                null,
                $discovery->title,
                $discovery->url,
                $discovery->price,
                null,
                $discovery->seller ? $discovery->seller->name : null,
                null,
                $discovery->origin,
                $discovery->sku,
                null,
                $discovery->picture,
                $discovery->qty_available,
                $discovery->qty_sold,
                $discovery->id,
                $discovery->account->name,
                $discovery->asset->name,
                $discovery->platform,
                $discovery->keyword,
                $discovery->status,
                (new Carbon($discovery->last_seen_at))->toIso8601String(),
                (new Carbon($discovery->created_at))->toIso8601String(),
                (new Carbon($discovery->updated_at))->toIso8601String()
            ]);
        }

        //Output our file location
        return Response::json([
            'data' => asset('storage/exports/'.$filename)
        ]);
    }

    public function show($id)
    {
        // Lookup discovery with given ID
        $discovery = $this->discoveries->find($id);

        // Enable includes
        $this->fractal->parseIncludes('asset,seller,statuses');

        // Build fractal item
        $item = new Item($discovery, new DiscoveryTransformer, 'discoveries');

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        // Return success response
        return Response::json($response);
    }

    public function update($id)
    {
        // No patch data found
        if (! $this->request->has('data.attributes')) {
            throw new BadRequestHttpException('You must provide attributes to perform an update.');
        }

        // Open a transaction
        Transaction::open('update', 'discovery.update');

        // Enable includes
        $this->fractal->parseIncludes('asset,seller');

        // Fetch discovery
        $discovery = $this->discoveries->find($id);

        // Run the updates
        $status    = $this->request->input('data.attributes.status');
        $discovery = $this->discoveries->updateStatus($discovery, $status, 'Manual update');

        // Enable includes
        $this->fractal->parseIncludes('asset,seller');

        // Build fractal item
        $item = new Item($discovery, new DiscoveryTransformer, 'discoveries');

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        //Return success response
        return Response::json($response);

        //Return the show response
        return $this->show($id);
    }

    public function massUpdateStatus()
    {
        // No status has been provided
        if (! $status = $this->request->input('status')) {
            throw new BadRequestHttpException('You must provide a status to perform a mass status update.');
        }

        // Open a transaction
        Transaction::open('update', 'discovery.massUpdate');

        // Fetch filters from request
        $filters = $this->request->get('filter', []);

        // Fetch comment from request
        $comment = $this->request->input('comment', 'Mass manual update');

        // Run update process
        $discoveries = $this->discoveries
            ->setAccount(Auth::getAccount())
            ->massUpdateStatus($status, $filters, $comment);

        // Build data collection
        $data = [];
        foreach ($discoveries as $discovery) {
            array_push($data, [
                'type' => 'discoveries',
                'id'   => (string) $discovery['id']
            ]);
        }

        //Build response
        $response = [
            'data' => $data,
            'meta' => [
                'enforceCount' => $this->discoveries->countForStatus('enforce')
            ]
        ];

        //Return success response
        return Response::json($response);
    }

    public function searchFilterOptions($slug, Request $request)
    {
        $query = $request->input('query', null);

        // Limit to current account unless global specified
        if (! $request->input('global') || ! Auth::can('access_global_account')) {
            $this->filtering->setAccount(Auth::getAccount());
        }

        return Response::json($this->filtering->searchFilterOptions($slug, $query));
    }

    public function discover(
        Request $request,
        DiscoveryRepositoryInterface $discoveryRepo,
        CrawlRepositoryInterface $crawlRepo
    ) {
        // No attributes provided
        if (! $attributes = $this->request->input()) {
            throw new BadRequestHttpException('You must provide attributes to create a discovery.');
        }

        // No crawl ID provided
        if (
            (! $crawlId = $this->request->header('X-Crawl'))
            || (! $crawl = $crawlRepo->find($crawlId))
        ) {
            throw new BadRequestHttpException('You must provide a valid crawl ID when creating submissions.');
        }

        // Create the discovery
        $discovery = $discoveryRepo->discover($attributes, $crawl);

        // Simplify serializer
        $this->fractal->setSerializer(new ArraySerializer);

        // Build fractal item
        $item = new Item($discovery, new DiscoveryTransformer, 'discoveries');

        // Build response
        $response = $this->fractal->createData($item)->toArray();
    }
}
