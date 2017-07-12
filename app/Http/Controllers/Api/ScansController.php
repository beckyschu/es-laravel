<?php

namespace App\Http\Controllers\Api;

use App;
use Auth;
use Response;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use App\Transformers\ScanTransformer;
use App\Transformers\DiscoveryTransformer;
use League\Fractal\Resource\Collection;
use App\Contracts\ScanRepositoryInterface;
use App\Contracts\DiscoveryRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ScansController extends Controller
{
    protected $request;

    protected $fractal;

    protected $scans;

    protected $discoveries;

    public function __construct(
        Request $request,
        Manager $fractal,
        ScanRepositoryInterface $scans,
        DiscoveryRepositoryInterface $discoveries
    ) {
        $this->request = $request;
        $this->fractal = $fractal;
        $this->scans   = $scans;
	$this->discoveries = $discoveries;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function show($id)
    {
        //Fetch scan
        $scan = $this->scans->find($id);

        //Enable includes
        $this->fractal->parseIncludes('enforcer');

        //Build fractal item
        $item = new Item($scan, new ScanTransformer, 'scans');

        //Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }

    public function start($id)
    {
        $scan = $this->scans->start($id);
	//$discoveries = $this->discoveries->getPendingForPlatform($scan->enforcer->platform);

        $this->fractal->parseIncludes('enforcer,discoveries');
        $item = new Item($scan, new ScanTransformer, 'scans');
	
        $response = $this->fractal->createData($item)->toArray();

        return Response::json($response);
    }

    public function stop($id)
    {
        $attributes = $this->request->only('memory_usage');

        $scan = $this->scans->stop($id, $attributes);

        $item = new Item($scan, new ScanTransformer, 'scans');
        $response = $this->fractal->createData($item)->toArray();

        return Response::json($response);
    }

    public function schedule()
    {
        $enforcer = $this->request->input('data.enforcer');
        $this->scans->schedule($enforcer);
        return Response::make(null, 201);
    }

    public function showLog($id)
    {
        // Fetch scan
        $scan = $this->scans->find($id);

        // Fetch log
        if ($log = $scan->log_contents) {
            return Response::json([
                'data' => [
                    'log' => $log[0],
                    'updated_at' => $log[1]->toIso8601String(),
                    'status' => $scan->status
                ]
            ]);
        }

        return Response::json([
            'data' => null
        ]);
    }
}
