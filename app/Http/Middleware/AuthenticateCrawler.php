<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\AuthStore;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateCrawler
{
    protected $auth;

    public function __construct(AuthStore $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        if (! $this->auth->isCrawler()) {
            throw new AccessDeniedHttpException('You must be authenticated as a crawler to perform this request.');
        }

        return $next($request);
    }
}
