<?php

namespace App\Http\Controllers\Api;

use Response;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Collection;
use App\Transformers\EnforcerTransformer;
use App\Contracts\EnforcerRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;

class EnforcersController extends Controller
{
    protected $request;

    protected $fractal;

    protected $enforcers;

    public function __construct(
        Request $request,
        Manager $fractal,
        EnforcerRepositoryInterface $enforcers
    ) {
        $this->request   = $request;
        $this->fractal   = $fractal;
        $this->enforcers = $enforcers;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //Grab enforcers collection
        $enforcers = $this->enforcers->all();

        //Build our enforcers collection
        $collection = new Collection($enforcers, new EnforcerTransformer, 'enforcers');

        //Return success response
        return Response::json($this->fractal->createData($collection)->toArray());
    }

    public function show($id)
    {
        //Fetch enforcer
        $enforcer = $this->enforcers->find($id);

        //Enable includes
        $this->fractal->parseIncludes('scans');

        //Build fractal item
        $item = new Item($enforcer, new EnforcerTransformer, 'enforcers');

        //Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }

    public function token($id)
    {
        //Fetch token
        $token = $this->enforcers->generateToken($id);

        //Return success response
        return Response::json(['data' => $token]);
    }

    public function reset($id)
    {
        //Run the reset
        $enforcer = $this->enforcers->reset($id);

        //Build fractal item
        $item = new Item($enforcer, new EnforcerTransformer, 'enforcers');

        //Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }
}
