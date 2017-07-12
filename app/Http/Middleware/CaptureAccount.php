<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use App\AuthStore;

class CaptureAccount
{
    public function handle($request, Closure $next)
    {
        if (
            ($account = $request->header('X-Account'))
            || ($account = $request->input('account'))
        ) {
            Auth::setAccount($account);
        }

        return $next($request);
    }
}
