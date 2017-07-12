<?php

namespace App\Http\Middleware;

use Closure;
use App\AuthStore;

class CaptureToken
{
    protected $auth;

    public function __construct(AuthStore $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        if (
            ($token = $request->header('Authorization'))
            || ($token = $request->input('token'))
        ) {
            $this->auth->processToken($token);
        }

        return $next($request);
    }
}
