<?php

namespace App\Http\Middleware;

use Closure;
use Transaction;

class CloseTransaction
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($transaction = Transaction::close()) {

            //Attach transaction ID to X-Transaction header
            $response->headers->set('X-Transaction', $transaction->id);

            //If possible to undo, attach undo action URL to X-Undo header
            if ($transaction->canUndo()) {
                $response->headers->set('X-Undo', url('api/transactions/'.$transaction->id.'/undo'));
            }

        }

        return $response;
    }
}
