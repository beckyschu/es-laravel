<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [],

        'api' => [
            \App\Http\Middleware\CaptureToken::class,
            \App\Http\Middleware\CaptureAccount::class,
            \App\Http\Middleware\CloseTransaction::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'         => \App\Http\Middleware\Authenticate::class,
        'auth-user'    => \App\Http\Middleware\AuthenticateUser::class,
        'auth-crawler' => \App\Http\Middleware\AuthenticateCrawler::class,
        'auth-enforcer' => \App\Http\Middleware\AuthenticateEnforcer::class,
        'throttle'     => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];
}
