<?php

namespace App\Http\Controllers\Api;

use Response;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use App\Transformers\UserTransformer;
use App\Contracts\UserRepositoryInterface;
use League\Fractal\Serializer\JsonApiSerializer;
use Illuminate\Auth\Access\AuthorizationException;

class AuthController extends Controller
{
    protected $request;

    protected $users;

    public function __construct(Request $request, UserRepositoryInterface $users, Manager $fractal)
    {
        $this->request = $request;
        $this->users   = $users;
        $this->fractal = $fractal;

        $this->fractal->setSerializer(new JsonApiSerializer);
    }

    public function auth()
    {
        try {

            //Grab our user entity if possible
            $user = $this->users->authenticate(
                $this->request->get('email'),
                $this->request->get('password')
            );

            //Generate a token for the found user
            $token = $this->users->generateTokenForUser($user);

        }

        //Authorisation failed, throw a 401
        catch (AuthorizationException $e) {
            return Response::json([
                'errors' => [
                    [
                        'status' => 401,
                        'title'  => 'Incorrect credentials',
                        'detail' => 'Either your email address or password is incorrect, please try again.'
                    ]
                ]
            ], 401);
        }

        //Enable account includes
        $this->fractal->parseIncludes('accessible_accounts');

        //Build our user resource
        $resource = new Item($user, new UserTransformer, 'users');

        //Tack on our token as a meta value
        $resource->setMetaValue('token', $token);

        //Return success response
        return Response::json($this->fractal->createData($resource)->toArray());
    }

}
