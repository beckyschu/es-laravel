<?php

namespace App\Http\Controllers\Api;

use Response;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Collection;
use App\Transformers\CrawlerTransformer;
use App\Contracts\CrawlerRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;

class CrawlersController extends Controller
{
    protected $request;

    protected $fractal;

    protected $crawlers;

    public function __construct(
        Request $request,
        Manager $fractal,
        CrawlerRepositoryInterface $crawlers
    ) {
        $this->request  = $request;
        $this->fractal  = $fractal;
        $this->crawlers = $crawlers;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //Grab crawlers collection
        $crawlers = $this->crawlers->all();

        //Build our crawlers collection
        $collection = new Collection($crawlers, new CrawlerTransformer, 'crawlers');

        //Return success response
        return Response::json($this->fractal->createData($collection)->toArray());
    }

    public function show($id)
    {
        //Fetch crawler
        $crawler = $this->crawlers->find($id);

        //Enable includes
        $this->fractal->parseIncludes('crawls,crawls.asset,crawls.asset.account');

        //Build fractal item
        $item = new Item($crawler, new CrawlerTransformer, 'crawlers');

        //Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }

    public function token($id)
    {
        //Fetch token
        $token = $this->crawlers->generateToken($id);

        //Return success response
        return Response::json(['data' => $token]);
    }

    public function reset($id)
    {
        //Run the reset
        $crawler = $this->crawlers->reset($id);

        //Build fractal item
        $item = new Item($crawler, new CrawlerTransformer, 'crawlers');

        //Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }
}
