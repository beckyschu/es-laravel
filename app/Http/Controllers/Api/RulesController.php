<?php

namespace App\Http\Controllers\Api;

use Auth, Response, Transaction;
use App\Http\Controllers\Controller;
use App\Repositories, App\Transformers;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RulesController extends Controller
{
    protected $fractal;

    public function __construct(\League\Fractal\Manager $fractal)
    {
        $this->fractal = $fractal;
        $this->fractal->setSerializer(new \League\Fractal\Serializer\ArraySerializer);
    }

    public function listing(Repositories\Eloquent\DiscoveryRuleRepository $rulesRepo)
    {
        // Fetch all rules
        $rules = $rulesRepo->all();

        // Build our rules collection
        $collection = new \League\Fractal\Resource\Collection($rules, new Transformers\DiscoveryRuleTransformer);

        // Return success response
        return Response::json($this->fractal->createData($collection)->toArray());
    }

    public function show($id, Repositories\Eloquent\DiscoveryRuleRepository $rulesRepo)
    {
        // Fetch rule
        $rule = $rulesRepo->find($id);

        // Build fractal item
        $item = new \League\Fractal\Resource\Item($rule, new Transformers\DiscoveryRuleTransformer);

        // Return success response
        return Response::json($this->fractal->createData($item)->toArray());
    }

    public function update(
        $id,
        \Illuminate\Http\Request $request,
        Repositories\Eloquent\DiscoveryRuleRepository $rulesRepo
    ) {
        // Fetch rule
        $rule = $rulesRepo->find($id);

        // No patch data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes to perform an update.');
        }

        // Rule is locked
        if ($rule->is_locked) {
            throw new BadRequestHttpException('This rule is locked.');
        }

        // Open a transaction
        Transaction::open('update', 'rules.update');

        // Run the update
        $rulesRepo->update($rule, $attributes);

        // Return the show response
        return $this->show($id, $rulesRepo);
    }

    public function create(
        \Illuminate\Http\Request $request,
        Repositories\Eloquent\DiscoveryRuleRepository $rulesRepo
    ) {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes to create an rule.');
        }

        // Open a transaction
        Transaction::open('create', 'rules.create');

        // Run the create
        $asset = $rulesRepo->create($attributes);

        // Return the show response
        return $this->show($asset->id, $rulesRepo);
    }
}
