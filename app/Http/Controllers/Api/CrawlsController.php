<?php

namespace App\Http\Controllers\Api;

use App;
use Auth;
use Response;
use App\Transformers;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use App\Transformers\CrawlTransformer;
use League\Fractal\Resource\Collection;
use App\Contracts\CrawlRepositoryInterface;
use League\Fractal\Serializer\ArraySerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CrawlsController extends Controller
{
    protected $request;

    protected $fractal;

    protected $crawls;

    public function __construct(
        Request $request,
        Manager $fractal,
        CrawlRepositoryInterface $crawls
    ) {
        $this->request = $request;
        $this->fractal = $fractal;
        $this->crawls  = $crawls;

        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function show($id)
    {
        //Fetch crawl
        $crawl = $this->crawls->find($id);

        //Enable includes
        $this->fractal->parseIncludes('failures,asset,asset.account,crawler');

        //Build fractal item
        $item = new Item($crawl, new CrawlTransformer, 'crawls');

        //Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }

    public function update($id)
    {
        // Collect attributes
        $attributes = $this->request->input();

        // Update the crawl
        $crawl = $this->crawls->update($id, $attributes);

        // Build fractal item
        $item = new Item($crawl, new CrawlTransformer, 'crawls');

        // Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }

    public function showLog($id)
    {
        //Fetch crawl
        $crawl = $this->crawls->find($id);

        //Fetch crawl log
        if ($log = $crawl->log_contents) {
            return Response::json([
                'data' => [
                    'log' => $log[0],
                    'updated_at' => $log[1]->toIso8601String(),
                    'status' => $crawl->status
                ]
            ]);
        }

        return Response::json([
            'data' => null
        ]);
    }

    public function start($id)
    {
        $crawl = $this->crawls->start($id);

        $this->fractal->parseIncludes('asset,crawler,keyword,keyword.settings');

        $item = new Item($crawl, new Transformers\CrawlBotTransformer, 'crawls');
        $response = $this->fractal->createData($item)->toArray();

        return Response::json($response);
    }

    public function stop($id)
    {
        $attributes = $this->request->only('memory_usage');

        $crawl = $this->crawls->stop($id, $attributes);

        $item = new Item($crawl, new Transformers\CrawlBotTransformer, 'crawls');
        $response = $this->fractal->createData($item)->toArray();

        return Response::json($response);
    }

    public function schedule()
    {
        // Crawler ID(s) have not been provided
        if (! $crawlerIds = $this->request->input('data.crawlers')) {
            throw new BadRequestHttpException('You must provide a valid crawler ID to schedule a crawl.');
        }

        // A valid asset ID has not been provided
        if (
            ! ($assetId = $this->request->input('data.asset'))
            || ! ($asset = app('App\Contracts\AssetRepositoryInterface')->find($assetId))
        ) {
            throw new BadRequestHttpException('You must provide a valid asset ID to schedule a crawl.');
        }

        // A keyword has not been provided
        if (! $keywords = $this->request->input('data.keywords')) {
            throw new BadRequestHttpException('You must provide a keyword to schedule a crawl.');
        }

        // Fetch the mode, default to light
        $mode = $this->request->input('data.mode', 'light');

        // Schedule the crawl through the repository
        app('App\Contracts\CrawlRepositoryInterface')->schedule($asset->id, $crawlerIds, $keywords, $mode);

        // Return an empty 201 response
        return Response::make(null, 201);
    }

    public function cancel($id)
    {
        // Cancel crawl
        $crawl = $this->crawls->cancel($id);

        // Enable includes
        $this->fractal->parseIncludes('failures,asset,asset.account,crawler');

        // Build fractal item
        $item = new Item($crawl, new CrawlTransformer, 'crawls');

        // Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }
}
