<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\AuthStore;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateEnforcer
{
    protected $auth;

    public function __construct(AuthStore $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        if (! $this->auth->isEnforcer()) {
            throw new AccessDeniedHttpException('You must be authenticated as an enforcer to perform this request.');
        }

        return $next($request);
    }
}
