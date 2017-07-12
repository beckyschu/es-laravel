<?php

namespace App\Http\Controllers\Api;

use Auth;
use Response;
use Transaction;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Collection;
use App\Transformers\SellerTransformer;
use App\Contracts\SellerRepositoryInterface;
use App\Contracts\DiscoveryRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SellersController extends Controller
{
    protected $request;

    protected $fractal;

    protected $sellers;

    protected $discoveries;

    public function __construct(
        Request $request,
        Manager $fractal,
        SellerRepositoryInterface $sellers,
        DiscoveryRepositoryInterface $discoveries
    ) {
        $this->request     = $request;
        $this->fractal     = $fractal;
        $this->sellers     = $sellers;
        $this->discoveries = $discoveries;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //An account must be selected unless admin
        if (! Auth::getAccount() && ! Auth::can('access_global_account')) {
            throw new AccessDeniedHttpException('An account must be selected to fetch sellers.');
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
        if (! Auth::getAccount()) {
            $this->fractal->parseIncludes('account');
        }

        //Fetch filters from request
        $filters = $this->request->get('filter', []);

        //Grab sellers paginator
        $sellers = $this->sellers
            ->setAccount(Auth::getAccount())
            ->paginate($page, 20, $sort, $filters);

        //Build our collection
        $collection = new Collection($sellers->items(), new SellerTransformer, 'sellers');

        //Build response
        $response = $this->fractal->createData($collection)->toArray();

        //Loop through data and add discovery count links
        foreach ($sellers as $key => $seller)
        {
            $count = $this->sellers
                ->setAccount(Auth::getAccount())
                ->countDiscoveries($seller);

            $response['data'][$key]['relationships']['discoveries'] = [
                'links' => [
                    'related' => url('api/sellers/'.$seller->id.'/discoveries')
                ],
                'meta' => [
                    'count' => $count
                ]
            ];
        }

        //Add meta data
        $response['meta'] = [
            'total' => $sellers->total(),
            'page'  => $page
        ];

        //Grab current URL
        $url = urldecode($this->request->fullUrl());

        //Generate next page link
        $nextPageLink = null;
        if ($sellers->hasMorePages()) {
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

    public function show($id)
    {
        // Find seller with ID
        $seller = $this->sellers->find($id);

        // Enable includes
        $this->fractal->parseIncludes('handles');

        // Build fractal item
        $item = new Item($seller, new SellerTransformer, 'sellers');

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
        Transaction::open('update', 'sellers.update');

        // Fetch seller
        $seller = $this->sellers->find($id);

        // Run the updates
        $this->sellers->update($seller, $this->request->input('data.attributes'));

        // Return the show response
        return $this->show($id);
    }

    public function massUpdateFlags()
    {
        //No flag has been provided
        if (! $flag = $this->request->input('flag')) {
            throw new BadRequestHttpException('You must provide a flag to perform a mass flag update.');
        }

        //Open a transaction
        Transaction::open('update', 'seller.massUpdate');

        //Fetch filters from request
        $filters = $this->request->get('filter', []);

        //Run update flag operation
        $sellers = $this->sellers
            ->setAccount(Auth::getAccount())
            ->massUpdateFlags($flag, $filters);

        //Build data collection
        $data = [];
        foreach ($sellers as $seller) {
            array_push($data, [
                'type' => 'sellers',
                'id'   => (string) $seller->id
            ]);
        }

        //Build response
        $response = [
            'data' => $data,
            'meta' => [
                'total' => $sellers->count()
            ]
        ];

        //Return success response
        return Response::json($response);
    }
}
