<?php

namespace App\Http\Controllers\Api;

use Tiny;
use Image;
use Response;
use Transaction;
use App\AuthStore;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use League\Fractal\Resource\Collection;
use App\Contracts\UserRepositoryInterface;
use App\Transformers\TransactionTransformer;
use League\Fractal\Serializer\JsonApiSerializer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UsersController extends Controller
{
    protected $request;

    protected $fractal;

    protected $auth;

    protected $users;

    public function __construct(
        Request $request,
        Manager $fractal,
        AuthStore $auth,
        UserRepositoryInterface $users
    ) {
        $this->request = $request;
        $this->fractal = $fractal;
        $this->auth    = $auth;
        $this->users   = $users;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function me()
    {
        //Grab user from auth store
        $user = $this->auth->getUser();

        //Enable account includes
        $this->fractal->parseIncludes('accessible_accounts');

        //Build our user resource
        $resource = new Item($user, new UserTransformer, 'users');

        //Return success response
        return Response::json($this->fractal->createData($resource)->toArray());
    }

    public function listing()
    {
        //Fetch filters from request
        $filters = $this->request->get('filter', []);

        //Grab user collection
        $users = $this->users->get($filters);

        //Build our user collection
        $collection = new Collection($users, new UserTransformer, 'users');

        //Return success response
        return Response::json($this->fractal->createData($collection)->toArray());
    }

    public function show($id)
    {
        //Fetch user
        $user = $this->users->find($id);

        //Enable includes
        $this->fractal->parseIncludes('accounts,revisions');

        //Build fractal item
        $item = new Item($user, new UserTransformer, 'users');

        //Build response
        $response = $this->fractal->createData($item)->toArray();

        //Return success response
        return Response::json($response);
    }

    public function create()
    {
        //No post data found
        if (! $attributes = $this->request->input('data.attributes')) {
            throw new BadRequestHttpException('You must provide attributes to create a user.');
        }

        //Open a transaction
        Transaction::open('create', 'users.create');

        //Run the create
        $result = $this->users->create($attributes);

        //Accounts relationship provided
        if ($accounts = $this->request->input('data.relationships.accounts.data')) {

            //Loop accounts and build an array of IDs
            $account_ids = [];
            foreach ($accounts as $account) {
                array_push($account_ids, $account['id']);
            }

            //Run the updates
            $this->users->attachAccounts($result->user->id, $account_ids);

        }

        //Build fractal item
        $item = new Item($result->user, new UserTransformer, 'users');

        //Build response
        $response = $this->fractal->createData($item)->toArray();

        //Add temporary password meta
        $response['meta']['password'] = $result->password;

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
        Transaction::open('update', 'users.update');

        //Run the updates
        $this->users->update($id, $attributes);

        //Return the show response
        return $this->show($id);
    }

    public function updateImage($id)
    {
        //Find or fail the user
        $user = $this->users->find($id);

        //No image provided
        if (! $this->request->hasFile('image')) {
            throw new BadRequestHttpException('You must provide an image to upload.');
        }

        //Grab the file
        $file = $this->request->file('image');

        //Something went wrong with the upload
        if (! $file->isValid()) {
            throw new BadRequestHttpException('There was a problem with the image you uploaded. Please try uploading it again.');
        }

        ///////////////////////
        // Move to temporary //
        ///////////////////////

        //Generate our tmp storage path
        $storage = storage_path('app/tmp');

        //Generate a tmp filename
        $tmpFilename = 'profile_'.$id.'.'.$file->guessExtension();

        //Move the uploaded file to tmp
        $file = $file->move(storage_path('app/tmp'), $tmpFilename);

        ///////////////////////////////
        // Create intervention image //
        ///////////////////////////////

        //Create an intervention image
        $image = Image::make($file->getRealPath());

        //Crop the image
        $image->fit(120);

        ///////////////////////////////
        // Save our production image //
        ///////////////////////////////

        //Generate a proper filename
        $actualFilename = 'profile_'.Tiny::to($id).'.jpg';

        //Save the proper image
        $image->save(storage_path('app/public/profiles/'.$actualFilename));

        //Remove the tmp file
        unlink(storage_path('app/tmp/'.$tmpFilename));

        //Return successful
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

        // Delete the user
        $this->users->delete($id, $permanent);

        // Return success
        return response()->make(null, 204);
    }

    public function resetPassword($id)
    {
        //Run the updates
        $password = $this->users->resetPassword($id);

        //Return success response
        return Response::json([
            'meta' => [
                'password' => $password
            ]
        ]);
    }

    public function detachAccounts($id)
    {
        //No relationship data found
        if (! ($accounts = $this->request->input('data')) || ! is_array($accounts)) {
            throw new BadRequestHttpException('You must provide an array of accounts to be detached from the user.');
        }

        //Loop accounts and build an array of IDs
        $account_ids = [];
        foreach ($accounts as $account) {
            array_push($account_ids, $account['id']);
        }

        //Run the updates
        $this->users->detachAccounts($id, $account_ids);

        //Return success response
        return Response::make(null, 204);
    }

    public function attachAccounts($id)
    {
        //No relationship data found
        if (! ($accounts = $this->request->input('data')) || ! is_array($accounts)) {
            throw new BadRequestHttpException('You must provide an array of accounts to be attached to the user.');
        }

        //Loop accounts and build an array of IDs
        $account_ids = [];
        foreach ($accounts as $account) {
            array_push($account_ids, $account['id']);
        }

        //Run the updates
        $this->users->attachAccounts($id, $account_ids);

        //Return success response
        return Response::make(null, 204);
    }

    public function showEvents($id)
    {
        //Grab requested page
        $page = (int) $this->request->get('page') ?: 1;

        //Fetch paginated data
        $transactions = app('App\Contracts\TransactionRepositoryInterface')->paginateForUser($id, $page);

        //Enable includes
        $this->fractal->parseIncludes('user,account');

        //Build fractal item
        $collection = new Collection($transactions, new TransactionTransformer, 'transactions');

        //Build response
        $response = $this->fractal->createData($collection)->toArray();

        //Add meta data
        $response['meta'] = [
            'total' => $transactions->total(),
            'page'  => $page
        ];

        //Grab current URL
        $url = urldecode($this->request->fullUrl());

        //Generate next page link
        $nextPageLink = null;
        if ($transactions->hasMorePages()) {
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
}
