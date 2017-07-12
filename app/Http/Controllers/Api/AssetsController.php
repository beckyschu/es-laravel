<?php

namespace App\Http\Controllers\Api;

use Response;
use Transaction;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use App\Models\EbayCategory;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use App\Transformers\AssetTransformer;
use League\Fractal\Resource\Collection;
use App\Contracts\AssetRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssetsController extends Controller
{
    protected $request;

    protected $fractal;

    protected $assets;

    public function __construct(
        Request $request,
        Manager $fractal,
        AssetRepositoryInterface $assets
    ) {
        $this->request = $request;
        $this->fractal = $fractal;
        $this->assets  = $assets;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //Grab assets collection
        $assets = $this->assets->all();

        //Enable includes
        $this->fractal->parseIncludes('account,keywords');

        //Build our accounts collection
        $collection = new Collection($assets, new AssetTransformer, 'assets');

        //Return success response
        return Response::json($this->fractal->createData($collection)->toArray());
    }

    public function listingForAccount($account)
    {
        $this->assets->setAccount($account);

        return $this->listing();
    }

    public function show($id)
    {
        //Fetch asset
        $asset = $this->assets->find($id);

        //Enable includes
        $this->fractal->parseIncludes('account,crawls,crawls.crawler,keywords');

        //Build fractal item
        $item = new Item($asset, new AssetTransformer, 'assets');

        //Build response
        $response = $this->fractal->createData($item)->toArray();

        // Add eBay categories to meta
        $response['meta'] = [
            'ebay_categories' => EbayCategory::orderBy('name', 'ASC')->get()
        ];

        //Return success response
        return Response::json($response);
    }

    public function update($id)
    {
        //No patch data found
        if (! $attributes = $this->request->input('data.attributes')) {
            throw new BadRequestHttpException('You must provide attributes to perform an update.');
        }

        //Grab account relationship
        $account = $this->request->input('data.relationships.account.data.id');

        //Open a transaction
        Transaction::open('update', 'asset.update');

        //Run the updates
        $this->assets->update($id, $attributes + ['account' => $account]);

        //Return the show response
        return $this->show($id);
    }

    public function create()
    {
        //No post data found
        if (! $attributes = $this->request->input('data.attributes')) {
            throw new BadRequestHttpException('You must provide attributes to create an asset.');
        }

        //Grab account relationship
        $account = $this->request->input('data.relationships.account.data.id');

        //Open a transaction
        Transaction::open('create', 'asset.create');

        //Run the create
        $asset = $this->assets->create($attributes + ['account' => $account]);

        //Return the show response
        return $this->show($asset->id);
    }

    public function delete($id, Request $request)
    {
        // Check user password
        if (! \Auth::getUser()->checkPassword($request->input('password'))) {
            throw new BadRequestHttpException('Your password is incorrect.');
        }

        // Grab permanent flag
        $permanent = $request->input('permanent', false);

        // Delete the asset
        $this->assets->delete($id, $permanent);

        // Return success
        return response()->make(null, 204);
    }
}
