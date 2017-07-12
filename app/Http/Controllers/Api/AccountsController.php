<?php

namespace App\Http\Controllers\Api;

use Response;
use Transaction;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Collection;
use App\Transformers\AccountTransformer;
use App\Contracts\AccountRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AccountsController extends Controller
{
    protected $request;

    protected $fractal;

    protected $accounts;

    public function __construct(
        Request $request,
        Manager $fractal,
        AccountRepositoryInterface $accounts
    ) {
        $this->request  = $request;
        $this->fractal  = $fractal;
        $this->accounts = $accounts;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function listing()
    {
        //Grab accounts collection
        $accounts = $this->accounts->all();

        //Build our accounts collection
        $collection = new Collection($accounts, new AccountTransformer, 'accounts');

        //Return success response
        return Response::json($this->fractal->createData($collection)->toArray());
    }

    public function show($id)
    {
        //Fetch account
        $account = $this->accounts->find($id);

        //Enable includes
        $this->fractal->parseIncludes('assets,users,revisions');

        //Build fractal item
        $item = new Item($account, new AccountTransformer, 'accounts');

        //Build response
        $response = $this->fractal->createData($item)->toArray();

        //Return success response
        return Response::json($response);
    }

    public function update($id)
    {
        //No patch data found
        if (! $attributes = $this->request->input('data.attributes')) {
            throw new BadRequestHttpException('You must provide attributes to perform an update.');
        }

        //Open a transaction
        Transaction::open('update', 'account.update');

        //Run the updates
        $this->accounts->update($id, $attributes);

        //Return the show response
        return $this->show($id);
    }

    public function create()
    {
        //No post data found
        if (! $attributes = $this->request->input('data.attributes')) {
            throw new BadRequestHttpException('You must provide attributes to create an account.');
        }

        //Run the create
        $account = $this->accounts->create($attributes);

        //Return the show response
        return $this->show($account->id);
    }

    public function detachUsers($id)
    {
        //No relationship data found
        if (! ($users = $this->request->input('data')) || ! is_array($users)) {
            throw new BadRequestHttpException('You must provide an array of users to be detached from the account.');
        }

        //Loop users and build an array of IDs
        $user_ids = [];
        foreach ($users as $user) {
            array_push($user_ids, $user['id']);
        }

        //Run the updates
        $this->accounts->detachUsers($id, $user_ids);

        //Return success response
        return Response::make(null, 204);
    }

    public function attachUsers($id)
    {
        //No relationship data found
        if (! ($users = $this->request->input('data')) || ! is_array($users)) {
            throw new BadRequestHttpException('You must provide an array of users to be attached to the account.');
        }

        //Loop users and build an array of IDs
        $user_ids = [];
        foreach ($users as $user) {
            array_push($user_ids, $user['id']);
        }

        //Run the updates
        $this->accounts->attachUsers($id, $user_ids);

        //Return success response
        return Response::make(null, 204);
    }

    public function delete($id, Request $request)
    {
        // Check user password
        if (! \Auth::getUser()->checkPassword($request->input('password'))) {
            throw new BadRequestHttpException('Your password is incorrect.');
        }

        // Grab permanent flag
        $permanent = $request->input('permanent', false);

        // Delete the account
        $this->accounts->delete($id, $permanent);

        // Return success
        return response()->make(null, 204);
    }
}
