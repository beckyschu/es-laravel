<?php

namespace App\Http\Controllers;

use Artisan;
use App\Http\Controllers\Controller;

class AppController extends Controller
{
    public function app()
    {
        return view('app');
    }

    public function deploy()
    {
        Artisan::call('shark:deploy');

        return response('Deployment complete.');
    }
}
