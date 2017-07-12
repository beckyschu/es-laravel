<?php

namespace App\Exceptions;

use Response;
use Exception;
use Psr\Log\LoggerInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        TokenExpiredException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @throws \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        // Do not report exception
        if ($this->shouldntReport($e)) {
            return;
        }

        // Send exception to Bugsnag
        if (app()->bound('bugsnag')) {
            app('bugsnag')->notifyException($e, null, 'error');
        }

        // Attempt to create logger
        try {
            $logger = $this->container->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

        // Log error message only (reduce log file verbosity)
        // We don't need a full stack trace here as Bugsnag records it
        $logger->error($e->getMessage());
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof BadRequestHttpException) {
            return Response::json([
                'errors' => [
                    [
                        'status' => 400,
                        'title'  => 'Bad Request',
                        'detail' => $e->getMessage()
                    ]
                ]
            ], 400);
        }

        if ($e instanceof AccessDeniedHttpException) {
            return Response::json([
                'errors' => [
                    [
                        'status' => 403,
                        'title'  => 'Forbidden',
                        'detail' => $e->getMessage()
                    ]
                ]
            ], 403);
        }

        if ($e instanceof ConflictHttpException) {
            return Response::json([
                'errors' => [
                    [
                        'status' => 409,
                        'title'  => 'Conflict',
                        'detail' => $e->getMessage()
                    ]
                ]
            ], 400);
        }

        if ($e instanceof TokenExpiredException) {
            return Response::json([
                'errors' => [
                    [
                        'status' => 403,
                        'title'  => 'Forbidden',
                        'detail' => $e->getMessage()
                    ]
                ]
            ], 400);
        }

        if ($e instanceof ValidationException) {
            return Response::json($e->getResponse(), 422);
        }

        return parent::render($request, $e);
    }


    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        return redirect()->guest('login');
    }
}
